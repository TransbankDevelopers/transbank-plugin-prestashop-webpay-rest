import { test, expect } from '@playwright/test';
import { login, addProductToCart, goThroughCheckoutWithWebpay } from '../../helpers/checkout.js';
import { fillCardAndAuthenticate, continueToCommerce, extractTokenFromUrl } from '../../helpers/webpay-form.js';
import { getOrderCountByToken, getTransactionStatus, holdLock, isLockHeld, closePool } from '../../helpers/database.js';
import { expectOrderConfirmation, expectPaymentError, expectValidResponse } from '../../helpers/assertions.js';

/* ════════════════════════════════════════════════════════════════════════════
 *  Test 1 — Lock prevents duplicate orders
 *
 *  Deterministically reproduces the duplicated-tab race condition:
 *    1. Completes a normal payment up to the Transbank result screen.
 *    2. Intercepts return requests with a barrier (Promise).
 *    3. Opens a second tab with the same return URL.
 *    4. Releases both requests simultaneously.
 *    5. Verifies: no crash, no duplicate orders, valid response on both tabs.
 *
 *    Tab 1 ──┐                 ┌── Tab 2
 *             ▼                 ▼
 *         BARRIER (freezes until both are captured)
 *                     │
 *              releaseReturns()
 *               ┌─────┴─────┐
 *               ▼           ▼
 *          PrestaShop + MariaDB lock
 *           → only 1 order created
 * ════════════════════════════════════════════════════════════════════════════ */

test.describe('Webpay Plus — Lock prevents duplicate orders', () => {
  test('Both tabs resolve without error when the return URL receives the same token twice', async ({ browser, baseURL }) => {
    console.log('═══ START: Lock prevents duplicate orders ═══');
    const context = await browser.newContext({ baseURL, ignoreHTTPSErrors: true });

    // ── Barrier: intercept return requests ──
    
    let releaseReturns;
    let returnsReleased = false;
    const returnsCanContinue = new Promise((resolve) => {
      releaseReturns = resolve;
    });
    const commerceReturns = [];

    // Intercepts initial return requests (without retryCount) and freezes them.
    // Lock retries (with retryCount) pass through.
    await context.route(
      (url) => {
        const s = url.toString();

        return (
          s.includes('webpaypluspaymentvalidate') &&
          s.includes('token_ws=') &&
          !s.includes('retryCount')
        );
      },
      async (route) => {
        const requestUrl = route.request().url();
        commerceReturns.push(requestUrl);
        console.log(`[BARRIER] Return intercepted #${commerceReturns.length}: ${requestUrl}`);

        if (!returnsReleased) {
          await returnsCanContinue;
        }

        await route.continue();
      }
    );

    const page = await context.newPage();
    let duplicatePage;

    try {
      // ── Full checkout up to Transbank ──

      await test.step('Login', async () => {
        await login(page);
      });

      await test.step('Add product to cart', async () => {
        await addProductToCart(page);
      });

      await test.step('Complete checkout with Webpay Plus', async () => {
        await goThroughCheckoutWithWebpay(page);
      });

      await test.step('Complete payment form on Transbank', async () => {
        await fillCardAndAuthenticate(page);
      });

      // ── Capture URL and duplicate tab ──

      await test.step('Capture return URL and open duplicate tab', async () => {
        await continueToCommerce(page);

        await expect
          .poll(() => commerceReturns.length, {
            message: 'Waiting for first intercepted return',
            timeout: 45_000,
            intervals: [500],
          })
          .toBeGreaterThanOrEqual(1);

        const commerceReturnUrl = commerceReturns[0];
        console.log(`[TEST] Return URL captured: ${commerceReturnUrl}`);

        duplicatePage = await context.newPage();
        const duplicateNavigation = duplicatePage.goto(commerceReturnUrl, {
          waitUntil: 'commit',
          timeout: 45_000,
        });

        await expect
          .poll(() => commerceReturns.length, {
            message: 'Waiting for both returns to be intercepted',
            timeout: 45_000,
            intervals: [500],
          })
          .toBeGreaterThanOrEqual(2);

        console.log(`[BARRIER] Both returns intercepted (${commerceReturns.length}). Releasing...`);
        returnsReleased = true;
        releaseReturns();

        await Promise.all([
          page.waitForLoadState('load').catch(() => {}),
          duplicateNavigation?.catch(() => {}),
        ]);

        await Promise.all([
          page.waitForLoadState('networkidle').catch(() => {}),
          duplicatePage.waitForLoadState('networkidle').catch(() => {}),
        ]);
      });

      // ── Verification ──

      await test.step('Verify that 2 requests arrived with the same token', async () => {
        expect(commerceReturns).toHaveLength(2);

        const tokens = commerceReturns.map(extractTokenFromUrl);

        expect(tokens[0]).toBeTruthy();
        expect(tokens[0]).toBe(tokens[1]);
        console.log(`[RESULT] token_ws: ${tokens[0]}`);
      });

      await test.step('Verify both tabs show a valid response', async () => {
        for (const { label, p } of [
          { label: 'Tab 1 (original)', p: page },
          { label: 'Tab 2 (duplicate)', p: duplicatePage },
        ]) {
          await expect.poll(async () => {
            try {
              return !p.url().includes('webpaypluspaymentvalidate');
            } catch {
              return false;
            }
          }, { timeout: 30_000, intervals: [1_000], message: `${label}: waiting for navigation to finish` }).toBe(true);

          const { confirmation, paymentErr } = await expectValidResponse(p, label);
          console.log(`[RESULT] ${label}: url=${p.url()}, confirmation=${confirmation}, payment_error=${paymentErr}`);
        }
      });

      await test.step('Verify exactly 1 order was created in the database', async () => {
        const token = extractTokenFromUrl(commerceReturns[0]);
        expect(token, 'Could not extract token from the return URL').toBeTruthy();
        const orderCount = await getOrderCountByToken(token);
        const txStatus = await getTransactionStatus(token);
        console.log(`[DB] Orders created: ${orderCount}, Transaction status: ${txStatus}`);
        expect(orderCount, 'There must be exactly 1 order for this token').toBe(1);
        expect(txStatus, 'Transaction must be in APPROVED state (4)').toBe(4);
      });
    } finally {
      console.log('═══ END: Lock prevents duplicate orders ═══');
      await closePool();
      await context.close();
    }
  });
});

/* ════════════════════════════════════════════════════════════════════════════
 *  Test 2 — Retry when GET_LOCK times out
 *
 *  Forces the retry path by acquiring the lock externally via a dedicated
 *  MySQL connection before the PHP request arrives. The controller attempts
 *  GET_LOCK with a 10s timeout, it expires, and redirects with retryCount.
 *  When the external lock is released, the retry acquires the lock and
 *  processes the transaction.
 *
 *    mysql conn ── GET_LOCK(token) ─────────── RELEASE_LOCK
 *                                     ▲
 *    PHP request ── GET_LOCK(token, 10s) ──┘ timeout
 *                          │
 *                   retryNormalFlow(retryCount=1)
 *                          │
 *                   GET_LOCK(token, 10s) ── OK (lock released)
 *                          │
 *                   processes transaction
 * ════════════════════════════════════════════════════════════════════════════ */

test.describe('Webpay Plus — Retry when lock is busy', () => {
  test('Retry processes the transaction after GET_LOCK times out', async ({ browser, baseURL }) => {
    console.log('═══ START: Retry when lock is busy ═══');
    const context = await browser.newContext({ baseURL, ignoreHTTPSErrors: true });

    // ── Intercept return request with barrier ──

    let releaseReturn;
    const returnCanContinue = new Promise((resolve) => {
      releaseReturn = resolve;
    });
    let returnUrl = '';
    let returnIntercepted = false;

    await context.route(
      (url) => {
        const s = url.toString();
        return (
          s.includes('webpaypluspaymentvalidate') &&
          s.includes('token_ws=') &&
          !s.includes('retryCount')
        );
      },
      async (route) => {
        returnUrl = route.request().url();
        returnIntercepted = true;
        console.log(`[BARRIER] Return intercepted: ${returnUrl}`);
        await returnCanContinue;
        await route.continue();
      }
    );

    const page = await context.newPage();
    let externalLock;

    // context.route() does not intercept requests from redirect-following,
    // but page.on('response') sees all responses including intermediate 302s.
    const retryRedirects = [];
    page.on('response', (res) => {
      const location = res.headers()['location'] || '';
      if (location.includes('retryCount')) {
        retryRedirects.push(location);
        console.log(`[RETRY] 302 redirect with retry: ${location}`);
      }
    });

    try {
      // ── Full checkout up to Transbank ──

      await test.step('Login', async () => {
        await login(page);
      });

      await test.step('Add product to cart', async () => {
        await addProductToCart(page);
      });

      await test.step('Complete checkout with Webpay Plus', async () => {
        await goThroughCheckoutWithWebpay(page);
      });

      await test.step('Complete payment form on Transbank', async () => {
        await fillCardAndAuthenticate(page);
      });

      // ── Force lock timeout ──

      await test.step('Capture return URL and acquire external lock', async () => {
        await continueToCommerce(page);

        await expect
          .poll(() => returnIntercepted, {
            message: 'Waiting for intercepted return',
            timeout: 45_000,
            intervals: [500],
          })
          .toBe(true);

        const token = extractTokenFromUrl(returnUrl);
        expect(token, 'Could not extract token from the return URL').toBeTruthy();
        console.log(`[TEST] Token captured: ${token}`);

        // Dedicated MySQL connection holds the lock so PHP's GET_LOCK(10s) times out
        // and triggers retryNormalFlow. The test releases it afterwards.
        externalLock = await holdLock(token);

        expect(await isLockHeld(token), 'External lock must be active before releasing the return').toBe(true);
        console.log('[TEST] External lock confirmed. Releasing return request...');

        releaseReturn();
      });

      // ── Wait for GET_LOCK timeout, release lock, wait for retry ──

      await test.step('Wait for GET_LOCK timeout and release lock for the retry', async () => {
        // PHP GET_LOCK(10s) timeout + sleep(1) + redirect ~ 12s.
        // Wait 13s to ensure PHP has already issued the retry redirect.
        await new Promise((r) => setTimeout(r, 13_000));

        await externalLock.release();
        externalLock = null;
        console.log('[TEST] External lock released. The retry should acquire the lock now.');

        await expect.poll(async () => {
          try {
            return !page.url().includes('webpaypluspaymentvalidate');
          } catch {
            return false;
          }
        }, { timeout: 60_000, intervals: [1_000], message: 'Waiting for the retry to finish processing' }).toBe(true);
      });

      // ── Verification ──

      await test.step('Verify at least one retry was executed', async () => {
        console.log(`[RETRY] Total redirects with retryCount: ${retryRedirects.length}`);
        expect(retryRedirects.length, 'PHP must have responded with at least one 302 with retryCount').toBeGreaterThanOrEqual(1);
        expect(retryRedirects[0], 'First redirect must have retryCount=1').toContain('retryCount=1');
      });

      await test.step('Verify the retry used the same token', async () => {
        const originalToken = extractTokenFromUrl(returnUrl);

        for (const url of retryRedirects) {
          const retryToken = extractTokenFromUrl(url);
          expect(retryToken, 'Retry must use the same token').toBe(originalToken);
        }
      });

      await test.step('Verify the page shows order confirmation', async () => {
        await page.waitForURL(/confirmacion-pedido/, { timeout: 30_000, waitUntil: 'load' });
        await expectOrderConfirmation(page);
      });

      await test.step('Verify exactly 1 order was created in the database', async () => {
        const token = extractTokenFromUrl(returnUrl);
        expect(token, 'Could not extract token from the return URL').toBeTruthy();
        const orderCount = await getOrderCountByToken(token);
        const txStatus = await getTransactionStatus(token);
        console.log(`[DB] Orders created: ${orderCount}, Transaction status: ${txStatus}`);
        expect(orderCount, 'There must be exactly 1 order for this token').toBe(1);
        expect(txStatus, 'Transaction must be in APPROVED state (4)').toBe(4);
      });
    } finally {
      await externalLock?.release();
      console.log('═══ END: Retry when lock is busy ═══');
      await closePool();
      await context.close();
    }
  });
});

/* ════════════════════════════════════════════════════════════════════════════
 *  Test 3 — Duplicate tab shows error after max retries exhausted
 *
 *  Simulates the real-world scenario: user duplicates the tab during the
 *  return from Transbank. Both tabs are released simultaneously, but an
 *  external lock simulates that Tab A (original) holds the lock for too
 *  long. Tab B (duplicate) cannot acquire it, exhausts all retries, and
 *  shows the payment error page. No assumptions are made about Tab A's
 *  outcome — only Tab B's graceful degradation is verified.
 *
 *    external lock ── GET_LOCK(token) ─────────── held until test ends
 *
 *    Tab A ──┐                ┌── Tab B
 *             ▼                ▼
 *         BARRIER (released simultaneously)
 *               ┌─────┴─────┐
 *               ▼           ▼
 *        Both blocked by external lock
 *        Tab B retries exhaust → error page
 *        No duplicate orders created
 * ════════════════════════════════════════════════════════════════════════════ */

test.describe('Webpay Plus — Max retries exhausted', () => {
  // 4 GET_LOCK(10s) + 3 sleep(1s) per tab ≈ 43s, plus checkout ≈ 15s.
  // Both tabs retry in parallel, so total ≈ 60s minimum.
  test('Duplicate tab shows error after retries exhaust', { timeout: 180_000 }, async ({ browser, baseURL }) => {
    console.log('═══ START: Max retries exhausted ═══');
    const context = await browser.newContext({ baseURL, ignoreHTTPSErrors: true });

    // ── Barrier: intercept return requests ──

    let releaseReturns;
    let returnsReleased = false;
    const returnsCanContinue = new Promise((resolve) => {
      releaseReturns = resolve;
    });
    const commerceReturns = [];

    // Intercepts initial return requests (without retryCount) and freezes them.
    // Lock retries (with retryCount) pass through.
    await context.route(
      (url) => {
        const s = url.toString();

        return (
          s.includes('webpaypluspaymentvalidate') &&
          s.includes('token_ws=') &&
          !s.includes('retryCount')
        );
      },
      async (route) => {
        const requestUrl = route.request().url();
        commerceReturns.push(requestUrl);
        console.log(`[BARRIER] Return intercepted #${commerceReturns.length}: ${requestUrl}`);

        if (!returnsReleased) {
          await returnsCanContinue;
        }

        await route.continue();
      }
    );

    const page = await context.newPage();
    let duplicatePage;
    let externalLock;
    const retryRedirects = [];

    try {
      // ── Full checkout up to Transbank ──

      await test.step('Login', async () => {
        await login(page);
      });

      await test.step('Add product to cart', async () => {
        await addProductToCart(page);
      });

      await test.step('Complete checkout with Webpay Plus', async () => {
        await goThroughCheckoutWithWebpay(page);
      });

      await test.step('Complete payment form on Transbank', async () => {
        await fillCardAndAuthenticate(page);
      });

      // ── Capture URL, duplicate tab, acquire lock, release both ──

      await test.step('Capture return URL, duplicate tab and acquire external lock', async () => {
        await continueToCommerce(page);

        await expect
          .poll(() => commerceReturns.length, {
            message: 'Waiting for first intercepted return',
            timeout: 45_000,
            intervals: [500],
          })
          .toBeGreaterThanOrEqual(1);

        const commerceReturnUrl = commerceReturns[0];
        console.log(`[TEST] Return URL captured: ${commerceReturnUrl}`);

        duplicatePage = await context.newPage();
        duplicatePage.goto(commerceReturnUrl, {
          waitUntil: 'commit',
          timeout: 120_000,
        });

        await expect
          .poll(() => commerceReturns.length, {
            message: 'Waiting for both returns to be intercepted',
            timeout: 45_000,
            intervals: [500],
          })
          .toBeGreaterThanOrEqual(2);

        // Acquire lock BEFORE releasing — simulates Tab A holding it too long.
        const token = extractTokenFromUrl(commerceReturnUrl);
        expect(token, 'Could not extract token from the return URL').toBeTruthy();
        externalLock = await holdLock(token);
        expect(await isLockHeld(token), 'External lock must be active before releasing returns').toBe(true);
        console.log(`[TEST] External lock acquired for token: ${token}`);

        // Listen for retry redirects BEFORE releasing the barrier to avoid race conditions.
        duplicatePage.on('response', (res) => {
          const location = res.headers()['location'] || '';
          if (location.includes('retryCount')) {
            retryRedirects.push(location);
            console.log(`[RETRY] Tab B 302 redirect: ${location}`);
          }
        });

        console.log(`[BARRIER] Both returns intercepted (${commerceReturns.length}). Releasing simultaneously...`);
        returnsReleased = true;
        releaseReturns();
      });

      // ── Wait for Tab B to exhaust retries ──
      // Each attempt: GET_LOCK(10s) + sleep(1s) = 11s. With 3 retries + 1 final
      // attempt that checks retryCount >= 3 (also blocks 10s on GET_LOCK first),
      // total ≈ 44s per tab.

      await test.step('Wait for Tab B retries to exhaust', async () => {
        await expect.poll(() => retryRedirects.length, {
          timeout: 60_000,
          intervals: [2_000],
          message: 'Waiting for all 3 retry redirects',
        }).toBeGreaterThanOrEqual(3);

        // After 3 redirects, the 4th attempt (retryCount=3) still blocks on
        // GET_LOCK(10s) before checking the retry limit and rendering the error page.
        await expect.poll(async () => {
          try {
            const content = await duplicatePage.content();
            return content.includes('Reintentar pago') || content.includes('errorMessage');
          } catch {
            return false;
          }
        }, { timeout: 30_000, intervals: [1_000], message: 'Waiting for Tab B error page to render' }).toBe(true);
      });

      // ── Verification ──

      await test.step('Verify all 3 retries were attempted on Tab B', async () => {
        console.log(`[RETRY] Total Tab B redirects: ${retryRedirects.length}`);
        expect(retryRedirects.length, 'Must have exactly 3 retry redirects').toBe(3);
        expect(retryRedirects[0], 'First redirect must be retryCount=1').toContain('retryCount=1');
        expect(retryRedirects[1], 'Second redirect must be retryCount=2').toContain('retryCount=2');
        expect(retryRedirects[2], 'Third redirect must be retryCount=3').toContain('retryCount=3');
      });

      await test.step('Verify Tab B shows a payment error (not a fatal error)', async () => {
        await expectPaymentError(duplicatePage);
      });

      await test.step('Verify no duplicate order was created', async () => {
        const token = extractTokenFromUrl(commerceReturns[0]);
        const orderCount = await getOrderCountByToken(token);
        console.log(`[DB] Orders for this token: ${orderCount}`);
        expect(orderCount, 'At most 1 order must exist (no duplicate from Tab B)').toBeLessThanOrEqual(1);
      });
    } finally {
      await externalLock?.release();
      console.log('═══ END: Max retries exhausted ═══');
      await closePool();
      await context.close();
    }
  });
});

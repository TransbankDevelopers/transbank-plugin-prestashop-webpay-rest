import { test, expect } from "@playwright/test";
import {
    login,
    addProductToCart,
    goThroughCheckoutWithWebpay
} from "../../../helpers/checkout.js";
import {
    fillCardAndAuthenticate,
    continueToCommerce,
    extractTokenFromUrl
} from "../../../helpers/webpay-form.js";
import {
    getOrderCountByToken,
    getTransactionStatus,
    holdLock,
    isLockHeld,
    closePool
} from "../../../helpers/database.js";
import { expectOrderConfirmation } from "../../../helpers/assertions.js";

/* ════════════════════════════════════════════════════════════════════════════
 *  Retry when GET_LOCK times out
 *
 *  Forces the retry path by acquiring the lock externally via a dedicated
 *  MySQL connection before the PHP request arrives. The controller attempts
 *  GET_LOCK with a 5s timeout, it expires, and retries internally.
 *  When the external lock is released, the next retry acquires the lock
 *  and processes the transaction.
 *
 *    mysql conn ── GET_LOCK(token) ─────────── RELEASE_LOCK
 *                                     ▲
 *    PHP request ── GET_LOCK(token, 5s) ──┘ timeout
 *                          │
 *                   internal retry
 *                          │
 *                   GET_LOCK(token, 5s) ── OK (lock released)
 *                          │
 *                   processes transaction
 * ════════════════════════════════════════════════════════════════════════════ */

test.describe("Webpay Plus — Retry when lock is busy", () => {
    test("Internal retry processes the transaction after GET_LOCK times out", async ({
        browser,
        baseURL
    }) => {
        console.log("═══ START: Retry when lock is busy ═══");
        const context = await browser.newContext({
            baseURL,
            ignoreHTTPSErrors: true
        });

        // ── Intercept return request with barrier ──

        let releaseReturn;
        const returnCanContinue = new Promise((resolve) => {
            releaseReturn = resolve;
        });
        let returnUrl = "";
        let returnIntercepted = false;

        await context.route(
            (url) => {
                const s = url.toString();
                return (
                    s.includes("webpaypluspaymentvalidate") &&
                    s.includes("token_ws=")
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

        try {
            // ── Full checkout up to Transbank ──

            await test.step("Login", async () => {
                await login(page);
            });

            await test.step("Add product to cart", async () => {
                await addProductToCart(page);
            });

            await test.step("Complete checkout with Webpay Plus", async () => {
                await goThroughCheckoutWithWebpay(page);
            });

            await test.step("Complete payment form on Transbank", async () => {
                await fillCardAndAuthenticate(page);
            });

            // ── Force lock timeout ──

            await test.step("Capture return URL and acquire external lock", async () => {
                await continueToCommerce(page);

                await expect
                    .poll(() => returnIntercepted, {
                        message: "Waiting for intercepted return",
                        timeout: 45_000,
                        intervals: [500]
                    })
                    .toBe(true);

                const token = extractTokenFromUrl(returnUrl);
                expect(
                    token,
                    "Could not extract token from the return URL"
                ).toBeTruthy();
                console.log(`[TEST] Token captured: ${token}`);

                externalLock = await holdLock(token);

                expect(
                    await isLockHeld(token),
                    "External lock must be active before releasing the return"
                ).toBe(true);
                console.log(
                    "[TEST] External lock confirmed. Releasing return request..."
                );

                releaseReturn();
            });

            // ── Wait for GET_LOCK timeout, release lock for the internal retry ──

            await test.step("Wait for GET_LOCK timeout and release lock for the retry", async () => {
                // PHP GET_LOCK(5s) timeout before the second attempt starts immediately.
                // Wait 6s to ensure PHP's first attempt has timed out and the retry has started.
                await new Promise((r) => setTimeout(r, 6_000));

                await externalLock.release();
                externalLock = null;
                console.log(
                    "[TEST] External lock released. The internal retry should acquire the lock now."
                );

                await expect
                    .poll(
                        async () => {
                            try {
                                return !page
                                    .url()
                                    .includes("webpaypluspaymentvalidate");
                            } catch {
                                return false;
                            }
                        },
                        {
                            timeout: 60_000,
                            intervals: [1_000],
                            message:
                                "Waiting for the retry to finish processing"
                        }
                    )
                    .toBe(true);
            });

            // ── Verification ──

            await test.step("Verify the page shows order confirmation", async () => {
                await page.waitForURL(/confirmacion-pedido/, {
                    timeout: 30_000,
                    waitUntil: "load"
                });
                await expectOrderConfirmation(page);
                console.log(`[RESULT] Page: url=${page.url()}, confirmation=true`);
            });

            await test.step("Verify exactly 1 order was created in the database", async () => {
                const token = extractTokenFromUrl(returnUrl);
                expect(
                    token,
                    "Could not extract token from the return URL"
                ).toBeTruthy();
                const orderCount = await getOrderCountByToken(token);
                const txStatus = await getTransactionStatus(token);
                console.log(
                    `[DB] Orders created: ${orderCount}, Transaction status: ${txStatus}`
                );
                expect(
                    orderCount,
                    "There must be exactly 1 order for this token"
                ).toBe(1);
                expect(
                    txStatus,
                    "Transaction must be in APPROVED state (4)"
                ).toBe(4);
            });
        } finally {
            await externalLock?.release();
            console.log("═══ END: Retry when lock is busy ═══");
            await closePool();
            await context.close();
        }
    });
});

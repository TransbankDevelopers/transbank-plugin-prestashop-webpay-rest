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
    holdLock,
    isLockHeld,
    closePool
} from "../../../helpers/database.js";
import { expectPaymentError } from "../../../helpers/assertions.js";

/* ════════════════════════════════════════════════════════════════════════════
 *  Duplicate request shows error after max retries exhausted
 *
 *  Simulates the real-world scenario: user duplicates the return request
 *  from Transbank. Both requests are released simultaneously, but an
 *  external lock prevents either from acquiring it. Both requests exhaust all
 *  internal retries (GET_LOCK(5s) with up to 3 retries = 20s) and show the
 *  payment error page.
 *
 *    external lock ── GET_LOCK(token) ─────────── held until test ends
 *
 *    Request A ──┐             ┌── Request B
 *             ▼                ▼
 *         BARRIER (released simultaneously)
 *               ┌─────┴─────┐
 *               ▼           ▼
 *        Both blocked by external lock
 *        Internal retries exhaust → error page
 *        No duplicate orders created
 * ════════════════════════════════════════════════════════════════════════════ */

test.describe("Webpay Plus — Max retries exhausted", () => {
    // GET_LOCK(5s) with up to 3 retries per request = 20s, plus checkout ≈ 15s.
    test(
        "Duplicate request shows error after retries exhaust",
        { timeout: 120_000 },
        async ({ browser, baseURL }) => {
            console.log("═══ START: Max retries exhausted ═══");
            const context = await browser.newContext({
                baseURL,
                ignoreHTTPSErrors: true
            });

            // ── Barrier: intercept return requests ──

            let releaseReturns;
            let returnsReleased = false;
            const returnsCanContinue = new Promise((resolve) => {
                releaseReturns = resolve;
            });
            const commerceReturns = [];

            await context.route(
                (url) => {
                    const s = url.toString();

                    return (
                        s.includes("webpaypluspaymentvalidate") &&
                        s.includes("token_ws=")
                    );
                },
                async (route) => {
                    const requestUrl = route.request().url();
                    commerceReturns.push(requestUrl);
                    console.log(
                        `[BARRIER] Return intercepted #${commerceReturns.length}: ${requestUrl}`
                    );

                    if (!returnsReleased) {
                        await returnsCanContinue;
                    }

                    await route.continue();
                }
            );

            const page = await context.newPage();
            let duplicatePage;
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

                // ── Capture URL, duplicate request, acquire lock, release both ──

                await test.step("Capture return URL, duplicate request and acquire external lock", async () => {
                    await continueToCommerce(page);

                    await expect
                        .poll(() => commerceReturns.length, {
                            message: "Waiting for first intercepted return",
                            timeout: 45_000,
                            intervals: [500]
                        })
                        .toBeGreaterThanOrEqual(1);

                    const commerceReturnUrl = commerceReturns[0];
                    console.log(
                        `[TEST] Return URL captured: ${commerceReturnUrl}`
                    );

                    duplicatePage = await context.newPage();
                    duplicatePage.goto(commerceReturnUrl, {
                        waitUntil: "commit",
                        timeout: 120_000
                    });

                    await expect
                        .poll(() => commerceReturns.length, {
                            message:
                                "Waiting for both returns to be intercepted",
                            timeout: 45_000,
                            intervals: [500]
                        })
                        .toBeGreaterThanOrEqual(2);

                    const token = extractTokenFromUrl(commerceReturnUrl);
                    expect(
                        token,
                        "Could not extract token from the return URL"
                    ).toBeTruthy();
                    externalLock = await holdLock(token);
                    expect(
                        await isLockHeld(token),
                        "External lock must be active before releasing returns"
                    ).toBe(true);
                    console.log(
                        `[TEST] External lock acquired for token: ${token}`
                    );

                    console.log(
                        `[BARRIER] Both returns intercepted (${commerceReturns.length}). Releasing simultaneously...`
                    );
                    returnsReleased = true;
                    releaseReturns();
                });

                // ── Wait for Request B to exhaust internal retries ──
                // GET_LOCK(5s) with up to 3 retries = 20s per request.

                await test.step("Wait for Request B retries to exhaust", async () => {
                    await expect
                        .poll(
                            async () => {
                                try {
                                    const content =
                                        await duplicatePage.content();
                                    return (
                                        content.includes("Reintentar pago") ||
                                        content.includes("errorMessage")
                                    );
                                } catch {
                                    return false;
                                }
                            },
                            {
                                timeout: 60_000,
                                intervals: [1_000],
                                message:
                                    "Waiting for Request B error page to render"
                            }
                        )
                        .toBe(true);
                });

                // ── Verification ──

                await test.step("Verify Request B shows a payment error (not a fatal error)", async () => {
                    await expectPaymentError(duplicatePage);
                    console.log(`[RESULT] Request B (duplicate): url=${duplicatePage.url()}, payment_error=true`);
                });

                await test.step("Verify no duplicate order was created", async () => {
                    const token = extractTokenFromUrl(commerceReturns[0]);
                    const orderCount = await getOrderCountByToken(token);
                    console.log(`[DB] Orders for this token: ${orderCount}`);
                    expect(
                        orderCount,
                        "At most 1 order must exist (no duplicate from Request B)"
                    ).toBeLessThanOrEqual(1);
                });
            } finally {
                await externalLock?.release();
                console.log("═══ END: Max retries exhausted ═══");
                await closePool();
                await context.close();
            }
        }
    );
});

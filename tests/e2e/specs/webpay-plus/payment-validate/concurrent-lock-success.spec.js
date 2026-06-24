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
    closePool
} from "../../../helpers/database.js";
import {
    expectValidResponse
} from "../../../helpers/assertions.js";

/* ════════════════════════════════════════════════════════════════════════════
 *  Lock prevents duplicate orders
 *
 *  Deterministically reproduces the duplicated-request race condition:
 *    1. Completes a normal payment up to the Transbank result screen.
 *    2. Intercepts return requests with a barrier (Promise).
 *    3. Opens a second request with the same return URL.
 *    4. Releases both requests simultaneously.
 *    5. Verifies: no crash, no duplicate orders, valid response on both requests.
 *
 *    Request 1 ──┐              ┌── Request 2
 *             ▼                 ▼
 *         BARRIER (freezes until both are captured)
 *                     │
 *              releaseReturns()
 *               ┌─────┴─────┐
 *               ▼           ▼
 *          PrestaShop + MariaDB lock
 *           → only 1 order created
 * ════════════════════════════════════════════════════════════════════════════ */

test.describe("Webpay Plus — Lock prevents duplicate orders", () => {
    test("Both requests resolve without error when the return URL receives the same token twice", async ({
        browser,
        baseURL
    }) => {
        console.log("═══ START: Lock prevents duplicate orders ═══");
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

            // ── Capture URL and duplicate request ──

            await test.step("Capture return URL and open duplicate request", async () => {
                await continueToCommerce(page);

                await expect
                    .poll(() => commerceReturns.length, {
                        message: "Waiting for first intercepted return",
                        timeout: 45_000,
                        intervals: [500]
                    })
                    .toBeGreaterThanOrEqual(1);

                const commerceReturnUrl = commerceReturns[0];
                console.log(`[TEST] Return URL captured: ${commerceReturnUrl}`);

                duplicatePage = await context.newPage();
                const duplicateNavigation = duplicatePage.goto(
                    commerceReturnUrl,
                    {
                        waitUntil: "commit",
                        timeout: 45_000
                    }
                );

                await expect
                    .poll(() => commerceReturns.length, {
                        message: "Waiting for both returns to be intercepted",
                        timeout: 45_000,
                        intervals: [500]
                    })
                    .toBeGreaterThanOrEqual(2);

                console.log(
                    `[BARRIER] Both returns intercepted (${commerceReturns.length}). Releasing...`
                );
                returnsReleased = true;
                releaseReturns();

                await Promise.all([
                    page.waitForLoadState("load").catch(() => {}),
                    duplicateNavigation?.catch(() => {})
                ]);

                await Promise.all([
                    page.waitForLoadState("networkidle").catch(() => {}),
                    duplicatePage
                        .waitForLoadState("networkidle")
                        .catch(() => {})
                ]);
            });

            // ── Verification ──

            await test.step("Verify that 2 requests arrived with the same token", async () => {
                expect(commerceReturns).toHaveLength(2);

                const tokens = commerceReturns.map(extractTokenFromUrl);

                expect(tokens[0]).toBeTruthy();
                expect(tokens[0]).toBe(tokens[1]);
                console.log(`[RESULT] token_ws: ${tokens[0]}`);
            });

            await test.step("Verify both requests show a valid response", async () => {
                for (const { label, p } of [
                    { label: "Request 1 (original)", p: page },
                    { label: "Request 2 (duplicate)", p: duplicatePage }
                ]) {
                    await expect
                        .poll(
                            async () => {
                                try {
                                    return !p
                                        .url()
                                        .includes("webpaypluspaymentvalidate");
                                } catch {
                                    return false;
                                }
                            },
                            {
                                timeout: 45_000,
                                intervals: [1_000],
                                message: `${label}: waiting for navigation to finish`
                            }
                        )
                        .toBe(true);

                    const { confirmation, paymentErr } =
                        await expectValidResponse(p, label);
                    console.log(
                        `[RESULT] ${label}: url=${p.url()}, confirmation=${confirmation}, payment_error=${paymentErr}`
                    );
                }
            });

            await test.step("Verify exactly 1 order was created in the database", async () => {
                const token = extractTokenFromUrl(commerceReturns[0]);
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
            console.log("═══ END: Lock prevents duplicate orders ═══");
            await closePool();
            await context.close();
        }
    });
});

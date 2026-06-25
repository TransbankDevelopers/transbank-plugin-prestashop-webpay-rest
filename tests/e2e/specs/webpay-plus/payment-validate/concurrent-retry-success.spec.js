import { test, expect } from "@playwright/test";
import {
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
import {
    runCheckoutFlow,
    holdReturnRequest,
    hasNavigatedPastValidation
} from "../../../helpers/concurrent.js";

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

const acquireExternalLock = async (returnHolder) => {
    const returnUrl = returnHolder.getReturnUrl();
    const token = extractTokenFromUrl(returnUrl);
    expect(token, "Could not extract token from the return URL").toBeTruthy();
    console.log(`[INTERCEPTOR] Token captured: ${token}`);

    const externalLock = await holdLock(token);
    expect(
        await isLockHeld(token),
        "External lock must be active before releasing the return"
    ).toBe(true);
    console.log("[INTERCEPTOR] External lock acquired, releasing return request...");

    returnHolder.release();

    return externalLock;
};

const waitForRetryAndReleaseLock = async (externalLock, page) => {
    await new Promise((r) => setTimeout(r, 6_000));

    await externalLock.release();
    console.log(
        "[INTERCEPTOR] External lock released, the internal retry should acquire the lock now"
    );

    await expect
        .poll(() => hasNavigatedPastValidation(page), {
            timeout: 60_000,
            intervals: [1_000],
            message: "Waiting for the retry to finish processing"
        })
        .toBe(true);
};

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
        const returnHolder = await holdReturnRequest(context);
        const page = await context.newPage();
        let externalLock;

        try {
            await test.step("Full checkout up to Transbank", async () =>
                runCheckoutFlow(page));

            await test.step("Capture return URL and acquire external lock", async () => {
                await continueToCommerce(page);

                await expect
                    .poll(returnHolder.isIntercepted, {
                        message: "Waiting for intercepted return",
                        timeout: 45_000,
                        intervals: [500]
                    })
                    .toBe(true);

                externalLock = await acquireExternalLock(returnHolder);
            });

            await test.step("Wait for GET_LOCK timeout and release lock for the retry", async () => {
                await waitForRetryAndReleaseLock(externalLock, page);
                externalLock = null;
            });

            await test.step("Verify the page shows order confirmation", async () => {
                await page.waitForURL(/confirmacion-pedido/, {
                    timeout: 30_000,
                    waitUntil: "load"
                });
                await expectOrderConfirmation(page);
                console.log(
                    `[INTERCEPTOR] Confirmation: url=${page.url()}`
                );
            });

            await test.step("Verify exactly 1 order was created in the database", async () => {
                const token = extractTokenFromUrl(returnHolder.getReturnUrl());
                expect(
                    token,
                    "Could not extract token from the return URL"
                ).toBeTruthy();

                const orderCount = await getOrderCountByToken(token);
                const txStatus = await getTransactionStatus(token);
                console.log(
                    `[INTERCEPTOR] Orders: ${orderCount}, transaction status: ${txStatus}`
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

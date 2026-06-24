import {
    login,
    addProductToCart,
    goThroughCheckoutWithWebpay
} from "./checkout.js";
import { fillCardAndAuthenticate } from "./webpay-form.js";

function isReturnUrl(url) {
    const s = url.toString();

    return s.includes("webpaypluspaymentvalidate") && s.includes("token_ws=");
}

export async function runCheckoutFlow(page) {
    await login(page);
    await addProductToCart(page);
    await goThroughCheckoutWithWebpay(page);
    await fillCardAndAuthenticate(page);
}

export async function holdReturnRequests(context) {
    let releaseReturns;
    let returnsReleased = false;
    const returnsCanContinue = new Promise((resolve) => {
        releaseReturns = resolve;
    });
    const commerceReturns = [];

    await context.route(isReturnUrl, async (route) => {
        const requestUrl = route.request().url();
        commerceReturns.push(requestUrl);
        console.log(
            `[INTERCEPTOR] Return #${commerceReturns.length} intercepted: ${requestUrl}`
        );

        if (!returnsReleased) {
            await returnsCanContinue;
        }

        await route.continue();
    });

    return {
        commerceReturns,
        release() {
            returnsReleased = true;
            releaseReturns();
        }
    };
}

export async function holdReturnRequest(context) {
    let releaseReturn;
    const returnCanContinue = new Promise((resolve) => {
        releaseReturn = resolve;
    });
    let returnUrl = "";
    let intercepted = false;

    await context.route(isReturnUrl, async (route) => {
        returnUrl = route.request().url();
        intercepted = true;
        console.log(`[INTERCEPTOR] Return intercepted: ${returnUrl}`);
        await returnCanContinue;
        await route.continue();
    });

    return {
        getReturnUrl: () => returnUrl,
        isIntercepted: () => intercepted,
        release: () => releaseReturn()
    };
}

export async function hasNavigatedPastValidation(page) {
    try {
        return !page.url().includes("webpaypluspaymentvalidate");
    } catch {
        return false;
    }
}

export async function hasErrorContent(page) {
    try {
        const content = await page.content();

        return (
            content.includes("Reintentar pago") ||
            content.includes("errorMessage")
        );
    } catch {
        return false;
    }
}

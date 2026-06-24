import { expect } from "@playwright/test";

/**
 * Helpers for the Transbank WebpayPlus integration test environment forms.
 *
 * Test card data (Transbank integration):
 *   Card:     4051 8856 0044 6623
 *   Expiry:   any future date
 *   CVV:      123
 *   RUT:      11.111.111-1
 *   Password: 123
 *
 * The selectors below target the Transbank integration test environment at
 * webpay3gint.transbank.cl. Update them if Transbank changes their form layout.
 */

const TEST_CARD = {
    number: "4051885600446623",
    expiry: "12/30",
    cvv: "123",
};

const BANK_SIM = {
    rut: "11.111.111-1",
    password: "123",
};

/* ---------- Selectors ---------- */

const S = {
    tarjetasBtn: "#tarjetas",
    cardNumber: "#card-number",
    cardExpiry: "#card-exp",
    cardCvv: "#card-cvv",
    payButton: "app-tarjeta form button.submit",
    bankRut: "#rutClient",
    bankPassword: "#passwordClient",
    bankAccept: 'input[type="submit"][value="Aceptar"]',
    resultVci: "#vci",
    continueBtn: 'input[type="submit"][value="Continuar"]',
};

/* ---------- Public API ---------- */

/**
 * Fills the Transbank card form and authenticates in the simulated bank.
 * After this function, the page should be on the Transbank result screen
 * showing the VCI status and a "Continuar" button.
 *
 * @param {import('@playwright/test').Page} page — must be on the Transbank card form.
 */
export async function fillCardAndAuthenticate(page) {
    await page
        .locator(S.tarjetasBtn)
        .waitFor({ state: "visible", timeout: 15_000 });
    await page.locator(S.tarjetasBtn).click();

    await page
        .locator(S.cardNumber)
        .waitFor({ state: "visible", timeout: 15_000 });
    await page.locator(S.cardNumber).fill(TEST_CARD.number);

    // Blur triggers card type validation before the form enables the next field.
    await page.locator(S.cardNumber).blur();

    await page
        .locator(S.cardExpiry)
        .waitFor({ state: "visible", timeout: 15_000 });
    await page.locator(S.cardExpiry).fill(TEST_CARD.expiry);

    await page
        .locator(S.cardCvv)
        .waitFor({ state: "visible", timeout: 10_000 });
    await page.locator(S.cardCvv).fill(TEST_CARD.cvv);

    await page.locator(S.payButton).click();

    await page
        .locator(S.bankRut)
        .waitFor({ state: "visible", timeout: 30_000 });
    await page.locator(S.bankRut).fill(BANK_SIM.rut);
    await page.locator(S.bankPassword).fill(BANK_SIM.password);
    await page.locator(S.bankAccept).click();

    await page
        .locator(S.resultVci)
        .waitFor({ state: "visible", timeout: 30_000 });
}

/**
 * Asserts the Transbank result shows an approved VCI and clicks "Continuar"
 * to return to the commerce. Call after fillCardAndAuthenticate.
 *
 * @param {import('@playwright/test').Page} page — must be on the Transbank result screen.
 */
export async function continueToCommerce(page) {
    await expect(page.locator(S.resultVci)).toHaveValue("TSY", {
        timeout: 30_000,
    });
    await page.locator(S.continueBtn).click();
}

/**
 * Extracts the token_ws value from a Webpay return URL.
 *
 * @param {string} url
 * @returns {string | null}
 */
export function extractTokenFromUrl(url) {
    return /token_ws=([^&]+)/.exec(url)?.[1] ?? null;
}

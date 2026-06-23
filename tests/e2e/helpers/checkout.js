/**
 * Helpers for the PrestaShop 9 checkout flow.
 *
 * Selectors target the Hummingbird theme with Spanish locale (PS_LANGUAGE=es).
 * Update selectors in this file if the theme or locale changes.
 */

const CUSTOMER = {
  email: process.env.CUSTOMER_EMAIL || 'test.user@example.com',
  password: process.env.CUSTOMER_PASSWORD || 'Password123!',
};

/**
 * Logs in with the test customer.
 * @param {import('@playwright/test').Page} page
 */
export async function login(page) {
  await page.goto('/iniciar-sesion');
  await page.locator('#field-email').fill(CUSTOMER.email);
  await page.locator('#field-password').fill(CUSTOMER.password);
  await page.locator('#submit-login').click();
  await page.waitForLoadState('domcontentloaded', { timeout: 15_000 });
}

/**
 * Adds the first available product to the cart and navigates to the cart page.
 * @param {import('@playwright/test').Page} page
 */
export async function addProductToCart(page) {
  await page.goto('/');
  await page.locator('.product-miniature a').first().click();
  await page.waitForLoadState('domcontentloaded');

  await page.locator('.add-to-cart').click();
  const modal = page.locator('#blockcart-modal');
  await modal.waitFor({ state: 'visible', timeout: 10_000 });
  await modal.locator('a').filter({ hasText: 'Finalizar compra' }).click();
  await page.waitForLoadState('domcontentloaded');
}

/**
 * Completes the PrestaShop checkout selecting Webpay Plus as payment method.
 * When finished, the browser is on the Transbank payment form.
 * @param {import('@playwright/test').Page} page
 */
export async function goThroughCheckoutWithWebpay(page) {
  // Proceed to checkout
  const proceedBtn = page
    .locator('a[href*="pedido"]')
    .filter({ hasText: 'Finalizar compra' });
  await proceedBtn.first().waitFor({ state: 'visible', timeout: 10_000 });
  await proceedBtn.first().click();
  await page.waitForLoadState('domcontentloaded');

  // Address step — customer has a pre-loaded address, just confirm
  const addressConfirm = page.locator('button[name="confirm-addresses"]');
  await addressConfirm.waitFor({ state: 'visible', timeout: 10_000 });
  await addressConfirm.click();

  // Shipping step — "My carrier" pre-selected, just confirm
  const deliveryConfirm = page.locator('button[name="confirmDeliveryOption"]');
  await deliveryConfirm.waitFor({ state: 'visible', timeout: 10_000 });
  await deliveryConfirm.click();

  // Payment step — select Webpay Plus
  const webpayLabel = page.locator('label').filter({ hasText: /webpay plus/i });
  await webpayLabel.waitFor({ state: 'visible', timeout: 10_000 });
  await webpayLabel.click();

  // Accept terms and conditions
  const termsCheckbox = page.locator('input[name="conditions_to_approve[terms-and-conditions]"]');
  await termsCheckbox.waitFor({ state: 'visible', timeout: 5_000 });
  await termsCheckbox.check();

  // Confirm payment — redirects to Transbank
  await page.locator('#payment-confirmation button[type="submit"]').click();
  await page.waitForURL(/webpay3gint\.transbank\.cl|tbk\.cl/, { timeout: 45_000 });
}

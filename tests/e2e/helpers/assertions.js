/**
 * Reusable assertion helpers for verifying PrestaShop page responses.
 */

import { expect } from '@playwright/test';

export function hasFatalError(content) {
  return content.includes('Fatal error') || content.includes('500 Internal Server Error');
}

export function isOrderConfirmation(url) {
  return url.includes('confirmacion-pedido');
}

export function isPaymentError(content) {
  return content.includes('Reintentar pago') || content.includes('errorMessage');
}

/**
 * Asserts the page reached order confirmation without fatal errors.
 * @param {import('@playwright/test').Page} page
 */
export async function expectOrderConfirmation(page) {
  const url = page.url();
  const content = await page.content();

  expect(hasFatalError(content), `Page has fatal errors — url: ${url}`).toBe(false);
  expect(isOrderConfirmation(url), `Expected confirmacion-pedido in URL — got: ${url}`).toBe(true);
}

/**
 * Asserts the page shows a payment error without fatal errors.
 * @param {import('@playwright/test').Page} page
 */
export async function expectPaymentError(page) {
  const url = page.url();
  const content = await page.content();

  expect(hasFatalError(content), `Page has fatal errors — url: ${url}`).toBe(false);
  expect(isPaymentError(content), `Expected payment error — url: ${url}`).toBe(true);
}

/**
 * Asserts the page shows either order confirmation or a payment error.
 * @param {import('@playwright/test').Page} page
 * @param {string} label
 */
export async function expectValidResponse(page, label) {
  const url = page.url();
  const content = await page.content();
  const fatal = hasFatalError(content);
  const confirmation = isOrderConfirmation(url);
  const paymentErr = isPaymentError(content);

  expect(fatal, `${label} has fatal errors — url: ${url}`).toBe(false);
  expect(confirmation || paymentErr, `${label} must show confirmation or error — url: ${url}`).toBe(true);

  return { confirmation, paymentErr };
}

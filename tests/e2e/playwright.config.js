// @ts-check
import "dotenv/config";
import { defineConfig } from "@playwright/test";

/**
 * Playwright configuration for Webpay Plus E2E tests.
 *
 * Prerequisites:
 *   - PrestaShop running via devcontainer (docker compose up)
 *   - Webpay module installed and configured in integration mode
 *   - Demo data present (default customer: test.user@example.com / Password123!)
 *
 * Environment variables (configured via .env — see .env.example):
 *   BASE_URL          — PrestaShop URL
 *   CUSTOMER_EMAIL    — Test customer email
 *   CUSTOMER_PASSWORD — Test customer password
 */
export default defineConfig({
    testDir: "./specs",
    fullyParallel: false,
    workers: 1,
    timeout: 120_000,
    expect: {
        timeout: 15_000,
    },
    retries: 0,
    reporter: [["html", { open: "never" }], ["list"]],
    use: {
        baseURL: process.env.BASE_URL,
        ignoreHTTPSErrors: true,
        trace: "retain-on-failure",
        screenshot: "only-on-failure",
        video: "retain-on-failure",
        actionTimeout: 15_000,
        navigationTimeout: 45_000,
    },
});

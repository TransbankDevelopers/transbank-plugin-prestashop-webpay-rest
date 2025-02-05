<?php

namespace PrestaShop\Module\WebpayPlus\Config;

/**
 * Interface for module configuration.
 */
interface ModuleConfigInterface
{
    /**
     * Gets the Commerce Code.
     *
     * @return string The Commerce Code.
     */
    public static function getCommerceCode(): ?string;

    /**
     * Gets the API Key.
     *
     * @return string The API Key.
     */
    public static function getApiKey(): ?string;

    /**
     * Gets the environment.
     *
     * @return string The environment.
     */
    public static function getEnvironment(): ?string;

    /**
     * Gets the order state ID after payment.
     *
     * @return string The order state ID after payment.
     */
    public static function getOrderStateIdAfterPayment(): ?string;

    /**
     * Gets the payment active.
     *
     * @return string The payment active.
     */
    public static function getPaymentActive(): ?string;

    /**
     * Sets the Commerce Code.
     *
     * @param string $commerceCode The Commerce Code.
     */
    public static function setCommerceCode(string $commerceCode): void;

    /**
     * Sets the API Key.
     *
     * @param string $apiKey The API Key.
     */
    public static function setApiKey(string $apiKey): void;

    /**
     * Sets the environment.
     *
     * @param string $environment The environment.
     */
    public static function setEnvironment(string $environment): void;

    /**
     * Sets the order state after payment.
     *
     * @param string $orderStateAfterPayment The order state after payment.
     */
    public static function setOrderStateIdAfterPayment(string $orderStateIdAfterPayment): void;

    /**
     * Sets the payment active.
     *
     * @param string $paymentActive The payment active.
     */
    public static function setPaymentActive(string $paymentActive): void;

    /**
     * Loads the default configuration.
     */
    public static function loadDefaultConfig(): void;

    /**
     * Checks if the configuration is OK.
     *
     * @return bool True if the configuration is OK, false otherwise.
     */
    public static function isConfigOk(): bool;

    /**
     * Checks if the payment method is active.
     *
     * @return bool True if the payment method is active, false otherwise.
     */
    public static function isPaymentMethodActive(): bool;
}

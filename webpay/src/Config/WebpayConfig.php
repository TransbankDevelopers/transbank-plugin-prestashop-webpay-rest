<?php

namespace PrestaShop\Module\WebpayPlus\Config;

use Configuration;
use Transbank\Webpay\Options;
use Transbank\Webpay\WebpayPlus;
use Transbank\Plugin\Helpers\TbkConstants;
use PrestaShop\Module\WebpayPlus\Utils\StringUtils;
use PrestaShop\Module\WebpayPlus\Config\ModuleConfigInterface;

class WebpayConfig extends AbstractModuleConfig
{

    /**
     * @inheritDoc
     */
    public static function getApiKey(): ?string
    {
        return self::getConfigValue('WEBPAY_API_KEY_SECRET');
    }

    /**
     * @inheritDoc
     */
    public static function getCommerceCode(): ?string
    {
        return self::getConfigValue('WEBPAY_STOREID');
    }

    /**
     * @inheritDoc
     */
    public static function getEnvironment(): ?string
    {
        return self::getConfigValue('WEBPAY_ENVIRONMENT');
    }

    /**
     * @inheritDoc
     */
    public static function getOrderStateIdAfterPayment(): ?string
    {
        return self::getConfigValue('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
    }

    /**
     * @inheritDoc
     */
    public static function getPaymentActive(): ?string
    {
        return self::getConfigValue('WEBPAY_ACTIVE');
    }

    /**
     * @inheritDoc
     */
    public static function isConfigOk(): bool
    {
        $webpayEnvironment = self::getEnvironment();
        $webpayCommerceCode = self::getCommerceCode();
        $webpayApiKey = self::getApiKey();
        $webpayDefaultOrderStateIdAfterPayment = self::getOrderStateIdAfterPayment();

        return StringUtils::isNotBlankOrNull($webpayEnvironment)
            && StringUtils::isNotBlankOrNull($webpayCommerceCode)
            && StringUtils::isNotBlankOrNull($webpayApiKey)
            && StringUtils::isNotBlankOrNull($webpayDefaultOrderStateIdAfterPayment);
    }

    /**
     * @inheritDoc
     */
    public static function loadDefaultConfig(): void
    {
        self::setPaymentActive(TbkConstants::ACTIVE_MODULE);
        self::setEnvironment(Options::DEFAULT_INTEGRATION_TYPE);
        self::setCommerceCode(WebpayPlus::DEFAULT_COMMERCE_CODE);
        self::setApiKey(WebpayPlus::DEFAULT_API_KEY);
        self::setOrderStateIdAfterPayment(Configuration::get('PS_OS_PREPARATION'));
    }

    /**
     * @inheritDoc
     */
    public static function setApiKey(string $apiKey): void
    {
        self::setConfigValue('WEBPAY_API_KEY_SECRET', $apiKey);
    }

    /**
     * @inheritDoc
     */
    public static function setCommerceCode(string $commerceCode): void
    {
        self::setConfigValue('WEBPAY_STOREID', $commerceCode);
    }

    /**
     * @inheritDoc
     */
    public static function setEnvironment(string $environment): void
    {
        self::setConfigValue('WEBPAY_ENVIRONMENT', $environment);
    }

    /**
     * @inheritDoc
     */
    public static function setOrderStateIdAfterPayment(string $orderStateIdAfterPayment): void
    {
        self::setConfigValue(
            'WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT',
            $orderStateIdAfterPayment
        );
    }

    /**
     * @inheritDoc
     */
    public static function setPaymentActive(string $paymentActive): void
    {
        self::setConfigValue('WEBPAY_ACTIVE', $paymentActive);
    }

    /**
     * @inheritDoc
     */
    public static function isPaymentMethodActive(): bool
    {
        return self::getPaymentActive() === TbkConstants::ACTIVE_MODULE;
    }

    /**
     * @inheritDoc
     */
    public static function initializeConfig(): void
    {
        if (!self::isConfigOk()) {
            self::loadDefaultConfig();
        }
    }
}

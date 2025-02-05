<?php

namespace PrestaShop\Module\WebpayPlus\Config;

use Configuration;
use Transbank\Webpay\Options;
use Transbank\Webpay\WebpayPlus;
use Transbank\Plugin\Helpers\TbkConstants;
use PrestaShop\Module\WebpayPlus\Utils\StringUtils;
use PrestaShop\Module\WebpayPlus\Config\ModuleConfigInterface;

class WebpayConfig implements ModuleConfigInterface
{

    /**
     * @inheritDoc
     */
    public static function getApiKey(): ?string
    {
        $value = Configuration::get('WEBPAY_API_KEY_SECRET');
        return $value ?: null;
    }

    /**
     * @inheritDoc
     */
    public static function getCommerceCode(): ?string
    {
        $value = Configuration::get('WEBPAY_STOREID');
        return $value ?: null;
    }

    /**
     * @inheritDoc
     */
    public static function getEnvironment(): ?string
    {
        $value = Configuration::get('WEBPAY_ENVIRONMENT');
        return $value ?: null;
    }

    /**
     * @inheritDoc
     */
    public static function getOrderStateIdAfterPayment(): ?string
    {
        $value = Configuration::get('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
        return $value ?: null;
    }

    /**
     * @inheritDoc
     */
    public static function getPaymentActive(): ?string
    {
        $value = Configuration::get('WEBPAY_ACTIVE');
        return $value ?: null;
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
        self::setCommerceCoder(WebpayPlus::DEFAULT_COMMERCE_CODE);
        self::setApiKey(WebpayPlus::DEFAULT_API_KEY);
        self::setOrderStateIdAfterPayment(Configuration::get('PS_OS_PREPARATION'));
    }

    /**
     * @inheritDoc
     */
    public static function setApiKey(string $apiKey): void
    {
        Configuration::updateValue('WEBPAY_API_KEY_SECRET', $apiKey);
    }

    /**
     * @inheritDoc
     */
    public static function setCommerceCode(string $commerceCode): void
    {
        Configuration::updateValue('WEBPAY_STOREID', $commerceCode);
    }

    /**
     * @inheritDoc
     */
    public static function setEnvironment(string $environment): void
    {
        Configuration::updateValue('WEBPAY_ENVIRONMENT', $environment);
    }

    /**
     * @inheritDoc
     */
    public static function setOrderStateIdAfterPayment(string $orderStateIdAfterPayment): void
    {
        Configuration::updateValue(
            'WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT',
            $orderStateIdAfterPayment
        );
    }

    /**
     * @inheritDoc
     */
    public static function setPaymentActive(string $paymentActive): void
    {
        Configuration::updateValue('WEBPAY_ACTIVE', $paymentActive);
    }
}

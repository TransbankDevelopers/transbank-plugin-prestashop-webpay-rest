<?php

namespace PrestaShop\Module\WebpayPlus\Config;

use Configuration;
use Transbank\Webpay\Options;
use Transbank\Webpay\Oneclick;
use Transbank\Plugin\Helpers\TbkConstants;
use PrestaShop\Module\WebpayPlus\Utils\StringUtils;

class OneclickConfig extends AbstractModuleConfig
{

    /**
     * @inheritDoc
     */
    public static function getApiKey(): ?string
    {
        return self::getConfigValue('ONECLICK_API_KEY');
    }

    /**
     * @inheritDoc
     */
    public static function getCommerceCode(): ?string
    {
        return self::getConfigValue('ONECLICK_MALL_COMMERCE_CODE');
    }

    /**
     * Gets the child commerce code.
     *
     * @return null|string
     */
    public static function getChildCommerceCode(): ?string
    {
        return self::getConfigValue('ONECLICK_CHILD_COMMERCE_CODE');
    }

    /**
     * @inheritDoc
     */
    public static function getEnvironment(): ?string
    {
        return self::getConfigValue('ONECLICK_ENVIRONMENT');
    }

    /**
     * @inheritDoc
     */
    public static function getOrderStateIdAfterPayment(): ?string
    {
        return self::getConfigValue('ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
    }

    /**
     * @inheritDoc
     */
    public static function getPaymentActive(): ?string
    {
        return self::getConfigValue('ONECLICK_ACTIVE');
    }

    /**
     * @inheritDoc
     */
    public static function isConfigOk(): bool
    {
        $oneclickEnvironment = self::getEnvironment();
        $oneclickMallCommerceCode = self::getCommerceCode();
        $oneclickChildCommerceCode = self::getChildCommerceCode();
        $oneclickApiKey = self::getApiKey();
        $oneclickDefaultOrderStateIdAfterPayment = self::getOrderStateIdAfterPayment();

        return StringUtils::isNotBlankOrNull($oneclickEnvironment)
            && StringUtils::isNotBlankOrNull($oneclickMallCommerceCode)
            && StringUtils::isNotBlankOrNull($oneclickChildCommerceCode)
            && StringUtils::isNotBlankOrNull($oneclickApiKey)
            && StringUtils::isNotBlankOrNull($oneclickDefaultOrderStateIdAfterPayment);
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
    public static function loadDefaultConfig(): void
    {
        self::setPaymentActive(TbkConstants::ACTIVE_MODULE);
        self::setEnvironment(Options::DEFAULT_INTEGRATION_TYPE);
        self::setCommerceCode(Oneclick::DEFAULT_COMMERCE_CODE);
        self::setChildCommerceCode(Oneclick::DEFAULT_CHILD_COMMERCE_CODE_1);
        self::setApiKey(Oneclick::DEFAULT_API_KEY);
        self::setOrderStateIdAfterPayment(Configuration::get('PS_OS_PREPARATION'));
    }

    /**
     * @inheritDoc
     */
    public static function setApiKey(string $apiKey): void
    {
        self::setConfigValue('ONECLICK_API_KEY', $apiKey);
    }

    /**
     * @inheritDoc
     */
    public static function setCommerceCode(string $commerceCode): void
    {
        self::setConfigValue('ONECLICK_MALL_COMMERCE_CODE', $commerceCode);
    }

    /**
     * Sets the child commerce code.
     *
     * @param string $childCommerceCode
     * @return void
     */
    public static function setChildCommerceCode(string $childCommerceCode): void
    {
        self::setConfigValue('ONECLICK_CHILD_COMMERCE_CODE', $childCommerceCode);
    }

    /**
     * @inheritDoc
     */
    public static function setEnvironment(string $environment): void
    {
        self::setConfigValue('ONECLICK_ENVIRONMENT', $environment);
    }

    /**
     * @inheritDoc
     */
    public static function setOrderStateIdAfterPayment(string $orderStateIdAfterPayment): void
    {
        self::setConfigValue(
            'ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT',
            $orderStateIdAfterPayment
        );
    }

    /**
     * @inheritDoc
     */
    public static function setPaymentActive(string $paymentActive): void
    {
        self::setConfigValue('ONECLICK_ACTIVE', $paymentActive);
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

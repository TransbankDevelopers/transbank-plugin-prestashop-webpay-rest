<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\Module\WebpayPlus\Config\WebpayConfig;
use PrestaShop\Module\WebpayPlus\Utils\TransbankSdkWebpay;

class WebpayPlusFactory
{
    /**
     * Create a new instance of TransbankSdkWebpay.
     *
     * @return TransbankSdkWebpay
     */
    public static function create(): TransbankSdkWebpay
    {
        $config = [
            'ENVIRONMENT' => WebpayConfig::getEnvironment(),
            'API_KEY_SECRET' => WebpayConfig::getApiKey(),
            'COMMERCE_CODE' => WebpayConfig::getCommerceCode(),
        ];

        return new TransbankSdkWebpay($config);
    }
}

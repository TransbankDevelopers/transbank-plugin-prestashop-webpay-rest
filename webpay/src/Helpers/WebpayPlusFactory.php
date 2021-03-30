<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use Configuration;
use TransbankSdkWebpay;

class WebpayPlusFactory
{
    public static function create()
    {
        $config = [
            "ENVIRONMENT" => Configuration::get('WEBPAY_ENVIRONMENT'),
            "API_KEY_SECRET" => Configuration::get('WEBPAY_API_KEY_SECRET'),
            "COMMERCE_CODE" => Configuration::get('WEBPAY_STOREID')
        ];
        return new TransbankSdkWebpay($config);
    }
}

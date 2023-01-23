<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use Configuration;
use PrestaShop\Module\WebpayPlus\Utils\TransbankSdkOneclick;

class OneclickFactory
{
    public static function create()
    {
        $config = [
            'ENVIRONMENT'    => Configuration::get('ONECLICK_ENVIRONMENT'),
            'API_KEY_SECRET' => Configuration::get('ONECLICK_API_KEY'),
            'COMMERCE_CODE'  => Configuration::get('ONECLICK_MALL_COMMERCE_CODE'),
            'CHILD_COMMERCE_CODE'  => Configuration::get('ONECLICK_CHILD_COMMERCE_CODE')
        ];

        return new TransbankSdkOneclick($config);
    }
}

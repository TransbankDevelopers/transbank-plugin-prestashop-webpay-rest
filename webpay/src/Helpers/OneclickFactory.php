<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\Module\WebpayPlus\Config\OneclickConfig;
use PrestaShop\Module\WebpayPlus\Utils\TransbankSdkOneclick;

class OneclickFactory
{
    /**
     * Create a new instance of TransbankSdkOneclick
     *
     * @return TransbankSdkOneclick
     */
    public static function create(): TransbankSdkOneclick
    {
        $config = [
            'ENVIRONMENT' => OneclickConfig::getEnvironment(),
            'API_KEY_SECRET' => OneclickConfig::getApiKey(),
            'COMMERCE_CODE' => OneclickConfig::getCommerceCode(),
            'CHILD_COMMERCE_CODE' => OneclickConfig::getChildCommerceCode()
        ];

        return new TransbankSdkOneclick($config);
    }
}

<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use Transbank\Plugin\Helpers\PluginLogger;
use Transbank\Plugin\Model\LogConfig;

class TbkFactory
{
    public static function createLogger()
    {
        $config = new LogConfig(_PS_ROOT_DIR_.'/var/logs/Transbank_webpay');
        return new PluginLogger($config);
    }
}


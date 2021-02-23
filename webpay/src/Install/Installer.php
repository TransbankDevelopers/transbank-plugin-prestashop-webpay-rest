<?php

namespace PrestaShop\Module\WebpayPlus\Install;

use Db;
use TransbankWebpayRestTransaction;

class Installer
{
    public function installWebpayOrdersTable()
    {
        return Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.TransbankWebpayRestTransaction::TABLE_NAME.'` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `cart_id` varchar(60) NOT NULL,
                `order_id` varchar(60),
                `buy_order` varchar(60) NOT NULL,
                `amount` bigint(20) NOT NULL,
                `token` varchar(100) NOT NULL,
                `session_id` varchar(100) NOT NULL,
                `status` tinyint(3) NOT NULL,
                `transbank_response` TEXT,
                `response_code` varchar(10),
                `currency_id` int(10),
                `vci`  varchar(10),
                `created_at` TIMESTAMP NOT NULL  DEFAULT NOW(),
                PRIMARY KEY (id)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;');
    }
}

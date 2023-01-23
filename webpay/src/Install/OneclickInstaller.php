<?php

namespace PrestaShop\Module\WebpayPlus\Install;

use Db;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;

class OneclickInstaller
{
    public function installInscriptionsTable()
    {
        /*
        |--------------------------------------------------------------------------
        | Oneclick inscriptions table
        |--------------------------------------------------------------------------
        */
        return Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.TransbankInscriptions::TABLE_NAME.'` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `token` varchar(100) NOT NULL,
            `username` varchar(100),
            `email` varchar(50) NOT NULL,
            `user_id` bigint(20),
            `tbk_token`  varchar(100) NULL,
            `order_id` bigint(20),
            `pay_after_inscription` TINYINT(1) DEFAULT 0,
            `finished` TINYINT(1) NOT NULL DEFAULT 0,
            `response_code` varchar(50),
            `authorization_code` varchar(50),
            `card_type` varchar(50),
            `card_number` varchar(50),
            `from` varchar(50),
            `status` varchar(50) NOT NULL,
            `environment` varchar(20),
            `commerce_code` varchar(60),
            `transbank_response` LONGTEXT,
            `created_at` TIMESTAMP NOT NULL  DEFAULT NOW(),
            PRIMARY KEY (id)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;');
    }
}

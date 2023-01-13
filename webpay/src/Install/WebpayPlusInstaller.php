<?php

namespace PrestaShop\Module\WebpayPlus\Install;

use Db;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

class WebpayPlusInstaller
{
    public function installWebpayOrdersTable()
    {
        /*
        |--------------------------------------------------------------------------
        | Webpay Transactions Table
        |--------------------------------------------------------------------------
        */
        $result = Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.TransbankWebpayRestTransaction::TABLE_NAME.'` (
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

                `commerce_code` varchar(60),
                `child_commerce_code` varchar(60),
                `product` varchar(30),
                `environment` varchar(20),
                PRIMARY KEY (id)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;');
        //actualizamos la tabla para darle soporte a Oneclick
        $this->updateDatabase(_DB_PREFIX_.TransbankWebpayRestTransaction::TABLE_NAME, array('commerce_code', 'child_commerce_code', 'transbank_response', 'product', 'environment'));
        return $result;
    }

    private function updateDatabase($tableName, $newColumns){
        $columns = Db::getInstance()->ExecuteS('DESCRIBE `'.$tableName.'`');
        foreach($newColumns as $newColumn){
            $found = false;
            foreach($columns as $col){
                if($col['Field'] == $newColumn){
                    $found = true;
                    break;
                }
            }
            if(!$found){
                Db::getInstance()->execute('ALTER TABLE `'.$tableName.'` ADD `'.$newColumn.'` text DEFAULT NULL');
            }
        }
    }
}

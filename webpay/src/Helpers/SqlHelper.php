<?php
namespace PrestaShop\Module\WebpayPlus\Helpers;

use Db;
use Exception;

class SqlHelper
{
    public static function executeSql($sql){
        try {
            return Db::getInstance()->executeS($sql);
        }
        catch(Exception $e) {
            return null;
        }
    }

    public static function getRow($sql){
        try {
            return Db::getInstance()->getRow($sql);
        }
        catch(Exception $e) {
            return null;
        }
    }

    public static function getValue($sql){
        try {
            return Db::getInstance()->getValue($sql, $use_cache = true);
        }
        catch(Exception $e) {
            return null;
        }
    }
    
}

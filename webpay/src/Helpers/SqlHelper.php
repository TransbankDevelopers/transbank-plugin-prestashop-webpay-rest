<?php
namespace PrestaShop\Module\WebpayPlus\Helpers;

use Db;
use Transbank\Plugin\Exceptions\EcommerceException;

class SqlHelper
{
    public static function executeSql($sql){
        $result = Db::getInstance()->executeS($sql);
        if ($result !== false){
            return $result;
        }
        throw new EcommerceException("Ocurrio un error ejecutando executeSql");
    }

    public static function getRow($sql){
        $result = Db::getInstance()->getRow($sql);
        if ($result !== false){
            return $result;
        }
        throw new EcommerceException("Ocurrio un error ejecutando getRow");
    }

    public static function getValue($sql){
        $result = Db::getInstance()->getValue($sql);
        if ($result !== false){
            return $result;
        }
        throw new EcommerceException("Ocurrio un error ejecutando getValue");
    }
    
}

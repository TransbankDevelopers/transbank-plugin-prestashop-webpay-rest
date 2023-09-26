<?php

namespace PrestaShop\Module\WebpayPlus\Repository;

use Transbank\Plugin\Repository\IUtilRepository;
use Db;
use Exception;

class UtilRepository implements IUtilRepository {
    
    public function executeSql($sql){
        return Db::getInstance()->executeS($sql);
    }

    public function getRow($sql){
        return Db::getInstance()->getRow($sql);
    }

    public function getValue($sql){
        return Db::getInstance()->getValue($sql, true);
    }

    public function sanitizeValue($value){
        return pSQL($value);
    }

}

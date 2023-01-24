<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

class StringUtils
{
    public static function isNotBlankOrNull($str){
        if (!isset($str)){
            return false;
        }
        else if (empty(trim($str))){
            return false;
        }
        return true;
    }

}

<?php

namespace Transbank\Plugin\Helpers;

class StringUtils
{
    public static function isNotBlankOrNull($str){
        if (!isset($str) || empty(trim($str))){
            return false;
        }
        return true;
    }

    public static function hasLength($str, $length){
        if (strlen($str) == $length){
            return true;
        }
        return false;
    }

    public static function snakeToCamel($str) {
        return lcfirst(str_replace('_', '', ucwords($str, '_')));
    }

    public static function camelToSnake($str) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
    }
}

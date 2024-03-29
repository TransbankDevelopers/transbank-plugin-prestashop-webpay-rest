<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

class Utils
{
    public static function getFullVersionPrestashop()
    {
        return _PS_VERSION_;
    }

    public static function getBaseVersionPrestashop()
    {
        return substr(_PS_VERSION_, 0, 3);
    }

    public static function isPrestashop_1_6()
    {
        return self::getBaseVersionPrestashop() == '1.6' ? true : false;
    }

    public static function isPrestashop_1_7()
    {
        return self::getBaseVersionPrestashop() == '1.7' ? true : false;
    }

    public static function isPrestashopEqualOrGreater_1_7_1()
    {
        return version_compare(_PS_VERSION_, '1.7.1', '>=');
    }
}

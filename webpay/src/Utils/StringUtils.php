<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

class StringUtils
{
    /**
     * Check if a string is not blank or null.
     *
     * @param string $str The string to check.
     *
     * @return bool True if the string is not blank or null, false otherwise.
     */
    public static function isNotBlankOrNull($str): bool
    {
        return isset($str) && !empty(trim($str));
    }

}

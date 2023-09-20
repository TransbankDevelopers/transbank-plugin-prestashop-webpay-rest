<?php

namespace Transbank\Plugin\Helpers;

use Exception;
use Transbank\Plugin\Helpers\StringUtils;

class ValidateUtil
{
    public static function isNotBlankOrNull($str, $errorMessage){
        if (!StringUtils::isNotBlankOrNull($str)){
            throw new Exception($errorMessage);
        }
    }

}

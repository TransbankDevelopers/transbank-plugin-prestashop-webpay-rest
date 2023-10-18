<?php

namespace Transbank\Plugin\Exceptions;

use Transbank\Plugin\Helpers\ExceptionConstans;

class EcommerceException extends \Exception
{
    public function __construct($message, \Exception $previous = null) {
        parent::__construct($message, ExceptionConstans::DEFAULT_CODE, $previous);
    }
}

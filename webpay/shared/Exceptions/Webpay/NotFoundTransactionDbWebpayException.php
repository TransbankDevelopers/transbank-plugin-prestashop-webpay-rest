<?php

namespace Transbank\Plugin\Exceptions\Webpay;

class NotFoundTransactionDbWebpayException extends \Exception
{
    private $orderId;

    public function __construct($message, $orderId, $code = 0, \Exception $previous = null) {
        $this->orderId = $orderId;
        parent::__construct($message, $code, $previous);
    }

    public function getOrderId() {
        return $this->orderId;
    }

}
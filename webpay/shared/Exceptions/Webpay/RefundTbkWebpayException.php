<?php

namespace Transbank\Plugin\Exceptions\Webpay;

class RefundTbkWebpayException extends \Exception
{
    private $token;
    private $transaction;

    public function __construct($message, $token, $transaction, $code = 0, \Exception $previous = null) {
        $this->token = $token;
        $this->transaction = $transaction;
        parent::__construct($message, $code, $previous);
    }

    public function getToken() {
        return $this->token;
    }

    public function getTransaction() {
        return $this->transaction;
    }
}

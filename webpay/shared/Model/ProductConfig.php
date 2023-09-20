<?php

namespace Transbank\Plugin\Model;

abstract class ProductConfig {
    public $active = false;
    public $production = false;
    public $apikey = null;
    public $commerceCode = null;
    public $orderStatusAfterPayment = null;

    /**
     * @return bool
    */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
    */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return bool
    */
    public function isProduction()
    {
        return $this->production;
    }

    /**
     * @param bool $production
    */
    public function setProduction($production)
    {
        $this->production = $production;
    }

    public function getApikey()
    {
        return $this->apikey;
    }

    public function setApikey($apikey)
    {
        $this->apikey = $apikey;
    }

    public function getCommerceCode()
    {
        return $this->commerceCode;
    }

    public function setCommerceCode($commerceCode)
    {
        $this->commerceCode = $commerceCode;
    }

    public function getOrderStatusAfterPayment()
    {
        return $this->orderStatusAfterPayment;
    }

    public function setOrderStatusAfterPayment($orderStatusAfterPayment)
    {
        $this->orderStatusAfterPayment = $orderStatusAfterPayment;
    }
}

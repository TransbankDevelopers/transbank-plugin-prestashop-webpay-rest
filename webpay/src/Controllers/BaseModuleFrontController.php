<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_.'webpay/libwebpay/LogHandler.php';

/**
 * Class BaseModuleFrontController.
 */
class BaseModuleFrontController extends ModuleFrontController
{
    protected function getCustomer($customerId){
        return new Customer($customerId);
    }

    protected function getCart($cartId){
        return new Cart($cartId);
    }

    protected function logError($msg){
        (new LogHandler())->logError($msg);
    }

    protected function logInfo($msg){
        (new LogHandler())->logInfo($msg);
    }

    protected function cartToLog($cart){
        $this->logInfo('-----------------------------------------------------');
        $this->logInfo('------------CART-------------------------------------');
        $this->logInfo(json_encode($cart));
        $prods = $cart->getProducts();
        foreach ($prods as $prod){
            $this->logInfo('------------PRODUCT---------------------------------');
            $this->logInfo(json_encode($prod));
        }
        $this->logInfo('-----------------------------------------------------');
    }

    protected function getCartFromContext(){
        return Context::getContext()->cart;
    }

    protected function getOrderTotalRound($cart){
        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        return round($amount); // for CLP it should alway be a int
    }

    protected function getOrderTotal($cart){
        return $cart->getOrderTotal(true, Cart::BOTH);
    }

    protected function getDebugActive(){
        return Configuration::get('DEBUG_ACTIVE');
    }
}
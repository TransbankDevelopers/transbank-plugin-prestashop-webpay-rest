<?php

namespace PrestaShop\Module\WebpayPlus\Controller;

use ModuleFrontController;
use Customer;
use Cart;
use Context;
use Tools;
use Configuration;

class BaseModuleFrontController extends ModuleFrontController
{
    public $display_column_right = false;
    public $display_footer = false;
    public $display_column_left = false;
    public $ssl = true;
    protected $logger = null;

    protected function getCustomer($customerId){
        return new Customer($customerId);
    }

    protected function getCart($cartId){
        return new Cart($cartId);
    }

    protected function getUserId(){
        if ($this->context->customer->isLogged()) {
            return $this->context->customer->id;
        }
        return null;
    }

    protected function getUserEmail(){
        if ($this->context->customer->isLogged()) {
            return $this->context->customer->email;
        }
        return null;
    }

    protected function logError($msg){
        $this->logger->logError($msg);
    }

    protected function logInfo($msg){
        $this->logger->logInfo($msg);
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

    protected function customerToLog($customer){
        $this->logInfo('-----------------------------------------------------');
        $this->logInfo('------------CUSTOMER-------------------------------------');
        $this->logInfo(json_encode($customer));
        $this->logInfo('-----------------------------------------------------');
    }

    protected function getCartFromContext(){
        return Context::getContext()->cart;
    }

    protected function getCustomerFromContext(){
        return new Customer($this->getCartFromContext()->id_customer);
    }

    protected function getOrderTotalRound($cart){
        return round($this->getOrderTotal($cart));
    }

    protected function getOrderTotal($cart){
        if (!isset($cart))
            $cart = $this->getCartFromContext();
        return $cart->getOrderTotal(true, Cart::BOTH);// for CLP it should alway be a int
    }

    protected function getDebugActive(){
        return Configuration::get('DEBUG_ACTIVE');
    }


    protected function setPaymentErrorPage($errorMessage)
    {
        $date_tx_hora = date('H:i:s');
        $date_tx_fecha = date('d-m-Y');
        $this->logError($errorMessage);
        Context::getContext()->smarty->assign([
            'WEBPAY_RESULT_CODE'          => 500,
            'WEBPAY_RESULT_DESC'          => $errorMessage,
            'WEBPAY_VOUCHER_ORDENCOMPRA'  => 0,
            'WEBPAY_VOUCHER_TXDATE_HORA'  => $date_tx_hora,
            'WEBPAY_VOUCHER_TXDATE_FECHA' => $date_tx_fecha,
        ]);
        $this->setTemplate('module:webpay/views/templates/front/payment_error.tpl');
    }

    protected function throwErrorRedirect($message, $redirectTo = 'index.php?controller=order&step=3')
    {
        $this->logError($message);
        Tools::redirect($redirectTo);
    }

    protected function loadCartFromCookie(){
        $this->context->cart = new Cart($this->context->cookie->webpay_cart_id);
        return $this->context->cart;
    }

    protected function getUserIdFromCookie(){
        return $this->context->cookie->webpay_customer_id;
    }

    protected function getUserEmailFromCookie(){
        return $this->context->cookie->webpay_email;
    }

    protected function generateRandomId($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
}



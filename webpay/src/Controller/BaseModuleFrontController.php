<?php

namespace PrestaShop\Module\WebpayPlus\Controller;

use ModuleFrontController;
use Customer;
use Cart;
use Context;
use Tools;
use Configuration;
use PrestaShop\Module\WebpayPlus\Utils\Utils;
use PrestaShop\Module\WebpayPlus\Utils\LogHandler;

class BaseModuleFrontController extends ModuleFrontController
{
    public $display_column_right = false;
    public $display_footer = false;
    public $display_column_left = false;
    public $ssl = true;

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


    /**
     * @param array $result
     */
    protected function setPaymentErrorPage($error, $detailError = null)
    {
        $date_tx_hora = date('H:i:s');
        $date_tx_fecha = date('d-m-Y');
        $msg = $error.(isset($detailError) ? ' ('.$detailError.')' : '');
        $this->logError($msg);
        Context::getContext()->smarty->assign([
            'WEBPAY_RESULT_CODE'          => 500,
            'WEBPAY_RESULT_DESC'          => $msg,
            'WEBPAY_VOUCHER_ORDENCOMPRA'  => 0,
            'WEBPAY_VOUCHER_TXDATE_HORA'  => $date_tx_hora,
            'WEBPAY_VOUCHER_TXDATE_FECHA' => $date_tx_fecha,
        ]);
        if (Utils::isPrestashop_1_6()) {
            $this->setTemplate('payment_error_1.6.tpl');
        } else {
            $this->setTemplate('module:webpay/views/templates/front/payment_error.tpl');
        }
    }

    protected function throwErrorRedirect($message, $redirectTo = 'index.php?controller=order&step=3')
    {
        $this->logError($message);
        Tools::redirect($redirectTo);
        exit;
    }

    protected function loadCartFromCookie(){
        $this->$context->cart = new Cart($this->context->cookie->webpay_cart_id);
        return $this->$context->cart;
    }

    protected function getUserIdFromCookie(){
        return $this->context->cookie->webpay_customer_id;
    }

    protected function getUserEmailFromCookie(){
        return $this->context->cookie->webpay_email;
    }
}



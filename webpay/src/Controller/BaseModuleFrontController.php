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
    protected $logger;

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

    /**
     * @param array $result
     */
    protected function setPaymentErrorPage($error, $detailError = null)
    {
        $msg = $error.(isset($detailError) ? ' ('.$detailError.')' : '');
        $this->logError($msg);
        Context::getContext()->smarty->assign([
            "data" => [
                ["label" => "Respuesta de la Transacción", "value" => $msg],
                ["label" => "Código de la Transacción", "value" => 500],
                ["label" => "Orden de Compra", "value" => 0],
                ["label" => "Fecha de Transaccion", "value" => date('d-m-Y')],
                ["label" => "Hora de Transaccion", "value" => date('H:i:s')]
            ]
        ]);
        $this->setTemplate('module:webpay/views/templates/front/payment_error.tpl');
    }

    protected function throwErrorRedirect($message, $redirectTo = 'index.php?controller=order&step=3')
    {
        $this->logError($message);
        Tools::redirect($redirectTo);
        exit;
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

    protected function getCurrentStoreId(){
        return Context::getContext()->shop->id;
    }
}



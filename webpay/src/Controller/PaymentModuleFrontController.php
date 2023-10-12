<?php

namespace PrestaShop\Module\WebpayPlus\Controller;

use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use Cart;
use Module;
use Validate;
use Tools;
use Exception;

class PaymentModuleFrontController extends BaseModuleFrontController
{
    protected function saveOrderPayment($order, $cart, $cardNumber){
        $payment = $order->getOrderPaymentCollection();
        if (isset($payment[0])) {
            $payment[0]->transaction_id = $cart->id;
            $payment[0]->card_number = '**** **** **** '.$cardNumber;
            $payment[0]->card_brand = '';
            $payment[0]->card_expiration = '';
            $payment[0]->card_holder = '';
            $payment[0]->save();
        }
    }

    /**
     * @param Cart $cart
     */
    protected function redirectToPaidSuccessPaymentPage(Cart $cart)
    {
        $customer = $this->getCustomer($cart->id_customer);
        $dataUrl = 'id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key;
        return Tools::redirect('index.php?controller=order-confirmation&'.$dataUrl);
    }

    protected function validate($cart, $customer){
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'webpay') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            $errorMessage = $this->module->getTranslator()->trans('This payment method is not available.', [], 'Modules.Webpay');
            throw new Exception($errorMessage);
        }
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
    }
    
}



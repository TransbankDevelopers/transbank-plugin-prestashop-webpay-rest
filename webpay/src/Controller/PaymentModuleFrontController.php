<?php

namespace PrestaShop\Module\WebpayPlus\Controller;

use ModuleFrontController;

class PaymentModuleFrontController extends ModuleFrontController
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
    
}



<?php

use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

/**
 * Class WebPayOneclickPaymentValidateModuleFrontController.
 */
class WebPayOneclickPaymentValidateModuleFrontController extends BaseModuleFrontController
{
    protected $responseData = [];

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {

        $cart = $this->context->cart;
        //$order = new Order($this->module->currentOrder);
        //$orderId = $order->id;;
        $customer = new Customer($cart->id_customer);
        $moduleId = $this->module->id;
        $this->validate($cart, $customer);

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        $data = $_REQUEST;
        $webpayTransaction = $this->authorizeTransaction($data['inscriptionId'], $cart, $total);

        $OKStatus = Configuration::get('ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
        if ($OKStatus === '0') {
            $OKStatus = Configuration::get('PS_OS_PREPARATION');
        }

        $this->module->validateOrder(
            (int) $cart->id,
            $OKStatus,
            $total,
            'Webpay Plus Oneclick',
            'Pago exitoso',
            []/* variables */,
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        $order = new Order($this->module->currentOrder);
        $payment = $order->getOrderPaymentCollection();
        if (isset($payment[0])) {
            $payment[0]->transaction_id = $cart->id;
            $payment[0]->card_number = '**** **** **** ';//.$result->cardDetail['card_number'];
            $payment[0]->card_brand = '';
            $payment[0]->card_expiration = '';
            $payment[0]->card_holder = '';
            $payment[0]->save();
        }

        //$webpayTransaction->response_code = $result->responseCode;
        $webpayTransaction->order_id = $order->id;
        //$webpayTransaction->vci = $result->vci;
        $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_APPROVED;
        $webpayTransaction->save();
        return $this->redirectToPaidSuccessPaymentPage($cart);
        //Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $moduleId . '&id_order=' . $orderId . '&key=' . $customer->secure_key);
    }

    /*
    private function redirectToSuccessPage(Cart $cart)
    {
        $customer = new Customer($cart->id_customer);
        $dataUrl = 'id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key;
        return Tools::redirect('index.php?controller=order-confirmation&'.$dataUrl);
    }*/

    private function validate($cart, $customer){
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
            exit($this->module->getTranslator()->trans('This payment method is not available.', [], 'Modules.Wirepayment.Shop'));
        }
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
    }

    private function authorizeTransaction($inscriptionId, $cart, $amount){
        //recuperamos la inscripcion de la tarjeta por el id
        $ins = new TransbankInscriptions($inscriptionId);
        //creamos un objeto Oneclick y autorizamos la transacción con la tarjeta recuperada
        $webpay = OneclickFactory::create();
        $resp = $webpay->authorizeTransaction($ins->username, $ins->tbk_token, (int) $cart->id, $amount);

        if (!is_array($resp) && $resp->isApproved()){
            $transaction = new TransbankWebpayRestTransaction();
            $transaction->cart_id = (int)$cart->id;
            $transaction->session_id = uniqid();
            $transaction->token = $resp->getBuyOrder();
            $transaction->buy_order = $resp->getBuyOrder();
            $transaction->child_buy_order = $resp->getDetails()[0]->getBuyOrder();
            $transaction->commerce_code =  $webpay->getCommerceCode();
            $transaction->child_commerce_code = $webpay->getChildCommerceCode();
            $transaction->amount = $amount;
            $transaction->environment = 'TEST';//$webpay->getEnviroment();
            $transaction->product = TransbankWebpayRestTransaction::PRODUCT_WEBPAY_ONECLICK;
            //$transaction->status = TransbankWebpayRestTransaction::STATUS_APPROVED;
            $transaction->status = TransbankWebpayRestTransaction::STATUS_FAILED; // Guardar como fallida por si algo falla más adelante
            $transaction->transbank_response = json_encode($resp);
            //$transaction->order_id = $orderId;

            $transaction->created_at = date('Y-m-d H:i:s');
            $transaction->shop_id = (int) Context::getContext()->shop->id;
            $transaction->currency_id = (int) $cart->id_currency;

            $saved = $transaction->save();
            if (!$saved) {
                $this->logError('Could not create record on webpay_transactions database');
                return $this->setPaymentErrorPage('No se pudo crear la transacción en la tabla webpay_transactions');
            }
            return $transaction;
        } else {
            exit($resp['error'].' : '.$resp['detail']);
        }
    }

    /**
     * @param Cart $cart
     */
    protected function redirectToPaidSuccessPaymentPage(Cart $cart)
    {
        $customer = new Customer($cart->id_customer);
        $dataUrl = 'id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key;

        return Tools::redirect('index.php?controller=order-confirmation&'.$dataUrl);
    }


    


}

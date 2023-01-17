<?php

use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use PrestaShop\Module\WebpayPlus\Controller\PaymentModuleFrontController;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

/**
 * Class WebPayOneclickPaymentValidateModuleFrontController.
 */
class WebPayOneclickPaymentValidateModuleFrontController extends PaymentModuleFrontController
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
        $OKStatus = $this->getOkStatus();

        if($this->getDebugActive()==1){
            $this->logInfo('C.4. Procesando pago - antes de validateOrder');
            $this->logInfo('amount : '.$total.', cartId: '.$cart->id.', OKStatus: '.$OKStatus.', currencyId: '.$currency->id.', customer_secure_key: '.$customer->secure_key);
        }

        $this->module->validateOrder(
            (int) $cart->id,
            $OKStatus,
            $total,
            'Webpay Oneclick',
            'Pago exitoso',
            []/* variables */,
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        $order = new Order($this->module->currentOrder);
        $this->saveOrderPayment($order, $cart, $webpayTransaction->card_number);

        /* finalmente marcamos la transaccion como correcta */ 
        $webpayTransaction->order_id = $order->id;
        $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_APPROVED;
        $webpayTransaction->save();


        return $this->redirectToPaidSuccessPaymentPage($cart);
        //Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $moduleId . '&id_order=' . $orderId . '&key=' . $customer->secure_key);
    }

    private function getOkStatus(){
        $OKStatus = Configuration::get('ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
        if ($OKStatus === '0') {
            $OKStatus = Configuration::get('PS_OS_PREPARATION');
        }
        return $OKStatus;
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
        /* 1. Creamos la transaccion antes de intentar autorizarla con tbk */
        $randomNumber = uniqid();
        $parentBuyOrder = 'ps:parent:'.$randomNumber;
        $childBuyOrder = 'ps:child:'.$randomNumber;
        $ins = new TransbankInscriptions($inscriptionId); //recuperamos la inscripcion de la tarjeta por el id
        $webpay = OneclickFactory::create(); //creamos un objeto Oneclick y autorizamos la transacción con la tarjeta recuperada
        
        /*Creamos la transaccion antes de autorizarla */
        $transaction = new TransbankWebpayRestTransaction();
        $transaction->cart_id = (int)$cart->id;
        $transaction->session_id = 'ps:sessionId:'.$randomNumber;
        $transaction->created_at = date('Y-m-d H:i:s');
        //$transaction->shop_id = (int) Context::getContext()->shop->id;
        $transaction->currency_id = (int) $cart->id_currency;

        $transaction->environment = $webpay->getEnviroment();
        $transaction->product = TransbankWebpayRestTransaction::PRODUCT_WEBPAY_ONECLICK;
        $transaction->commerce_code =  $webpay->getCommerceCode();
        $transaction->child_commerce_code = $webpay->getChildCommerceCode();
        $transaction->amount = $amount;
        $transaction->token = $childBuyOrder;
        $transaction->buy_order = $parentBuyOrder;
        $transaction->status = TransbankWebpayRestTransaction::STATUS_FAILED; // Guardar como fallida por si algo falla más adelante
        $saved = $transaction->save();

        if (!$saved) {
            $error = 'No se pudo guardar en base de datos el resultado de la transacción: '.SqlHelper::getMsgError();
            $this->logError($error);
            return $this->setPaymentErrorPage($error);
        }

        /* 2. Autorizamos la transacción */
        $resp = $webpay->authorizeTransaction($ins->username, $ins->tbk_token, $parentBuyOrder, $childBuyOrder, $amount);
        /* Si arroja un error */
        if (is_array($resp) && isset($resp['error'])) {
            /* 2.1. Si arroja un error y guardamos el error*/
            $error = 'Error: '.$resp['detail'];
            $transaction->transbank_response = json_encode($resp);
            $saved = $transaction->save();
            $this->logError($error);
            $this->throwErrorRedirect($error);
        }

        /* 2.2 no arrojo error pero la operación podria haber sido rechazada */
        $transaction->response_code = $resp->getDetails()[0]->responseCode;
        $transaction->card_number = $resp->getDetails()[0]->cardNumber;
        $transaction->transbank_response = json_encode($resp);
        $saved = $transaction->save();

        /* Si no se aprueba la orden */
        if (!$resp->isApproved()){    
            $error = 'Pago rechazado';
            $this->logError($error);
            $this->throwErrorRedirect($error);
        }
        return $transaction;
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

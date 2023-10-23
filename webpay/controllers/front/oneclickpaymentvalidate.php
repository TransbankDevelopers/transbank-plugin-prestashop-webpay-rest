<?php

use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use PrestaShop\Module\WebpayPlus\Controller\PaymentModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithCommon;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclick;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;

/**
 * Class WebPayOneclickPaymentValidateModuleFrontController.
 */
class WebPayOneclickPaymentValidateModuleFrontController extends PaymentModuleFrontController
{
    use InteractsWithCommon;
    use InteractsWithOneclick;
    
    protected $responseData = [];

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $this->logger = TbkFactory::createLogger();
        if($this->isDebugActive()){
            $this->logInfo("B.1. Iniciando medio de pago Oneclick");
        }
        
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $this->validate($cart, $customer);

        $currency = $this->context->currency;
        $total = $this->getOrderTotalOriginal($cart);
        $totalRound = $this->getOrderTotalRound($cart);
        $data = $_REQUEST;
        $inscriptionId = $data['inscriptionId'];
        $webpayTransaction = $this->authorizeTransaction($inscriptionId, $cart, $totalRound);
        $OKStatus = $this->getOneclickOkStatus();

        if($this->isDebugActive()){
            $this->logInfo('C.4. Procesando pago - antes de validateOrder');
            $this->logInfo("amount : {$total}, cartId: {$cart->id}, OKStatus: {$OKStatus},
                currencyId: {$currency->id}, customer_secure_key: {$customer->secure_key}");
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

        if($this->isDebugActive()){
            $this->logInfo("B.7. Procesando pago despues de validateOrder => inscriptionId: {$inscriptionId}");
            $this->logInfo(json_encode($webpayTransaction));
        }

        $order = new Order($this->module->currentOrder);
        $this->saveOrderPayment($order, $cart, $webpayTransaction->card_number);

        /* finalmente marcamos la transaccion como correcta */ 
        $webpayTransaction->order_id = $order->id;
        $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_APPROVED;
        $webpayTransaction->save();

        $this->logInfo("***** TODO OK *****");
        $this->logInfo("TRANSACCION VALIDADA POR PRESTASHOP Y POR TBK EN ESTADO STATUS_APPROVED => INSCRIPTION_ID:
            {$inscriptionId}");
        $this->logInfo(json_encode($webpayTransaction));
        return $this->redirectToPaidSuccessPaymentPage($cart);
    }

    private function authorizeTransaction($inscriptionId, $cart, $amount){
        /* 1. Creamos la transaccion antes de intentar autorizarla con tbk */
        $orderId = $cart->id;
        $randomNumber = $this->generateRandomId();
        $parentBuyOrder = "ps:{$randomNumber}:{$orderId}";
        $childBuyOrder = "cb:{$randomNumber}:{$orderId}";
        if($this->isDebugActive()){
            $this->logInfo("B.2. Antes de obtener inscripción de la BD => inscriptionId:
                {$inscriptionId}, cartId: {$cart->id}, amount: {$amount}");
        }
        $ins = new TransbankInscriptions($inscriptionId); //recuperamos la inscripcion de la tarjeta por el id
        if($this->isDebugActive()){
            $this->logInfo("B.2. Despues de obtener inscripción de la BD => inscriptionId: {$inscriptionId}");
            $this->logInfo(json_encode($ins));
        }
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

        if($this->isDebugActive()){
            $this->logInfo("B.3. Preparando datos antes de crear la transacción en BD =>
                inscriptionId: {$inscriptionId}");
            $this->logInfo(json_encode($transaction));
        }
        $saved = $transaction->save();

        if (!$saved) {
            $this->logError("B.4. No se pudo crear la transacción en BD => inscriptionId: {$inscriptionId}");
            $this->logError(json_encode($transaction));
            $this->setPaymentErrorPage('No se pudo guardar en base de datos el resultado de la transacción ');
        }

        if($this->isDebugActive()){
            $this->logInfo("B.4. Preparando datos antes de autorizar la transacción en Transbank");
            $this->logInfo("username: {$ins->username}, tbkToken: {$ins->tbk_token}, parentBuyOrder:
                {$parentBuyOrder}, childBuyOrder: {$childBuyOrder}, amount: {$amount}");
        }
        /* 2. Autorizamos la transacción */
        try {
            $resp = $webpay->authorize($ins->username, $ins->tbk_token, $parentBuyOrder, $childBuyOrder, $amount);
        } catch (\Exception $e) {
            $this->throwErrorRedirect($e->getMessage());
        }

        $this->logInfo("B.5. Transacción con autorización en Transbank => username: {$ins->username}, tbkToken:
            {$ins->tbk_token}, parentBuyOrder: {$parentBuyOrder}, childBuyOrder: {$childBuyOrder}, amount: {$amount}");
        $this->logInfo(json_encode($resp));
        if (!is_array($resp) && $resp->isApproved()){
            $this->logInfo("***** AUTORIZADO POR TBK OK *****");
            $this->logInfo("TRANSACCION VALIDADA POR TBK => username: {$ins->username}, tbkToken: {$ins->tbk_token}
                , parentBuyOrder: {$parentBuyOrder} childBuyOrder: {$childBuyOrder}, amount: {$amount}");
            $this->logInfo("SI NO SE ENCUENTRA VALIDACION POR PRESTASHOP DEBE ANULARSE");
        }
        /* Si arroja un error */
        if (is_array($resp) && isset($resp['error'])) {
            $this->logError("B.6. Transacción con autorización con error => parentBuyOrder:
                {$parentBuyOrder}, childBuyOrder: {$childBuyOrder}");
            $this->logError(json_encode($resp));
            /* 2.1. Si arroja un error y guardamos el error*/
            $transaction->transbank_response = json_encode($resp);
            $saved = $transaction->save();
            $this->throwErrorRedirect('Error: '.$resp['detail']);
        }

        /* 2.2 no arrojo error pero la operación podria haber sido rechazada */
        $transaction->response_code = $resp->getDetails()[0]->responseCode;
        $transaction->card_number = $resp->cardNumber;
        $transaction->transbank_response = json_encode($resp);
        $saved = $transaction->save();

        /* Si no se aprueba la orden */
        if (!$resp->isApproved()){
            $this->logError("B.6. Transacción con autorización rechazada => parentBuyOrder:
                {$parentBuyOrder}, childBuyOrder: {$childBuyOrder}");
            $this->logError(json_encode($resp));
            $this->throwErrorRedirect('Pago rechazado');
        }
        return $transaction;
    }


}

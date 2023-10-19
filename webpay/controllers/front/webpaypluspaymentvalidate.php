<?php

use PrestaShop\Module\WebpayPlus\Helpers\SqlHelper;
use PrestaShop\Module\WebpayPlus\Controller\PaymentModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithCommon;
use PrestaShop\Module\WebpayPlus\Helpers\WebpayPlusFactory;
use Transbank\Webpay\WebpayPlus\TransactionCommitResponse;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;

/**
 * Class WebPayWebpayplusPaymentValidateModuleFrontController.
 */
class WebPayWebpayplusPaymentValidateModuleFrontController extends PaymentModuleFrontController
{
    use InteractsWithCommon;
    use InteractsWithWebpay;
    use InteractsWithWebpayDb;

    protected $responseData = [];

    public function initContent()
    {
        parent::initContent();
        $this->logger = TbkFactory::createLogger();
        //Flujos:
        //1. Flujo normal (OK): solo llega token_ws
        //2. Timeout (más de 10 minutos en el formulario de Transbank): llegan TBK_ID_SESION y TBK_ORDEN_COMPRA
        //3. Pago abortado (con botón anular compra en el formulario de Webpay): llegan TBK_TOKEN, TBK_ID_SESION, TBK_ORDEN_COMPRA
        //4. Caso atipico: llega todos token_ws, TBK_TOKEN, TBK_ID_SESION, TBK_ORDEN_COMPRA
        $params = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        $tokenWs = isset($params['token_ws']) && $params['token_ws']!==null ? $params['token_ws'] : null;
        $tbktoken = isset($params['TBK_TOKEN']) && $params['TBK_TOKEN']!==null ? $params['TBK_TOKEN'] : null;
        $tbkIdSesion = isset($params['TBK_ID_SESION'])&& $params['TBK_ID_SESION']!==null ? $params['TBK_ID_SESION'] : null;
        if($this->isDebugActive()){
            $this->logInfo("C.1. Iniciando validación luego de redirección desde tbk =>
                method: {$_SERVER['REQUEST_METHOD']}");
            $this->logInfo(json_encode($params));
        }

        if (isset($tokenWs) && !isset($tbktoken)) {//Flujo 1 => Confirmar Transacción

            $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($tokenWs);
            if($this->isDebugActive()){
                $this->logInfo("C.2. Tx obtenido desde la tabla webpay_transactions => token: {$tokenWs}");
                $this->logInfo(json_encode($webpayTransaction));
            }
            $cart = $this->getCart($webpayTransaction->cart_id);
            $this->validateData($cart);

            if ($webpayTransaction->status == TransbankWebpayRestTransaction::STATUS_APPROVED) {
                $this->logError("C.3. Transacción ya estaba aprobada => token: {$tokenWs}");
                $this->logError(json_encode($webpayTransaction));
                return $this->redirectToPaidSuccessPaymentPage($cart);
            } 
            else if ($webpayTransaction->status != TransbankWebpayRestTransaction::STATUS_INITIALIZED) {
                $this->logError("C.3. Transacción se encuentra en estado rechazado o cancelado => token: {$tokenWs}");
                $this->logError(json_encode($webpayTransaction));
                $msg = 'Esta compra se encuentra en estado rechazado o cancelado y no se puede aceptar el pago';
                $this->setPaymentErrorPage($msg);
            }

            if ($this->getTransactionApprovedByCartId($webpayTransaction->cart_id) && !isset($_GET['final'])) {
                $this->logError("C.3. El carro de compras ya fue pagado con otra Transacción => token: {$tokenWs}");
                $this->logError(json_encode($webpayTransaction));
                $msg = "Otra transacción de este carro de compras ya fue aprobada. 
                    Se rechazo este pago para no generar un cobro duplicado";
                $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
                $webpayTransaction->save();
                $this->setPaymentErrorPage($msg);
            }

            $this->processPayment($tokenWs, $webpayTransaction, $cart);
        }
        else if (!isset($tokenWs) && !isset($tbktoken)) {//Flujo 2 => El pago fue anulado por tiempo de espera.
            $this->logError("C.2. Error tipo Flujo 2: El pago fue anulado por tiempo de espera => tbkIdSesion:
                {$tbkIdSesion}");
            $this->stopIfComingFromAnTimeoutErrorOnWebpay($tbkIdSesion);
        }
        else if (!isset($tokenWs) && isset($tbktoken)) {//Flujo 3 => El pago fue anulado por el usuario.
            $this->logError("C.2. Error tipo Flujo 3: El pago fue anulado por el usuario => tbktoken: {$tbktoken}");
            $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($tbktoken);
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_ABORTED_BY_USER;
            $this->logError("C.2. Error tipo Flujo 3 => tbktoken: {$tbktoken}");
            $this->logError(json_encode($webpayTransaction));
            $webpayTransaction->save();
            $msg = 'Transacción abortada desde el formulario de pago. Puedes reintentar el pago. ';
            $this->setPaymentErrorPage($msg);
        }
        else if (isset($tokenWs) && isset($tbktoken)) {//Flujo 4 => El pago es inválido.
            $this->logError("C.2. Error tipo Flujo 4: El pago es inválido  => tokenWs:
                {$tokenWs}, tbktoken: {$tbktoken}");
            $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($tokenWs);
            $msg = 'Al parecer ocurrió un error durante el proceso de pago. Puedes volver a intentar. ';
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
            $webpayTransaction->save();
            $this->setPaymentErrorPage($msg);
        }
        
    }

    private function validateData($cart)
    {
        if (!$this->module->active) {
            $error = 'El módulo no esta activo';
            $this->logError($error);
            $this->throwErrorRedirect($error);
        } 

        if ($cart->id == null) {
            $error = 'Cart id was null. Redirecto to confirmation page of the last order';
            $this->logError($error);
            $id_usuario = Context::getContext()->customer->id;
            $sql = 'SELECT id_cart FROM '._DB_PREFIX_."cart p WHERE p.id_customer = $id_usuario ORDER BY p.id_cart DESC";
            $cart->id = SqlHelper::getValue($sql);
            $customer = $this->getCustomer($cart->id_customer);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int) $cart->id.'&id_module='
                .(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
        }

        if ($cart->id_customer == 0) {
            $error = 'id_customer es cero';
            $this->logError($error);
            $this->throwErrorRedirect($error);
        } 
        else if ($cart->id_address_delivery == 0) {
            $error = 'id_address_delivery es cero';
            $this->logError($error);
            $this->throwErrorRedirect($error);
        }
        else if ($cart->id_address_invoice == 0) {
            $error = 'id_address_invoice es cero';
            $this->logError($error);
            $this->throwErrorRedirect($error);
        }

        $customer = $this->getCustomer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $error = 'Customer not load';
            $this->logError($error);
            $this->throwErrorRedirect($error);
        }
    }

    /**
     * @param $webpayTransaction TransbankWebpayRestTransaction
     * @param $cart
     */
    private function processPayment($token, $webpayTransaction, $cart)
    {
        if($this->isDebugActive()){
            $this->logInfo("C.3. Transaccion antes del commit  => token: {$token}");
            $this->logInfo(json_encode($webpayTransaction));
        }
        $amount = $this->getOrderTotal($cart);
        if ($webpayTransaction->amount != $this->getOrderTotalRound($cart)) {
            $this->logError("C.3. El carro de compras ha sido manipulado => token: {$token}");
            $this->logError(json_encode($webpayTransaction));
            $this->handleCartManipulated($token, $webpayTransaction);
        }

        $transbankSdkWebpay = WebpayPlusFactory::create();
        try {
            $result = $transbankSdkWebpay->commitTransaction($webpayTransaction->token);
        } catch (\Exception $e) {
            $this->setPaymentErrorPage($e->getMessage());
        }
        $this->logInfo("C.4. Transacción con commit en Transbank => token: {$token}");
        $this->logInfo(json_encode($result));
        if (!is_array($result) && isset($result->buyOrder) && $result->responseCode === 0){
            $this->logInfo("***** COMMIT TBK OK *****");
            $this->logInfo("TRANSACCION VALIDADA POR TBK => TOKEN: {$token}");
            $this->logInfo("SI NO SE ENCUENTRA VALIDACION POR PRESTASHOP DEBE ANULARSE");
        }

        $webpayTransaction->transbank_response = json_encode($result);
        $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
        $webpayTransaction->card_number = is_array($result) ? '' : $result->cardDetail['card_number'];
        $saved = $webpayTransaction->save(); // Guardar como fallida por si algo falla más adelante

        if (!$saved) {
            $this->logError("C.5. No se pudo guardar en base de datos el resultado del commit => token: {$token}");
            $this->logError(json_encode($webpayTransaction));
            $error = 'No se pudo guardar en base de datos el resultado de la transacción';
            $this->throwErrorRedirect($error);
        }
        else if (!is_array($result) && isset($result->buyOrder) && $result->responseCode === 0) {
            if($this->isDebugActive()){
                $this->logInfo("C.5. Transacción con commit exitoso en Transbank y guardado => token: {$token}");
            }
            $customer = $this->getCustomer($cart->id_customer);
            $currency = Context::getContext()->currency;
            $okStatus = $this->getWebpayOkStatus();

            if($this->isDebugActive()){
                $this->logInfo("C.6. Procesando pago - antes de validateOrder");
                $this->logInfo("token : {$token}, amount : {$amount}, cartId: {$cart->id}, okStatus: {$okStatus}
                    , currencyId: {$currency->id}, customer_secure_key: {$customer->secure_key}");
            }
            $this->module->validateOrder(
                (int) $cart->id,
                $okStatus,
                $amount,
                $this->module->displayName,
                'Pago exitoso',
                [],
                (int) $currency->id,
                false,
                $customer->secure_key
            );
            if($this->isDebugActive()){
                $this->logInfo("C.7. Procesando pago despues de validateOrder => token: {$token}");
            }

            $order = new Order($this->module->currentOrder);
            $this->saveOrderPayment($order, $cart, $webpayTransaction->card_number);

            $webpayTransaction->response_code = $result->responseCode;
            $webpayTransaction->order_id = $order->id;
            $webpayTransaction->vci = $result->vci;
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_APPROVED;
            $webpayTransaction->save();
            $this->logInfo("***** TODO OK *****");
            $this->logInfo("TRANSACCION VALIDADA POR PRESTASHOP Y POR TBK EN ESTADO STATUS_APPROVED => 
                TOKEN: {$token}");
            $this->logInfo(json_encode($webpayTransaction));
            $this->redirectToPaidSuccessPaymentPage($cart);
        } else {
            $this->logError("C.5. Respuesta de tbk commit fallido => token: {$token}");
            $this->logError(json_encode($result));
            $webpayTransaction->response_code = isset($result->responseCode) ? $result->responseCode : null;
            $webpayTransaction->transbank_response = json_encode($result);
            $webpayTransaction->save();

            $this->responseData['PAYMENT_OK'] = 'FAIL';

            $error = 'Error en el pago';
            if (is_array($result) && isset($result['error'])) {
                $error = $result['error'];
            }
            else if ($result instanceof TransactionCommitResponse) {
                $error = 'La transacción ha sido rechazada. Por favor, reintente el pago. '.
                    'Código de respuesta: '.$result->getResponseCode().'. Estado: '.$result->getStatus();
            }
            $this->setPaymentErrorPage($error);
        }
    }

    protected function handleCartManipulated($token, $webpayTransaction)
    {
        $error = 'El monto del carro ha cambiado, la transacción no fue completada, ningún
        cargo será realizado en su tarjeta. Por favor, reintente el pago.';
        $message = 'Carro ha sido manipulado durante el proceso de pago';
        $this->updateTransactionStatus($webpayTransaction, 
            TransbankWebpayRestTransaction::STATUS_FAILED, json_encode(['error' => $message]));
        $this->setPaymentErrorPage($error);
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @return void
     */
    private function stopIfComingFromAnTimeoutErrorOnWebpay($sessionId)
    {
        $webpayTransaction = $this->getTransbankWebpayRestTransactionBySessionId($sessionId);
        $errorMessage = "Al parecer pasaron más de 15 minutos en el formulario de pago, 
            por lo que la transacción se ha cancelado automáticamente";
        if (!isset($webpayTransaction)) {
            $this->setPaymentErrorPage($errorMessage);
        }

        if ($webpayTransaction->status == TransbankWebpayRestTransaction::STATUS_APPROVED) {
            $cart = $this->getCart($webpayTransaction->cart_id);
            $this->redirectToPaidSuccessPaymentPage($cart);
        }
        $this->updateTransactionStatus($webpayTransaction, 
            TransbankWebpayRestTransaction::STATUS_FAILED, json_encode(['error' => $errorMessage]));
        $this->setPaymentErrorPage($errorMessage);
    }

    private function updateTransactionStatus($tx, $status, $tbkResponse){
        $tx->status = $status;
        $tx->transbank_response = $tbkResponse;
        $tx->save();
    }

}

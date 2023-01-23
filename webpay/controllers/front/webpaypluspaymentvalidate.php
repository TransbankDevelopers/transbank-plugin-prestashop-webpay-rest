<?php

use PrestaShop\Module\WebpayPlus\Helpers\SqlHelper;
use PrestaShop\Module\WebpayPlus\Controller\PaymentModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\WebpayPlusFactory;
use Transbank\Webpay\WebpayPlus\TransactionCommitResponse;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithFullLog;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;

/**
 * Class WebPayWebpayplusPaymentValidateModuleFrontController.
 */
class WebPayWebpayplusPaymentValidateModuleFrontController extends PaymentModuleFrontController
{
    use InteractsWithFullLog;
    use InteractsWithWebpay;
    use InteractsWithWebpayDb;

    protected $responseData = [];

    public function initContent()
    {
        parent::initContent();

        //Flujos:
        //1. Flujo normal (OK): solo llega token_ws
        //2. Timeout (más de 10 minutos en el formulario de Transbank): llegan TBK_ID_SESION y TBK_ORDEN_COMPRA
        //3. Pago abortado (con botón anular compra en el formulario de Webpay): llegan TBK_TOKEN, TBK_ID_SESION, TBK_ORDEN_COMPRA
        //4. Caso atipico: llega todos token_ws, TBK_TOKEN, TBK_ID_SESION, TBK_ORDEN_COMPRA
        $params = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        $tokenWs = isset($params['token_ws']) ? $params['token_ws'] : '';
        $tbktoken = isset($params['TBK_TOKEN']) ? $params['TBK_TOKEN'] : '';
        $tbkOrdenCompra = isset($params['TBK_ORDEN_COMPRA']) ? $params['TBK_ORDEN_COMPRA'] : '';
        $tbkIdSesion = isset($params['TBK_ID_SESION']) ? $params['TBK_ID_SESION'] : '';
        $this->logWebpayPlusRetornandoDesdeTbk($_SERVER['REQUEST_METHOD'], $params);

        if (isset($tokenWs) && !isset($tbktoken)) {//Flujo 1 => Confirmar Transacción

            $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($tokenWs);
            $this->logWebpayPlusDespuesObtenerTx($tokenWs, $webpayTransaction);
            $cart = $this->getCart($webpayTransaction->cart_id);
            $this->validateData($cart);

            if ($webpayTransaction->status == TransbankWebpayRestTransaction::STATUS_APPROVED) {
                $this->logWebpayPlusCommitTxYaAprobadoError($tokenWs, $webpayTransaction);
                return $this->redirectToPaidSuccessPaymentPage($cart);
            } 
            else if ($webpayTransaction->status != TransbankWebpayRestTransaction::STATUS_INITIALIZED) {
                $this->logWebpayPlusCommitTxNoInicializadoError($tokenWs, $webpayTransaction);
                $msg = 'Esta compra se encuentra en estado rechazado o cancelado y no se puede aceptar el pago';
                return $this->setPaymentErrorPage($msg);
            }

            if ($this->getTransactionApprovedByCartId($webpayTransaction->cart_id) && !isset($_GET['final'])) {
                $this->logWebpayPlusCommitTxCarroAprobadoError($tokenWs, $webpayTransaction);
                $msg = 'Otra transacción de este carro de compras ya fue aprobada. Se rechazo este pago para no generar un cobro duplicado';
                $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
                $webpayTransaction->save();
                return $this->setPaymentErrorPage($msg);
            }

            $this->processPayment($tokenWs, $webpayTransaction, $cart);
        }
        else if (!isset($tokenWs) && !isset($tbktoken)) {//Flujo 2 => El pago fue anulado por tiempo de espera.
            $this->stopIfComingFromAnTimeoutErrorOnWebpay($tbkIdSesion);
        }
        else if (!isset($tokenWs) && isset($tbktoken)) {//Flujo 3 => El pago fue anulado por el usuario.
            $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($tbktoken);
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_ABORTED_BY_USER;
            $webpayTransaction->save();
            $msg = 'Transacción abortada desde el formulario de pago. Puedes reintentar el pago. ';
            $this->logError($msg);
            return $this->setPaymentErrorPage($msg);
        }
        else if (isset($tokenWs) && isset($tbktoken)) {//Flujo 4 => El pago es inválido.
            $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($tokenWs);
            $msg = 'Al parecer ocurrió un error durante el proceso de pago. Puedes volver a intentar. ';
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
            $webpayTransaction->save();
            if($this->getDebugActive()==1){
                $this->logError($msg);
            }
            return $this->setPaymentErrorPage($msg);
        }
        
    }

    private function validateData($cart)
    {
        if (!$this->module->active) {
            $this->throwErrorRedirect('El módulo no esta activo');
        } 

        if ($cart->id == null) {
            $this->logError('Cart id was null. Redirecto to confirmation page of the last order');
            $id_usuario = Context::getContext()->customer->id;
            $sql = 'SELECT id_cart FROM '._DB_PREFIX_."cart p WHERE p.id_customer = $id_usuario ORDER BY p.id_cart DESC";
            $cart->id = SqlHelper::getValue($sql);
            $customer = $this->getCustomer($cart->id_customer);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
        }

        if ($cart->id_customer == 0) {
            $this->throwErrorRedirect('id_customer es cero');
        } 
        else if ($cart->id_address_delivery == 0) {
            $this->throwErrorRedirect('id_address_delivery es cero');
        }
        else if ($cart->id_address_invoice == 0) {
            $this->throwErrorRedirect('id_address_invoice es cero');
        }

        $customer = $this->getCustomer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->throwErrorRedirect('Customer not load');
        }
    }

    /**
     * @param $webpayTransaction TransbankWebpayRestTransaction
     * @param $cart
     */
    private function processPayment($token, $webpayTransaction, $cart)
    {
        $this->logWebpayPlusAntesCommitTx($token, $webpayTransaction, $cart);
        $amount = $this->getOrderTotal($cart);
        if ($webpayTransaction->amount != $this->getOrderTotalRound($cart)) {
            $this->logWebpayPlusCommitTxCarroManipuladoError($token, $webpayTransaction);
            return $this->handleCartManipulated($token, $webpayTransaction);
        }

        $transbankSdkWebpay = WebpayPlusFactory::create();
        $result = $transbankSdkWebpay->commitTransaction($webpayTransaction->token);
        $this->logWebpayPlusDespuesCommitTx($token, $result);

        $webpayTransaction->transbank_response = json_encode($result);
        $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
        $webpayTransaction->card_number = is_array($result) ? '' : $result->cardDetail['card_number'];
        $webpayTransaction->save(); // Guardar como fallida por si algo falla más adelante

        if (SqlHelper::getMsgError()!=null) {
            $this->logWebpayPlusGuardandoCommitError($token, $webpayTransaction);
            $error = 'No se pudo guardar en base de datos el resultado de la transacción: '.SqlHelper::getMsgError();
            $this->throwErrorRedirect($error);
        }
        else if (!is_array($result) && isset($result->buyOrder) && $result->responseCode === 0) {
            $this->logWebpayPlusGuardandoCommitExitoso($token);
            $customer = $this->getCustomer($cart->id_customer);
            $currency = Context::getContext()->currency;
            $OKStatus = $this->getWebpayOkStatus();

            $this->logWebpayPlusAntesValidateOrderPrestashop($token, $amount, $cart->id, $OKStatus, $currency->id, $customer->secure_key);
            $this->module->validateOrder(
                (int) $cart->id,
                $OKStatus,
                $amount,
                $this->module->displayName,
                'Pago exitoso',
                [],
                (int) $currency->id,
                false,
                $customer->secure_key
            );
            $this->logWebpayPlusDespuesValidateOrderPrestashop($token);

            $order = new Order($this->module->currentOrder);
            $this->saveOrderPayment($order, $cart, $webpayTransaction->card_number);

            $webpayTransaction->response_code = $result->responseCode;
            $webpayTransaction->order_id = $order->id;
            $webpayTransaction->vci = $result->vci;
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_APPROVED;
            $webpayTransaction->save();
            $this->logWebpayPlusTodoOk($token, $webpayTransaction);
            return $this->redirectToPaidSuccessPaymentPage($cart);
        } else {
            $this->logWebpayPlusCommitFallidoError($token, $result);
            $webpayTransaction->response_code = isset($result->responseCode) ? $result->responseCode : null;
            $webpayTransaction->transbank_response = json_encode($result);
            $webpayTransaction->save();

            $this->responseData['PAYMENT_OK'] = 'FAIL';

            $error = 'Error en el pago';
            $detail = 'Indefinido';
            if (is_array($result) && isset($result['error'])) {
                $error = $result['error'];
                $detail = isset($result['detail']) ? $result['detail'] : 'Indefinido';
            }
            else if ($result instanceof TransactionCommitResponse) {
                $error = 'La transacción ha sido rechazada. Por favor, reintente el pago. ';
                $detail = 'Código de respuesta: '.$result->getResponseCode().'. Estado: '.$result->getStatus();
            }
            $this->setPaymentErrorPage($error, $detail);
        }
    }

    protected function handleCartManipulated($token, $webpayTransaction)
    {
        $error = 'El monto del carro ha cambiado, la transacción no fue completada, ningún
        cargo será realizado en su tarjeta. Por favor, reintente el pago.';
        $message = 'Carro ha sido manipulado durante el proceso de pago';
        $this->updateTransactionStatus($webpayTransaction, TransbankWebpayRestTransaction::STATUS_FAILED, json_encode(['error' => $message]));
        return $this->setPaymentErrorPage($error);
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
        $errorMessage = 'Al parecer pasaron más de 15 minutos en el formulario de pago, por lo que la transacción se ha cancelado automáticamente';
        if (!isset($webpayTransaction)) {
            $this->setPaymentErrorPage($errorMessage);
        }

        if ($webpayTransaction->status == TransbankWebpayRestTransaction::STATUS_APPROVED) {
            $cart = $this->getCart($webpayTransaction->cart_id);
            return $this->redirectToPaidSuccessPaymentPage($cart);
        }
        $this->updateTransactionStatus($webpayTransaction, TransbankWebpayRestTransaction::STATUS_FAILED, json_encode(['error' => $errorMessage]));
        $this->setPaymentErrorPage($errorMessage);
    }

    private function updateTransactionStatus($tx, $status, $tbkResponse){
        $tx->status = $status;
        $tx->transbank_response = $tbkResponse;
        return $tx->save();
    }

}

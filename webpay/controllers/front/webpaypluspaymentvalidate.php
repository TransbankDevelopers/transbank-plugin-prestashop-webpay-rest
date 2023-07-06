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
        $tokenWs = isset($params['token_ws']) && $params['token_ws']!==null ? $params['token_ws'] : null;
        $tbktoken = isset($params['TBK_TOKEN']) && $params['TBK_TOKEN']!==null ? $params['TBK_TOKEN'] : null;
        $tbkOrdenCompra = isset($params['TBK_ORDEN_COMPRA']) && $params['TBK_ORDEN_COMPRA']!==null ? $params['TBK_ORDEN_COMPRA'] : null;
        $tbkIdSesion = isset($params['TBK_ID_SESION'])&& $params['TBK_ID_SESION']!==null ? $params['TBK_ID_SESION'] : null;
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
                $this->setPaymentErrorPage($msg);
            }

            if ($this->getTransactionApprovedByCartId($webpayTransaction->cart_id) && !isset($_GET['final'])) {
                $this->logWebpayPlusCommitTxCarroAprobadoError($tokenWs, $webpayTransaction);
                $msg = 'Otra transacción de este carro de compras ya fue aprobada. Se rechazo este pago para no generar un cobro duplicado';
                $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
                $webpayTransaction->save();
                $this->setPaymentErrorPage($msg);
            }

            $this->processPayment($tokenWs, $webpayTransaction, $cart);
        }
        else if (!isset($tokenWs) && !isset($tbktoken)) {//Flujo 2 => El pago fue anulado por tiempo de espera.
            $this->logWebpayPlusRetornandoDesdeTbkFujo2Error($tbkIdSesion);
            $this->stopIfComingFromAnTimeoutErrorOnWebpay($tbkIdSesion);
        }
        else if (!isset($tokenWs) && isset($tbktoken)) {//Flujo 3 => El pago fue anulado por el usuario.
            $this->logWebpayPlusRetornandoDesdeTbkFujo3Error($tbktoken);
            $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($tbktoken);
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_ABORTED_BY_USER;
            $this->logWebpayPlusRetornandoDesdeTbkFujo3TxError($tbktoken, $webpayTransaction);
            $webpayTransaction->save();
            $msg = 'Transacción abortada desde el formulario de pago. Puedes reintentar el pago. ';
            $this->setPaymentErrorPage($msg);
        }
        else if (isset($tokenWs) && isset($tbktoken)) {//Flujo 4 => El pago es inválido.
            $this->logWebpayPlusRetornandoDesdeTbkFujo4Error($tokenWs, $tbktoken);
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
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
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
        $saved = $webpayTransaction->save(); // Guardar como fallida por si algo falla más adelante

        if (!$saved) {
            $this->logWebpayPlusGuardandoCommitError($token, $webpayTransaction);
            $error = 'No se pudo guardar en base de datos el resultado de la transacción';
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

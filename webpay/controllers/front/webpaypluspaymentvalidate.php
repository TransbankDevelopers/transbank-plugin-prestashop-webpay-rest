<?php

use PrestaShop\Module\WebpayPlus\Helpers\SqlHelper;
use PrestaShop\Module\WebpayPlus\Controller\PaymentModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\WebpayPlusFactory;
use Transbank\Webpay\WebpayPlus\TransactionCommitResponse;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

/**
 * Class WebPayValidateModuleFrontController.
 */
class WebPayWebpayplusPaymentValidateModuleFrontController extends PaymentModuleFrontController
{
    protected $responseData = [];
    /**
     * @var string[]
     */
    private $paymentTypeCodearray = [
        'VD' => 'Venta débito',
        'VN' => 'Venta normal',
        'VC' => 'Venta en cuotas',
        'SI' => '3 cuotas sin interés',
        'S2' => '2 cuotas sin interés',
        'NC' => 'N cuotas sin interés',
    ];

    public function initContent()
    {
        parent::initContent();
        if($this->getDebugActive()==1){
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $tokenWs = isset($_POST['token_ws']) ? $_POST['token_ws'] : '';
                $tbktoken = isset($_POST['TBK_TOKEN']) ? $_POST['TBK_TOKEN'] : '';
                $tbkOrdenCompra = isset($_POST['TBK_ORDEN_COMPRA']) ? $_POST['TBK_ORDEN_COMPRA'] : '';
                $tbkIdSesion = isset($_POST['TBK_ID_SESION']) ? $_POST['TBK_ID_SESION'] : '';
                $this->logInfo('C.1. Iniciando validación luego de redirección por POST');
            }
            else{
                $tokenWs = isset($_GET['token_ws']) ? $_GET['token_ws'] : '';
                $tbktoken = isset($_GET['TBK_TOKEN']) ? $_GET['TBK_TOKEN'] : '';
                $tbkOrdenCompra = isset($_GET['TBK_ORDEN_COMPRA']) ? $_GET['TBK_ORDEN_COMPRA'] : '';
                $tbkIdSesion = isset($_GET['TBK_ID_SESION']) ? $_GET['TBK_ID_SESION'] : '';
                $this->logInfo('C.1. Iniciando validación luego de redirección por GET');
            }
            $this->logInfo('TOKEN_WS: '.$tokenWs.', TBK_TOKEN: '.$tbktoken.', TBK_ORDEN_COMPRA: '.$tbkOrdenCompra.', TBK_ID_SESION: '.$tbkIdSesion);
        }

        $this->stopIfComingFromAnTimeoutErrorOnWebpay();

        if (isset($_POST['TBK_TOKEN']) && !isset($_POST['token_ws'])) {
            $token = $_POST['TBK_TOKEN'];

            $webpayTransaction = $this->getTransactionByToken($token);
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_ABORTED_BY_USER;
            $webpayTransaction->save();
            $msg = 'Transacción abortada desde el formulario de pago. Puedes reintentar el pago. ';
            $this->logError($msg);
            return $this->setPaymentErrorPage($msg);
        }

        $webpayTransaction = $this->getTransactionByToken();
        $cart = $this->getCart($webpayTransaction->cart_id);
        if (!$this->validateData($cart)) {
            $msg = 'Can not validate order cart';
            $this->logError($msg);
            return $this->throwErrorRedirect($msg);
        }

        if ($webpayTransaction->status == TransbankWebpayRestTransaction::STATUS_APPROVED) {
            if($this->getDebugActive()==1){
                $this->logInfo('Transacción ya estaba aprobada');
            }
            return $this->redirectToSuccessPage($cart);
        }

        if ($webpayTransaction->status != TransbankWebpayRestTransaction::STATUS_INITIALIZED) {
            $msg = 'Esta compra se encuentra en estado rechazado o cancelado y no se puede aceptar el pago';
            if($this->getDebugActive()==1){
                $this->logError($msg);
            }
            return $this->setPaymentErrorPage($msg);
        }

        if (isset($_POST['TBK_TOKEN']) && isset($_POST['token_ws'])) {
            $msg = 'Al parecer ocurrió un error durante el proceso de pago. Puedes volver a intentar. ';
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
            $webpayTransaction->save();
            if($this->getDebugActive()==1){
                $this->logError($msg);
            }
            return $this->setPaymentErrorPage($msg);
        }

        if ($this->getOtherApprovedTransactionsOfThisCart($webpayTransaction) && !isset($_GET['final'])) {
            $msg = 'Otra transacción de este carro de compras ya fue aprobada. Se rechazo este pago para no generar un cobro duplicado';
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
            $webpayTransaction->save();
            if($this->getDebugActive()==1){
                $this->logError($msg);
            }
            return $this->setPaymentErrorPage($msg);
        }


        if($this->getDebugActive()==1){
            $this->logInfo('------------CART DB-------------------------------------');
            $this->cartToLog($cart);
            $this->logInfo('------------CART CONTEXT--------------------------------');
            $this->cartToLog($this->getCartFromContext());
        }
        $this->processPayment($webpayTransaction, $cart);
    }
    
    /**
     * @param $data
     *
     * @return |null
     */
    protected function getTokenWs($data)
    {
        $token_ws = isset($data['token_ws']) ? $data['token_ws'] : null;
        if (!isset($token_ws)) {
            $this->throwErrorRedirect('No se recibió el token');
        }
        return $token_ws;
    }

    private function validateData($cart)
    {
        if ($cart->id == null) {
            $this->logError('Cart id was null. Redirecto to confirmation page of the last order');
            $id_usuario = Context::getContext()->customer->id;
            $sql = 'SELECT id_cart FROM '._DB_PREFIX_."cart p WHERE p.id_customer = $id_usuario ORDER BY p.id_cart DESC";
            $cart->id = SqlHelper::getValue($sql);
            $customer = $this->getCustomer($cart->id_customer);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
            return false;
        }

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            $this->throwErrorRedirect('id_costumer or id_address_delivery or id_address_invoice or $this->module->active was true');
            return false;
        }

        $customer = $this->getCustomer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            $this->throwErrorRedirect('Customer not load');

            return false;
        }

        return true;
    }

    /**
     * @param $webpayTransaction TransbankWebpayRestTransaction
     * @param $cart
     */
    private function processPayment($webpayTransaction, $cart)
    {
        $amount = $this->getOrderTotal($cart);
        if ($webpayTransaction->amount != $this->getOrderTotalRound($cart)) {
            return $this->handleCartManipulated($webpayTransaction);
        }

        $transbankSdkWebpay = WebpayPlusFactory::create();
        if($this->getDebugActive()==1){
            $this->logInfo('C.2. Preparando datos antes del commit en Transbank');
            $this->logInfo(json_encode($webpayTransaction));
        }
        $result = $transbankSdkWebpay->commitTransaction($webpayTransaction->token);

        if($this->getDebugActive()==1){
            $this->logInfo('C.3. Transacción con commit en Transbank');
            $this->logInfo(json_encode($result));
        }

        $webpayTransaction->transbank_response = json_encode($result);
        $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
        $webpayTransaction->card_number = is_array($result) ? '' : $result->cardDetail['card_number'];
        $updateResult = $webpayTransaction->save(); // Guardar como fallida por si algo falla más adelante

        if (!$updateResult) {
            $error = 'No se pudo guardar en base de datos el resultado de la transacción: '.SqlHelper::getMsgError();
            $this->logError($error);
            $this->throwErrorRedirect($error);
        }
        if (is_array($result) && isset($result['error'])) {
            $error = 'Error: '.$result['detail'];
            $this->logError($error);
            $this->throwErrorRedirect($error);
        }

        if (isset($result->buyOrder) && $result->responseCode === 0) {
            $customer = $this->getCustomer($cart->id_customer);
            $currency = Context::getContext()->currency;
            $OKStatus = $this->getOkStatus();
            
            if($this->getDebugActive()==1){
                $this->logInfo('C.4. Procesando pago - antes de validateOrder');
                $this->logInfo('amount : '.$amount.', cartId: '.$cart->id.', OKStatus: '.$OKStatus.', currencyId: '.$currency->id.', customer_secure_key: '.$customer->secure_key);
            }

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
            if($this->getDebugActive()==1){
                $this->logInfo('C.5. Procesando pago - después de validateOrder');
            }

            $order = new Order($this->module->currentOrder);
            $this->saveOrderPayment($order, $cart, $webpayTransaction->card_number);

            $webpayTransaction->response_code = $result->responseCode;
            $webpayTransaction->order_id = $order->id;
            $webpayTransaction->vci = $result->vci;
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_APPROVED;
            $webpayTransaction->save();

            if($this->getDebugActive()==1){
                $this->logInfo('C.6. Procesando pago - se actualizó la transacción como STATUS_APPROVED');
            }

            return $this->redirectToSuccessPage($cart);
        } else {

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

            if ($result instanceof TransactionCommitResponse) {
                $error = 'La transacción ha sido rechazada. Por favor, reintente el pago. ';
                $detail = 'Código de respuesta: '.$result->getResponseCode().'. Estado: '.$result->getStatus();
            }

            $this->setPaymentErrorPage($error, $detail);
        }
    }

    

    protected function handleCartManipulated($webpayTransaction)
    {
        $error = 'El monto del carro ha cambiado, la transacción no fue completada, ningún
        cargo será realizado en su tarjeta. Por favor, reintente el pago.';
        $message = 'Carro ha sido manipulado durante el proceso de pago';
        $this->updateTransactionStatus($webpayTransaction, TransbankWebpayRestTransaction::STATUS_FAILED, json_encode(['error' => $message]));
        $this->logError($message);
        return $this->setPaymentErrorPage($error);
    }

    

    private function toRedirect($url, $data = [])
    {
        echo "<form action='".$url."' method='POST' name='webpayForm'>";
        foreach ($data as $name => $value) {
            echo "<input type='hidden' name='".htmlentities($name)."' value='".htmlentities($value)."'>";
        }
        echo '</form>'."<script language='JavaScript'>".'document.webpayForm.submit();'.'</script>';
    }

   

    

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @return void
     */
    private function stopIfComingFromAnTimeoutErrorOnWebpay()
    {
        if (!isset($_POST['TBK_TOKEN']) && !isset($_POST['token_ws']) && isset($_POST['TBK_ID_SESION'])) {
            $sessionId = $_POST['TBK_ID_SESION'];
            $sqlQuery = 'SELECT * FROM '._DB_PREFIX_.TransbankWebpayRestTransaction::TABLE_NAME.' WHERE `session_id` = "'.$sessionId.'"';
            $transaction = SqlHelper::getRow($sqlQuery);
            $errorMessage = 'Al parecer pasaron más de 15 minutos en el formulario de pago, por lo que la transacción se ha cancelado automáticamente';
            if (!$transaction) {
                $this->setPaymentErrorPage($errorMessage);
            }
            $webpayTransaction = new TransbankWebpayRestTransaction($transaction['id']);
            if ($webpayTransaction->status == TransbankWebpayRestTransaction::STATUS_APPROVED) {
                $cart = $this->getCart($webpayTransaction->cart_id);
                return $this->redirectToSuccessPage($cart);
            }
            $this->updateTransactionStatus($webpayTransaction, TransbankWebpayRestTransaction::STATUS_FAILED, json_encode(['error' => $errorMessage]));
            $this->setPaymentErrorPage($errorMessage);
        }
    }

    /**
     * @return TransbankWebpayRestTransaction
     */
    private function getTransactionByToken($token = null)
    {
        if (!$token) {
            $token = $this->getTokenWs($_GET);
        }
        $sql = 'SELECT * FROM '._DB_PREFIX_.TransbankWebpayRestTransaction::TABLE_NAME.' WHERE `token` = "'.pSQL($token).'"';
        $result = SqlHelper::getRow($sql);
        if ($result === false) {
            $this->throwErrorRedirect('Webpay Token '.$token.' was not found on database');
        }
        $webpayTransaction = new TransbankWebpayRestTransaction($result['id']);
        return $webpayTransaction;
    }

    /**
     * @return TransbankWebpayRestTransaction
     */
    private function getOtherApprovedTransactionsOfThisCart($webpayTransaction)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.TransbankWebpayRestTransaction::TABLE_NAME.' WHERE `cart_id` = "'.pSQL($webpayTransaction->cart_id).'" and status = '.TransbankWebpayRestTransaction::STATUS_APPROVED;
        return SqlHelper::getRow($sql);
    }

    /**
     * @param Cart $cart
     */
    private function redirectToSuccessPage(Cart $cart)
    {
        $customer = $this->getCustomer($cart->id_customer);
        $dataUrl = 'id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key;
        return Tools::redirect('index.php?controller=order-confirmation&'.$dataUrl);
    }

    private function getOkStatus(){
        $OKStatus = Configuration::get('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
        if ($OKStatus === '0') {
            $OKStatus = Configuration::get('PS_OS_PREPARATION');
        }
        return $OKStatus;
    }

    private function updateTransactionStatus($tx, $status, $tbkResponse){
        $tx->status = $status;
        $tx->transbank_response = $tbkResponse;
        return $tx->save();
    }

}

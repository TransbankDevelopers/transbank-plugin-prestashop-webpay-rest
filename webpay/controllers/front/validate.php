<?php

use PrestaShop\Module\WebpayPlus\Helpers\WebpayPlusFactory;
use Transbank\Webpay\WebpayPlus\TransactionCommitResponse;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_.'webpay/src/Model/TransbankWebpayRestTransaction.php';
require_once _PS_MODULE_DIR_.'webpay/libwebpay/TransbankSdkWebpay.php';
require_once _PS_MODULE_DIR_.'webpay/libwebpay/LogHandler.php';

/**
 * Class WebPayValidateModuleFrontController.
 */
class WebPayValidateModuleFrontController extends ModuleFrontController
{
    /**
     * @var LogHandler
     */
    protected $log;

    public $display_column_right = false;
    public $display_footer = false;
    public $display_column_left = false;
    public $ssl = true;

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

        $this->stopIfComingFromAnTimeoutErrorOnWebpay();

        if (isset($_POST['TBK_TOKEN']) && !isset($_POST['token_ws'])) {
            $token = $_POST['TBK_TOKEN'];

            $webpayTransaction = $this->getTransactionByToken($token);
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_ABORTED_BY_USER;
            $webpayTransaction->save();

            return $this->showErrorPage('Transacción abortada desde el formulario de pago. Puedes reintentar el pago. ');
        }

        $webpayTransaction = $this->getTransactionByToken();

        $cart = new Cart($webpayTransaction->cart_id);
        if (!$this->validateData($cart)) {
            return $this->throwError('Can not validate order cart');
        }

        if ($webpayTransaction->status == TransbankWebpayRestTransaction::STATUS_APPROVED) {
            return $this->redirectToSuccessPage($cart);
        }

        if ($webpayTransaction->status != TransbankWebpayRestTransaction::STATUS_INITIALIZED) {
            return $this->showErrorPage('Esta compra se encuentra en estado rechazado o cancelado y no se puede aceptar el pago');
        }

        if (isset($_POST['TBK_TOKEN']) && isset($_POST['token_ws'])) {
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
            $webpayTransaction->save();

            return $this->showErrorPage('Al parecer ocurrió un error durante el proceso de pago. Puedes volver a intentar. ');
        }

        if ($this->getOtherApprovedTransactionsOfThisCart($webpayTransaction) && !isset($_GET['final'])) {
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
            $webpayTransaction->save();

            return $this->setErrorPage('Otra transacción de este carro de compras ya fue aprobada. Se rechazo este pago para no generar un cobro duplicado');
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
            $this->throwError('RESPONSE: No se recibió el token');
        }

        return $token_ws;
    }

    private function validateData($cart)
    {
        if ($cart->id == null) {
            (new LogHandler())->logDebug('Cart id was null. Redirecto to confirmation page of the last order');
            $id_usuario = Context::getContext()->customer->id;
            $sql = 'SELECT id_cart FROM '._DB_PREFIX_."cart p WHERE p.id_customer = $id_usuario ORDER BY p.id_cart DESC";
            $id_carro = Db::getInstance()->getValue($sql, $use_cache = true);
            $cart->id = $id_carro;
            $customer = new Customer($cart->id_customer);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);

            return false;
        }

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            $this->throwError('Error: id_costumer or id_address_delivery or id_address_invoice or $this->module->active was true');

            return false;
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            $this->throwError();

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
        $amount = $cart->getOrderTotal(true, Cart::BOTH);

        if ($webpayTransaction->amount != $amount) {
            return $this->handleCartManipulated($webpayTransaction);
        }

        $transbankSdkWebpay = WebpayPlusFactory::create();
        $result = $transbankSdkWebpay->commitTransaction($webpayTransaction->token);
        $webpayTransaction->transbank_response = json_encode($result);
        $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
        $updateResult = $webpayTransaction->save(); // Guardar como fallida por si algo falla más adelante

        if (!$updateResult) {
            $this->throwError('No se pudo guardar en base de datos el resultado de la transacción: '.\DB::getMsgError());
        }
        if (is_array($result) && isset($result['error'])) {
            $this->throwError('Error: '.$result['detail']);
        }

        if (isset($result->buyOrder) && $result->responseCode === 0) {
            $customer = new Customer($cart->id_customer);
            $currency = Context::getContext()->currency;
            $OKStatus = Configuration::get('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
            if ($OKStatus === '0') {
                $OKStatus = Configuration::get('PS_OS_PREPARATION');
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

            $order = new Order($this->module->currentOrder);
            $payment = $order->getOrderPaymentCollection();
            if (isset($payment[0])) {
                $payment[0]->transaction_id = $cart->id;
                $payment[0]->card_number = '**** **** **** '.$result->cardDetail['card_number'];
                $payment[0]->card_brand = '';
                $payment[0]->card_expiration = '';
                $payment[0]->card_holder = '';
                $payment[0]->save();
            }

            $webpayTransaction->response_code = $result->responseCode;
            $webpayTransaction->order_id = $order->id;
            $webpayTransaction->vci = $result->vci;
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_APPROVED;
            $webpayTransaction->save();

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

            $this->showErrorPage($error.'  ('.$detail.')');
        }
    }

    protected function handleCartManipulated($webpayTransaction)
    {
        $error = 'El monto del carro ha cambiado, la transacción no fue completada, ningún
        cargo será realizado en su tarjeta. Por favor, reintente el pago.';
        $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
        $message = 'Carro ha sido manipulado durante el proceso de pago';
        $webpayTransaction->transbank_response = json_encode(['error' => $message]);
        $webpayTransaction->save();
        (new LogHandler())->logError($message);
        return $this->showErrorPage($error);
    }

    private function showErrorPage($description = '', $resultCode = null)
    {
        $WEBPAY_RESULT_DESC = $description;
        $WEBPAY_RESULT_CODE = $resultCode;
        $WEBPAY_VOUCHER_ORDENCOMPRA = isset($this->responseData['WEBPAY_VOUCHER_ORDENCOMPRA']) ? $this->responseData['WEBPAY_VOUCHER_ORDENCOMPRA'] : null;
        $WEBPAY_VOUCHER_TXDATE_HORA = isset($this->responseData['WEBPAY_VOUCHER_TXDATE_HORA']) ? $this->responseData['WEBPAY_VOUCHER_TXDATE_HORA'] : null;
        $WEBPAY_VOUCHER_TXDATE_FECHA = isset($this->responseData['WEBPAY_VOUCHER_TXDATE_FECHA']) ? $this->responseData['WEBPAY_VOUCHER_TXDATE_FECHA'] : null;

        $this->setErrorPage(
            $WEBPAY_RESULT_DESC,
            $WEBPAY_RESULT_CODE,
            $WEBPAY_VOUCHER_ORDENCOMPRA,
            $WEBPAY_VOUCHER_TXDATE_HORA,
            $WEBPAY_VOUCHER_TXDATE_FECHA
        );
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
     * @param $WEBPAY_RESULT_CODE
     * @param $WEBPAY_RESULT_DESC
     * @param $WEBPAY_VOUCHER_ORDENCOMPRA
     * @param $WEBPAY_VOUCHER_TXDATE_HORA
     * @param $WEBPAY_VOUCHER_TXDATE_FECHA
     *
     * @throws PrestaShopException
     */
    private function setErrorPage(
        $WEBPAY_RESULT_DESC,
        $WEBPAY_RESULT_CODE = null,
        $WEBPAY_VOUCHER_ORDENCOMPRA = null,
        $WEBPAY_VOUCHER_TXDATE_HORA = null,
        $WEBPAY_VOUCHER_TXDATE_FECHA = null
    ) {
        (new LogHandler())->logError('ERROR PAGE: '.$WEBPAY_RESULT_DESC);
        Context::getContext()->smarty->assign([
            'WEBPAY_RESULT_CODE'          => $WEBPAY_RESULT_CODE,
            'WEBPAY_RESULT_DESC'          => $WEBPAY_RESULT_DESC,
            'WEBPAY_VOUCHER_ORDENCOMPRA'  => $WEBPAY_VOUCHER_ORDENCOMPRA,
            'WEBPAY_VOUCHER_TXDATE_HORA'  => $WEBPAY_VOUCHER_TXDATE_HORA,
            'WEBPAY_VOUCHER_TXDATE_FECHA' => $WEBPAY_VOUCHER_TXDATE_FECHA,
        ]);

        if (Utils::isPrestashop_1_6()) {
            $this->setTemplate('payment_error_1.6.tpl');
        } else {
            $this->setTemplate('module:webpay/views/templates/front/payment_error.tpl');
        }
    }

    protected function throwError($message, $redirectTo = 'index.php?controller=order&step=3')
    {
        (new LogHandler())->logError($message);
        Tools::redirect($redirectTo);
        exit;
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
            $transaction = \Db::getInstance()->getRow($sqlQuery);
            $errorMessage = 'Al parecer pasaron más de 15 minutos en el formulario de pago, por lo que la transacción se ha cancelado automáticamente';
            if (!$transaction) {
                $this->showErrorPage($errorMessage);
            }
            $webpayTransaction = new TransbankWebpayRestTransaction($transaction['id']);
            if ($webpayTransaction->status == TransbankWebpayRestTransaction::STATUS_APPROVED) {
                $cart = new Cart($webpayTransaction->cart_id);

                return $this->redirectToSuccessPage($cart);
            }
            $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
            $webpayTransaction->transbank_response = json_encode(['error' => $errorMessage]);
            $webpayTransaction->save();
            $this->showErrorPage($errorMessage);
        }
    }

    /**
     * @return TransbankWebpayRestTransaction
     */
    private function getTransactionByToken($token = null)
    {
        if (!$token) {
            $token = $this->getTokenWs($_POST);
        }
        $sql = 'SELECT * FROM '._DB_PREFIX_.TransbankWebpayRestTransaction::TABLE_NAME.' WHERE `token` = "'.pSQL($token).'"';
        $result = \Db::getInstance()->getRow($sql);

        if ($result === false) {
            $this->throwError('Webpay Token '.$token.' was not found on database');
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

        return \Db::getInstance()->getRow($sql);
    }

    /**
     * @param Cart $cart
     */
    private function redirectToSuccessPage(Cart $cart)
    {
        $customer = new Customer($cart->id_customer);
        $dataUrl = 'id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key;

        return Tools::redirect('index.php?controller=order-confirmation&'.$dataUrl);
    }
}

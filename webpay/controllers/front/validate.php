<?php
require_once(dirname(__FILE__).'../../../../../config/config.inc.php');
if (!defined('_PS_VERSION_')) exit;

require_once(_PS_MODULE_DIR_.'webpay/libwebpay/TransbankSdkWebpay.php');
require_once(_PS_MODULE_DIR_.'webpay/libwebpay/LogHandler.php');

class WebPayValidateModuleFrontController extends ModuleFrontController {

    private $paymentTypeCodearray = array(
        "VD" => "Venta Debito",
        "VN" => "Venta Normal",
        "VC" => "Venta en cuotas",
        "SI" => "3 cuotas sin interés",
        "S2" => "2 cuotas sin interés",
        "NC" => "N cuotas sin interés",
    );

    public function initContent() {

        $this->display_column_left = true;
        $this->display_column_right = true;
        parent::initContent();

        $this->log = new LogHandler();

        if (Context::getContext()->cookie->PAYMENT_OK == 'WAITING') {
            $this->processPayment($_POST);
        } else {
            $this->processRedirect($_POST);
        }
    }

    private function validateData($cart) {

        $authorized = false;

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'webpay') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        if ($cart->id == null) {
            $id_usuario = Context::getContext()->customer->id;
            $sql = "SELECT id_cart FROM ps_cart p WHERE p.id_customer = $id_usuario ORDER BY p.id_cart DESC";
            $id_carro = Db::getInstance()->getValue($sql, $use_cache = true);
            $cart->id = $id_carro;
            $customer = new Customer($cart->id_customer);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
            return false;
        }

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
            return false;
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
            return false;
        }

        return true;
    }

	private function processPayment($data) {

        $cart = Context::getContext()->cart;

        if (!$this->validateData($cart)) {
            return;
        }

        $products = $cart->getProducts();
        $itemsId = array();
        foreach ($products as $product) {
            $itemsId[] = (int)$product['id_product'];
        }

        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        $buyOrder = $cart->id;

        $tokenWs = isset($data["token_ws"]) ? $data["token_ws"] : null;

        if (!isset($tokenWs)) {

            $error = 'Compra cancelada';
            $detail = 'El token no ha sido enviado';

            Context::getContext()->cookie->__set('PAYMENT_OK', 'FAIL');
            Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', 500);
            Context::getContext()->cookie->__set('WEBPAY_RESULT_DESC', $error . ', ' . $detail);

            $customer = new Customer($cart->id_customer);
            $currency = Context::getContext()->currency;
            $orderStatus = Configuration::get('PS_OS_CANCELED');

            $this->module->validateOrder((int)$cart->id,
                                        $orderStatus,
                                        $amount,
                                        $this->module->displayName,
                                        'Pago cancelado',
                                        array(),
                                        (int)$currency->id,
                                        false,
                                        $customer->secure_key);

            $this->processRedirect($data);

            return;
        }

        //patch for error with parallels carts
        $dataPaymentHash = $amount . $buyOrder. json_encode($itemsId);
        $paymentHash = md5($dataPaymentHash);
        $dataPaymentHashOriginal = $_GET['ph_'];

        //patch for error with parallels carts
        if ($dataPaymentHashOriginal != $paymentHash) {

            $this->log->logError('Error en el pago - dataPaymentHashOriginal: ' . $dataPaymentHashOriginal .
                                ', paymentHash: ' . $paymentHash);

            Context::getContext()->cookie->__set('PAYMENT_OK', 'FAIL');
            Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', 500);
            Context::getContext()->cookie->__set('WEBPAY_RESULT_DESC', 'Error en el pago, Carro inválido');

            $customer = new Customer($cart->id_customer);
            $currency = Context::getContext()->currency;
            $orderStatus = Configuration::get('PS_OS_ERROR');

            $this->module->validateOrder((int)$cart->id,
                                        $orderStatus,
                                        $amount,
                                        $this->module->displayName,
                                        'Pago fallido',
                                        array(),
                                        (int)$currency->id,
                                        false,
                                        $customer->secure_key);

            $this->processRedirect($data);
            return;
        }

        $config = array(
            "MODO" => Configuration::get('WEBPAY_AMBIENT'),
            "API_KEY" => Configuration::get('WEBPAY_APIKEY'),
            "COMMERCE_CODE" => Configuration::get('WEBPAY_STOREID')
        );

        $transbankSdkWebpay = new TransbankSdkWebpay($config);
        $result = $transbankSdkWebpay->commitTransaction($tokenWs);

        if (isset($result->buyOrder) && isset($result->detailOutput) && $result->detailOutput->responseCode == 0) {

            $transactionResponse = "Transacción aprobada";
            $date_tmp = strtotime($result->transactionDate);
            $date_tx_hora = date('H:i:s',$date_tmp);
            $date_tx_fecha = date('d-m-Y',$date_tmp);

            if($result->detailOutput->paymentTypeCode == "SI" || $result->detailOutput->paymentTypeCode == "S2" ||
                $result->detailOutput->paymentTypeCode == "NC" || $result->detailOutput->paymentTypeCode == "VC" ) {
                $tipo_cuotas = $this->paymentTypeCodearray[$result->detailOutput->paymentTypeCode];
            } else {
                $tipo_cuotas = "Sin cuotas";
            }

            if($result->detailOutput->paymentTypeCode == "VD"){
                $paymentType = "Débito";
            } else {
                $paymentType = "Crédito";
            }

            Context::getContext()->cookie->__set('PAYMENT_OK', 'SUCCESS');
            Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', $result->detailOutput->responseCode);
            Context::getContext()->cookie->__set('WEBPAY_RESULT_DESC', $transactionResponse);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXRESPTEXTO', $transactionResponse);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TOTALPAGO', $result->detailOutput->amount);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ACCDATE', $result->accountingDate);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ORDENCOMPRA', $result->buyOrder);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_HORA', $date_tx_hora);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_FECHA', $date_tx_fecha);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROTARJETA', $result->cardDetail['card_number']);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_AUTCODE', $result->detailOutput->authorizationCode);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOPAGO', $paymentType);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOCUOTAS', $tipo_cuotas);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_RESPCODE', $result->detailOutput->responseCode);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROCUOTAS', $result->detailOutput->installmentsNumber);

            $customer = new Customer($cart->id_customer);
            $currency = Context::getContext()->currency;
            $orderStatus = Configuration::get('PS_OS_PREPARATION');

            $this->module->validateOrder((int)$cart->id,
                                        $orderStatus,
                                        $amount,
                                        $this->module->displayName,
                                        'Pago exitoso',
                                        array(),
                                        (int)$currency->id,
                                        false,
                                        $customer->secure_key);

            $order = new Order($this->module->currentOrder);
            $payment = $order->getOrderPaymentCollection();
            if (isset($payment[0])) {
                $payment[0]->transaction_id = $cart->id;
                $payment[0]->card_number = '**********' . $result->cardDetail['card_number'];
                $payment[0]->card_brand = '';
                $payment[0]->card_expiration = '';
                $payment[0]->card_holder = '';
                $payment[0]->save();
            }

            $this->toRedirect($result->urlRedirection, array("token_ws" => $tokenWs));

        } else {

            Context::getContext()->cookie->__set('PAYMENT_OK', 'FAIL');

            $customer = new Customer($cart->id_customer);
            $currency = Context::getContext()->currency;
            $orderStatus = Configuration::get('PS_OS_ERROR');

            $this->module->validateOrder((int)$cart->id,
                                        $orderStatus,
                                        $amount,
                                        $this->module->displayName,
                                        'Pago fallido',
                                        array(),
                                        (int)$currency->id,
                                        false,
                                        $customer->secure_key);

            if (isset($result->detailOutput->responseDescription)) {

                Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', $result->detailOutput->responseCode);
                Context::getContext()->cookie->__set('WEBPAY_RESULT_DESC', $result->detailOutput->responseDescription);

                $this->processRedirect($data);

            } else {

                $error = isset($result["error"]) ? $result["error"] : 'Error en el pago';
                $detail = isset($result["detail"]) ? $result["detail"] : 'Indefinido';

                Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', 500);
                Context::getContext()->cookie->__set('WEBPAY_RESULT_DESC', $error . ', ' . $detail);

                $this->processRedirect($data);
            }
        }
    }

    private function processRedirect($data) {

        $cart = Context::getContext()->cart;

        if (!$this->validateData($cart)) {
            return;
        }

        $customer = new Customer($cart->id_customer);
        $currency = Context::getContext()->currency;

        if (Context::getContext()->cookie->PAYMENT_OK == 'SUCCESS') {

            $dataUrl = 'id_cart='.(int)$cart->id.
                    '&id_module='.(int)$this->module->id.
                    '&id_order='.$this->module->currentOrder.
                    '&key='.$customer->secure_key;

            Tools::redirect('index.php?controller=order-confirmation&' . $dataUrl);

        } else {

            $WEBPAY_RESULT_CODE = Context::getContext()->cookie->__get('WEBPAY_RESULT_CODE');
            $WEBPAY_RESULT_DESC = Context::getContext()->cookie->__get('WEBPAY_RESULT_DESC');
            $WEBPAY_VOUCHER_ORDENCOMPRA = Context::getContext()->cookie->__get('WEBPAY_VOUCHER_ORDENCOMPRA');
            $WEBPAY_VOUCHER_TXDATE_HORA = Context::getContext()->cookie->__get('WEBPAY_VOUCHER_TXDATE_HORA');
            $WEBPAY_VOUCHER_TXDATE_FECHA = Context::getContext()->cookie->__get('WEBPAY_VOUCHER_TXDATE_FECHA');

            Context::getContext()->smarty->assign(array(
                'WEBPAY_RESULT_CODE' => $WEBPAY_RESULT_CODE,
                'WEBPAY_RESULT_DESC' => $WEBPAY_RESULT_DESC,
                'WEBPAY_VOUCHER_ORDENCOMPRA' => $WEBPAY_VOUCHER_ORDENCOMPRA,
                'WEBPAY_VOUCHER_TXDATE_HORA' => $WEBPAY_VOUCHER_TXDATE_HORA,
                'WEBPAY_VOUCHER_TXDATE_FECHA' => $WEBPAY_VOUCHER_TXDATE_FECHA
            ));

            if (Utils::isPrestashop_1_6()) {
                $this->setTemplate('payment_error_1.6.tpl');
            } else {
                $this->setTemplate('module:webpay/views/templates/front/payment_error.tpl');
            }
        }
    }

    private function toRedirect($url, $data = []) {
        echo  "<form action='" . $url . "' method='POST' name='webpayForm'>";
        foreach ($data as $name => $value) {
            echo "<input type='hidden' name='".htmlentities($name)."' value='".htmlentities($value)."'>";
        }
        echo  "</form>"
                ."<script language='JavaScript'>"
                ."document.webpayForm.submit();"
                ."</script>";
    }
}

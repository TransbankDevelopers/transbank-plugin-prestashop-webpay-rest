<?php
require_once(dirname(__FILE__).'../../../../../config/config.inc.php');
if (!defined('_PS_VERSION_')) exit;

require_once(_PS_MODULE_DIR_.'webpay/libwebpay/TransbankSdkWebpay.php');
require_once(_PS_MODULE_DIR_.'webpay/libwebpay/LogHandler.php');
require_once(_PS_MODULE_DIR_.'webpay/libwebpay/Utils.php');

class WebPayPaymentModuleFrontController extends ModuleFrontController {

    public function initContent() {

        $this->ssl = true;
        $this->display_column_left = false;
        parent::initContent();

        $cart = $this->context->cart;

        $log = new LogHandler();

        $order = new Order(Order::getOrderByCartId($cart->id));

        $config = array(
            "MODO" => Configuration::get('WEBPAY_AMBIENT'),
            "API_KEY" => Configuration::get('WEBPAY_APIKEY'),
            "COMMERCE_CODE" => Configuration::get('WEBPAY_STOREID')
        );

        $products = $cart->getProducts();
        $itemsId = array();
        foreach ($products as $product) {
            $itemsId[] = (int)$product['id_product'];
        }

        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        $buyOrder = $cart->id;
        $sessionId = uniqid();

        //patch for error with parallels carts
        $dataPaymentHash = $amount . $buyOrder. json_encode($itemsId);
        $paymentHash = md5($dataPaymentHash);

        $url = Context::getContext()->link->getModuleLink('webpay', 'validate', array(), true);
        $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . ('ph_=' . $paymentHash);
        $returnUrl = $url;
        $finalUrl = $url;

        $transbankSdkWebpay = new TransbankSdkWebpay($config);
        $result = $transbankSdkWebpay->createTransaction(round($amount), $sessionId, $buyOrder, $returnUrl);

        if (isset($result["token_ws"])) {

            $date_tx_hora = date('H:i:s');
            $date_tx_fecha = date('d-m-Y');

            Context::getContext()->cookie->__set('PAYMENT_OK', 'WAITING');
            Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', '');
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXRESPTEXTO', '');
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TOTALPAGO', round($amount));
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ITEMS_ID', json_encode($itemsId));
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ACCDATE', '');
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ORDENCOMPRA', $buyOrder);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_HORA', $date_tx_hora);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_FECHA', $date_tx_fecha);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROTARJETA', '');
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_AUTCODE', '');
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOPAGO', '');
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOCUOTAS', '');
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_RESPCODE', '');
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROCUOTAS', '');

            Context::getContext()->smarty->assign(array(
                'url' => isset($result["url"]) ? $result["url"] : '',
                'token_ws' => isset($result["token_ws"]) ? $result["token_ws"] : '',
                'amount' => round($amount)
            ));

            if (Utils::isPrestashop_1_6()) {
                $this->setTemplate('payment_execution_1.6.tpl');
            } else {
                $this->setTemplate('module:webpay/views/templates/front/payment_execution.tpl');
            }

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

            $date_tx_hora = date('H:i:s');
            $date_tx_fecha = date('d-m-Y');

            $error = isset($result['error']) ? $result['error'] : '';
            $detail = isset($result['detail']) ? $result['detail'] : '';

            $WEBPAY_RESULT_CODE = 500;
            $WEBPAY_RESULT_DESC = $error . ', ' . $detail;
            $WEBPAY_VOUCHER_ORDENCOMPRA = 0;
            $WEBPAY_VOUCHER_TXDATE_HORA = $date_tx_hora;
            $WEBPAY_VOUCHER_TXDATE_FECHA = $date_tx_fecha;

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
}

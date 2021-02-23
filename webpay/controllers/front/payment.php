<?php

use PrestaShop\Module\WebpayPlus\Helpers\WebpayPlusFactory;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_.'webpay/src/Model/TransbankWebpayRestTransaction.php';
require_once _PS_MODULE_DIR_.'webpay/libwebpay/TransbankSdkWebpay.php';
require_once _PS_MODULE_DIR_.'webpay/libwebpay/LogHandler.php';
require_once _PS_MODULE_DIR_.'webpay/libwebpay/Utils.php';

class WebPayPaymentModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ssl = true;
        $this->display_column_left = false;
        $this->display_footer = false;
        parent::initContent();

        $cart = $this->context->cart;

        $webpay = WebpayPlusFactory::create();

        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        $amount = round($amount); // for CLP it should alway be a int
        $buyOrder = $cart->id;
        $sessionId = uniqid();

        //patch for error with parallels carts

        $cartId = Context::getContext()->cart->id;
        $recoverQueryParams = ['token_cart' => md5(_COOKIE_KEY_.'recover_cart_'.$cartId), 'recover_cart' => $cartId];
        $returnUrl = Context::getContext()->link->getModuleLink('webpay', 'validate', $recoverQueryParams, true);
        // $finalUrl = Context::getContext()->link->getModuleLink('webpay', 'validate', array_merge($recoverQueryParams, ['final' => true]), true);

        $result = $webpay->createTransaction($amount, $sessionId, $buyOrder, $returnUrl);

        if (isset($result['token_ws'])) {
            $transaction = new TransbankWebpayRestTransaction();
            $transaction->amount = $amount;
            $transaction->cart_id = (int) $cart->id;
            $transaction->buy_order = 'Order:'.$buyOrder;
            $transaction->session_id = $sessionId;
            $transaction->token = $result['token_ws'];
            $transaction->status = TransbankWebpayRestTransaction::STATUS_INITIALIZED;
            $transaction->created_at = date('Y-m-d H:i:s');
            $transaction->shop_id = (int) Context::getContext()->shop->id;
            $transaction->currency_id = (int) Context::getContext()->cart->id_currency;
            $saved = $transaction->save();
            if (!$saved) {
                (new LogHandler())->logError('Could not create record on webpay_transactions database');

                return $this->setErrorTemplate(['error' => 'No se pudo crear la transacción en la tabla webpay_transactions']);
            }
            $this->setRedirectionTemplate($result, $amount);
        } else {
            $this->setErrorTemplate($result);
        }
    }

    /**
     * @param array $result
     * @param $amount
     */
    protected function setRedirectionTemplate(array $result, $amount)
    {
        Context::getContext()->smarty->assign([
            'url'      => isset($result['url']) ? $result['url'] : '',
            'token_ws' => $result['token_ws'],
            'amount'   => round($amount),
        ]);

        if (Utils::isPrestashop_1_6()) {
            $this->setTemplate('payment_execution_1.6.tpl');
        } else {
            $this->setTemplate('module:webpay/views/templates/front/payment_execution.tpl');
        }
    }

    /**
     * @param $cart
     * @param $amount
     * @param array $result
     */
    protected function setErrorTemplate(array $result)
    {
        $date_tx_hora = date('H:i:s');
        $date_tx_fecha = date('d-m-Y');

        $error = isset($result['error']) ? $result['error'] : '';
        $detail = isset($result['detail']) ? $result['detail'] : '';

        (new LogHandler())->logError('No se pudo inicializar el pago: '.$detail);

        Context::getContext()->smarty->assign([
            'WEBPAY_RESULT_CODE'          => 500,
            'WEBPAY_RESULT_DESC'          => $error.' ('.$detail.')',
            'WEBPAY_VOUCHER_ORDENCOMPRA'  => 0,
            'WEBPAY_VOUCHER_TXDATE_HORA'  => $date_tx_hora,
            'WEBPAY_VOUCHER_TXDATE_FECHA' => $date_tx_fecha,
        ]);

        if (Utils::isPrestashop_1_6()) {
            $this->setTemplate('payment_error_1.6.tpl');
        } else {
            $this->setTemplate('module:webpay/views/templates/front/payment_error.tpl');
        }
    }
}

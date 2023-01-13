<?php

use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\WebpayPlusFactory;
use PrestaShop\Module\WebpayPlus\Utils\Utils;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

class WebPayWebpayplusPaymentModuleFrontController extends BaseModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        if($this->getDebugActive()==1){
            $this->logInfo('B.1. Iniciando medio de pago Webpay Plus');
        }

        $cart = $this->getCartFromContext();
        if($this->getDebugActive()==1){
            $this->cartToLog($cart);
        }
        $cartId = $cart->id;
        $webpay = WebpayPlusFactory::create();

        $amount = $this->getOrderTotalRound($cart); 
        $buyOrder = 'Order:'.$cartId;
        $sessionId = uniqid();

        //patch for error with parallels carts
        $recoverQueryParams = ['token_cart' => md5(_COOKIE_KEY_.'recover_cart_'.$cartId), 'recover_cart' => $cartId];
        $returnUrl = Context::getContext()->link->getModuleLink('webpay', 'webpaypluspaymentvalidate', $recoverQueryParams, true);

        if($this->getDebugActive()==1){
            $this->logInfo('B.2. Preparando datos antes de crear la transacción en Transbank');
            $this->logInfo('amount: '.$amount.', sessionId: '.$sessionId.', buyOrder: '.$buyOrder.', returnUrl: '.$returnUrl);
        }

        $result = $webpay->createTransaction($amount, $sessionId, $buyOrder, $returnUrl);

        if($this->getDebugActive()==1){
            $this->logInfo('B.3. Transacción creada en Transbank');
            $this->logInfo(json_encode($result));
        }

        if (isset($result['token_ws'])) {
            if($this->getDebugActive()==1){
                $this->logInfo('B.4. Preparando datos antes de crear la transacción en la tabla webpay_transactions');
            }
            $saved = $this->createTransbankWebpayRestTransaction($webpay, $sessionId, $cartId, $cart->id_currency, $result['token_ws'], $buyOrder, $amount);
            if (!$saved) {
                $msg = 'No se pudo crear la transacción en la tabla webpay_transactions';
                $this->logError($msg);
                return $this->setErrorTemplate(['error' => $msg]);
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
            'amount'   => $amount,
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

        $this->logError('No se pudo inicializar el pago: '.$detail);

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

    private function createTransbankWebpayRestTransaction($webpay, $sessionId, $cartId, $currencyId, $token, $buyOrder, $amount){
        $transaction = new TransbankWebpayRestTransaction();
        $transaction->amount = $amount;
        $transaction->cart_id = (int) $cartId;
        $transaction->buy_order = $buyOrder;
        $transaction->session_id = $sessionId;
        $transaction->token = $token;
        $transaction->status = TransbankWebpayRestTransaction::STATUS_INITIALIZED;
        $transaction->created_at = date('Y-m-d H:i:s');
        $transaction->shop_id = (int) Context::getContext()->shop->id;
        $transaction->currency_id = (int) $currencyId;

        $transaction->commerce_code =  $webpay->getCommerceCode();
        $transaction->environment = $webpay->getEnviroment();
        $transaction->product = TransbankWebpayRestTransaction::PRODUCT_WEBPAY_PLUS;
        return $transaction->save();
    }
    
}

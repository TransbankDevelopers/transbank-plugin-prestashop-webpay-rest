<?php

use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\WebpayPlusFactory;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayLog;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;

/**
 * Class WebPayWebpayplusPaymentModuleFrontController.
 */
class WebPayWebpayplusPaymentModuleFrontController extends BaseModuleFrontController
{
    use InteractsWithWebpayLog;

    public function initContent()
    {
        parent::initContent();
        $this->logger = TbkFactory::createLogger();
        $this->logWebpayPlusIniciando();

        $randomNumber = uniqid();
        $cart = $this->getCartFromContext();
        $this->logPrintCart($cart);
        $cartId = $cart->id;
        $webpay = WebpayPlusFactory::create();
        $amount = $this->getOrderTotalRound($cart); 
        $buyOrder = 'ps:'.$randomNumber;
        $sessionId = 'ps:sessionId:'.$randomNumber;

        $returnUrl = Context::getContext()->link->getModuleLink('webpay', 'webpaypluspaymentvalidate', [], true);
        $this->logWebpayPlusAntesCrearTx($amount, $sessionId, $buyOrder, $returnUrl);
        try {
            $result = $webpay->createTransaction($amount, $sessionId, $buyOrder, $returnUrl);
        } catch (\Exception $e) {
            $this->logWebpayPlusDespuesCrearTxError($result);
            $this->setPaymentErrorPage($e->getMessage());
        }
        
        if (isset($result['token_ws'])) {
            $this->logWebpayPlusDespuesCrearTx($result);
            $transaction = $this->createTransbankWebpayRestTransaction($webpay, $sessionId, $cartId, $cart->id_currency, $result['token_ws'], $buyOrder, $amount);
            $this->logWebpayPlusDespuesCrearTxEnTabla($transaction);
            $this->setRedirectionTemplate($result, $amount);
        } else {
            $this->logWebpayPlusDespuesCrearTxError($result);
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
        $this->setTemplate('module:webpay/views/templates/front/payment_execution.tpl');
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

        $this->logWebpayPlusAntesCrearTxEnTabla($transaction);
        $saved = $transaction->save();
        if (!$saved) {
            $this->logWebpayPlusDespuesCrearTxEnTablaError($transaction);
            $this->setErrorTemplate('No se pudo crear la transacción en la tabla webpay_transactions');
        }
        return $transaction;
    }
    
}

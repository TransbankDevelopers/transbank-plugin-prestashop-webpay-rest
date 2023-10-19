<?php

use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithCommon;
use PrestaShop\Module\WebpayPlus\Helpers\WebpayPlusFactory;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;

/**
 * Class WebPayWebpayplusPaymentModuleFrontController.
 */
class WebPayWebpayplusPaymentModuleFrontController extends BaseModuleFrontController
{
    use InteractsWithCommon;

    public function initContent()
    {
        parent::initContent();
        $this->logger = TbkFactory::createLogger();
        if($this->isDebugActive()){
            $this->logInfo("B.1. Iniciando medio de pago Webpay Plus");
        }

        $randomNumber = uniqid();
        $cart = $this->getCartFromContext();
        $cartId = $cart->id;
        $webpay = WebpayPlusFactory::create();
        $amount = $this->getOrderTotalRound($cart);
        $buyOrder = 'ps:'.$randomNumber;
        $sessionId = 'ps:sessionId:'.$randomNumber;

        $returnUrl = Context::getContext()->link->getModuleLink('webpay', 'webpaypluspaymentvalidate', [], true);
        if($this->isDebugActive()){
            $this->logInfo("B.2. Preparando datos antes de crear la transacción en Transbank");
            $this->logInfo("amount: {$amount}, sessionId: {$sessionId}, buyOrder: {$buyOrder}
                , returnUrl: {$returnUrl}");
        }
        try {
            $result = $webpay->createTransaction($amount, $sessionId, $buyOrder, $returnUrl);
        } catch (\Exception $e) {
            $this->logError("B.3. Transacción creada con error en Transbank");
            $this->logError(json_encode($result));
            $this->setPaymentErrorPage($e->getMessage());
        }
        if($this->isDebugActive()){
            $this->logInfo("B.3. Transacción creada en Transbank");
            $this->logInfo(json_encode($result));
        }
        $transaction = $this->createTransbankWebpayRestTransaction($webpay, $sessionId, $cartId, $cart->id_currency, $result['token_ws'], $buyOrder, $amount);
        if($this->isDebugActive()){
            $this->logInfo("B.5. Transacción creada en la tabla webpay_transactions");
            $this->logInfo(json_encode($transaction));
        }
        $this->setRedirectionTemplate($result, $amount);
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

        if($this->isDebugActive()){
            $this->logInfo("B.4. Preparando datos antes de crear la transacción en la tabla webpay_transactions");
            $this->logInfo(json_encode($transaction));
        }
        $saved = $transaction->save();
        if (!$saved) {
            $this->logError("B.5. Transacción no se pudo crear en la tabla webpay_transactions => ");
            $this->logError(json_encode($transaction));
            $this->setErrorTemplate('No se pudo crear la transacción en la tabla webpay_transactions');
        }
        return $transaction;
    }
    
}

<?php

use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;

class WebPayOneclickInscriptionModuleFrontController extends BaseModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->logger = TbkFactory::createLogger();
        if($this->getDebugActive()==1){
            $this->logInfo('B.1. Iniciando medio de pago Oneclick');
        }

        $cart = $this->getCartFromContext();
        $customer = $this->getCustomerFromContext();
        if($this->getDebugActive()==1){
            $this->cartToLog($cart);
        }

        if($this->getDebugActive()==1){
            $this->customerToLog($customer);
        }

        $webpay = OneclickFactory::create();

        $userId = $customer->id;
        $userName = $this->generateUsername($userId, $webpay->getCommerceCode());
        $userEmail = $customer->email;
        $returnUrl = Context::getContext()->link->getModuleLink('webpay', 'oneclickinscriptionvalidate', [], true);


        try {
            $resp = $webpay->startInscription($userName, $userEmail, $returnUrl);
        } catch (\Exception $e) {
            $this->setPaymentErrorPage($e->getMessage());
        }

        if (isset($resp['token'])) {
            $ins = new TransbankInscriptions();
            $ins->token = $resp['token'];
            $ins->username = $userName;
            $ins->email = $userEmail;
            $ins->user_id = $userId;
            $ins->pay_after_inscription = false;
            $ins->from = 'checkout';
            $ins->status = TransbankInscriptions::STATUS_INITIALIZED;
            $ins->environment = $webpay->getEnviroment();
            $ins->commerce_code = $webpay->getCommerceCode();
            $ins->order_id = $this->module->currentOrder;//importante para recuperar la orden en curso y el carro en curso
            $saved = $ins->save();
            if (!$saved) {
                $this->logError('Could not create record on transbank_inscriptions database');
                $this->setPaymentErrorPage('No se pudo crear la transacción en la tabla transbank_inscriptions');
            }
            $this->setRedirectionTemplate($resp, $this->getOrderTotalRound($cart));
        } else {
            $this->setErrorTemplate($resp);
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
            'token_ws' => $result['token'],
            'amount'   => round($amount)
        ]);
        $this->setTemplate('module:webpay/views/templates/front/oneclick_inscription_execution.tpl');
    }

    private function generateUsername($userId, $commerceCode){
        return 'PS:'.$commerceCode.':'.$userId.':'.uniqid();
    }

}

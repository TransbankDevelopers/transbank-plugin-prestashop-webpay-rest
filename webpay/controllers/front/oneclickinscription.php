<?php

use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use PrestaShop\Module\WebpayPlus\Utils\Utils;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;

class WebPayOneclickInscriptionModuleFrontController extends BaseModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

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

        $recoverQueryParams = [];
        //$recoverQueryParams = ['token_cart' => md5(_COOKIE_KEY_.'recover_cart_'.$cartId), 'recover_cart' => $cartId];
        $webpay = OneclickFactory::create();

        $userId = $customer->id;
        $userName = $this->generateUsername($userId, $webpay->getCommerceCode());
        $userEmail = $customer->email;
        $returnUrl = Context::getContext()->link->getModuleLink('webpay', 'oneclickinscriptionvalidate', $recoverQueryParams, true);


        $resp = $webpay->startInscription($userName, $userEmail, $returnUrl);

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
                return $this->setErrorTemplate(['error' => 'No se pudo crear la transacción en la tabla transbank_inscriptions']);
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

        if (Utils::isPrestashop_1_6()) {
            $this->setTemplate('oneclick_inscription_execution_1.6.tpl');
        } else {
            $this->setTemplate('module:webpay/views/templates/front/oneclick_inscription_execution.tpl');
        }
    }

    

    private function generateUsername($userId, $commerceCode){
        return 'PS:'.$commerceCode.':'.$userId.':'.uniqid();
    }


    /**
     * @param array $result
     */
    protected function setErrorTemplate(array $result)
    {
        $date_tx_hora = date('H:i:s');
        $date_tx_fecha = date('d-m-Y');

        $error = isset($result['error']) ? $result['error'] : '';
        $detail = isset($result['detail']) ? $result['detail'] : '';

        $this->logError($error.' ('.$detail.')');

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

<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;
use Transbank\Webpay\Oneclick;
use Transbank\Webpay\Options;
use PrestaShop\Module\WebpayPlus\Helpers\SqlHelper;
use Configuration;
use Tools;
use Media;
use PrestaShop\Module\WebpayPlus\Utils\StringUtils;
 
/**
 * Trait InteractsWithOneclick.
 */
trait InteractsWithOneclick
{
    protected function getGroupOneclickPaymentOption($base, $context)
    {
        if (!$context->customer->isLogged()){
            return [];
        }
        if ($this->getOneclickActive()!=1){
            return [];
        }
        if ($this->getCountCardsByUserId($this->getUserId($context)) > 0){
            return $this->getOneclickPaymentOption($base, $context);
        }
        return [
            $this->getNewOneclickPaymentOption($base, $context)
        ];
    }

    protected function getOneclickPaymentOption($base, $context)
    {
        $result = [];
        $paymentController = $context->link->getModuleLink($base->name, 'oneclickpaymentvalidate', array(), true);
        $cards = $this->getCardsByUserId($this->getUserId($context));
        foreach($cards as $card){
            $po = new PaymentOption();
            $cardNumber = $card['card_number'];
            $environment = $card['environment']=='TEST' ? '[TEST] ' : '';
            array_push($result,
                $po->setCallToActionText($environment.$card['card_type'].' terminada en '.substr($cardNumber,- 4, 4))
                    ->setAction($paymentController)
                    ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/oneclick_small.png'))
                    ->setInputs([
                        'token' => [
                            'name' =>'inscriptionId',
                            'type' =>'hidden',
                            'value' => $card['id']
                        ],
                    ])
                );
        }

        array_push($result, $this->getOneclickInscriptionOption($base, $context, 'Usar un nuevo método de pago'));
        return $result;
    }

    protected function getNewOneclickPaymentOption($base, $context)
    {
        return $this->getOneclickInscriptionOption($base, $context, 'Inscribe tu tarjeta de crédito, débito o prepago y luego paga con un solo click a través de Webpay Oneclick');
    }

    protected function getOneclickInscriptionOption($base, $context, $description)
    {
        $po = new PaymentOption();
        $controller = $context->link->getModuleLink($base->name, 'oneclickinscription', array(), true);
        return $po->setCallToActionText($description)
            ->setAction($controller)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/oneclick_small.svg'))
            ->setInputs([
                'token' => [
                    'name' =>'inscriptionId',
                    'type' =>'hidden',
                    'value' => 0
                ],
            ]);
    }

    protected function getCardsByUserId($userId)
    {
        $r = SqlHelper::executeSql('SELECT * FROM '._DB_PREFIX_.TransbankInscriptions::TABLE_NAME.' WHERE `status` = "'.TransbankInscriptions::STATUS_COMPLETED.'" and `user_id` = "'.pSQL($userId).'"');
        if (!isset($r)){
            return [];
        }
        return $r;
    }

    protected function getCountCardsByUserId($userId)
    {
        return SqlHelper::getValue('SELECT count(1) FROM '._DB_PREFIX_.TransbankInscriptions::TABLE_NAME.' WHERE `status` = "'.TransbankInscriptions::STATUS_COMPLETED.'" and `user_id` = "'.pSQL($userId).'"');
    }

    protected function getUserId($context){
        if ($context->customer->isLogged()) {
            return $context->customer->id;
        }
        return null;
    }

    protected function loadDefaultConfigurationOneclick()
    {
        $oneclickEnviroment = $this->getDefaultOneclickEnvironment();
        /* Si existe configuración de producción se copiara */
        if (isset($oneclickEnviroment) && $oneclickEnviroment == Options::ENVIRONMENT_PRODUCTION){
            $oneclickActive = $this->getOneclickActive();
            $oneclickActive = isset($oneclickActive) ? $oneclickActive : 1;
            $oneclickMallCommerceCode = $this->getOneclickMallCommerceCode();
            $oneclickChildCommerceCode = $this->getOneclickChildCommerceCode();
            $oneclickApikey = $this->getOneclickApiKey();
            $oneclickDefaultOrderStateIdAfterPayment = $this->getDefaultOneclickOrderAfterPayment();
            //si alguno de los datos falta se resetea
            if (
                StringUtils::isNotBlankOrNull($oneclickMallCommerceCode)
                && StringUtils::isNotBlankOrNull($oneclickChildCommerceCode)
                && StringUtils::isNotBlankOrNull($oneclickApikey)
                && StringUtils::isNotBlankOrNull($oneclickDefaultOrderStateIdAfterPayment)
            ){
                $this->setOneclickActive($oneclickActive);
                $this->setOneclickMallCommerceCode($oneclickMallCommerceCode);
                $this->setOneclickChildCommerceCode($oneclickChildCommerceCode);
                $this->setOneclickApiKey($oneclickApikey);
                $this->setOneclickOrderAfterPayment($oneclickDefaultOrderStateIdAfterPayment);
                $this->logOneclickInstallConfigLoad($oneclickMallCommerceCode, $oneclickChildCommerceCode, $oneclickDefaultOrderStateIdAfterPayment);
            }
            else{
                $this->loadDefaultOneclick();
                $this->logOneclickInstallConfigLoadDefaultPorIncompleta();               
            }
        }
        else{
            $this->loadDefaultOneclick();
            $this->logOneclickInstallConfigLoadDefault();
        }
    }

    protected function oneclickUpdateSettings(){
        $theEnvironmentChanged = false;
        if (Tools::getIsset('btn_oneclick_update')) {
            if ($this->getFormOneclickEnvironment() !=  $this->getOneclickEnvironment()) {
                $theEnvironmentChanged = true;
            }
            $this->setOneclickMallCommerceCode($this->getFormOneclickMallCommerceCode());
            $this->setOneclickChildCommerceCode($this->getFormOneclickChildCommerceCode());
            $this->setOneclickApiKey($this->getFormOneclickApiKey());
            $this->setOneclickEnvironment($this->getFormOneclickEnvironment());
            $this->setOneclickOrderAfterPayment($this->getDefaultWebpayOrderAfterPayment());
        } 
        return $theEnvironmentChanged;
    }

    protected function getFormOneclickMallCommerceCode(){
        return trim(Tools::getValue('form_oneclick_mall_commerce_code'));
    }

    protected function getFormOneclickChildCommerceCode(){
        return trim(Tools::getValue('form_oneclick_child_commerce_code'));
    }

    protected function getFormOneclickApiKey(){
        return trim(Tools::getValue('form_oneclick_api_key'));
    }

    protected function getFormOneclickEnvironment(){
        return Tools::getValue('form_oneclick_environment');
    }

    protected function getFormOneclickOrderAfterPayment(){
        return (int)Tools::getValue('form_oneclick_order_after_payment');
    }

    protected function getOneclickMallCommerceCode(){
        return Configuration::get('ONECLICK_MALL_COMMERCE_CODE');
    }

    protected function getOneclickChildCommerceCode(){
        return Configuration::get('ONECLICK_CHILD_COMMERCE_CODE');
    }

    protected function getOneclickApiKey(){
        return Configuration::get('ONECLICK_API_KEY');
    }

    protected function getOneclickEnvironment(){
        return Configuration::get('ONECLICK_ENVIRONMENT');
    }

    protected function getOneclickOrderAfterPayment(){
        return Configuration::get('ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
    }

    protected function getOneclickActive(){
        return Configuration::get('ONECLICK_ACTIVE');
    }

    protected function setOneclickApiKey($value){
        return Configuration::updateValue('ONECLICK_API_KEY', $value);
    }

    protected function setOneclickMallCommerceCode($value){
        return Configuration::updateValue('ONECLICK_MALL_COMMERCE_CODE', $value);
    }

    protected function setOneclickChildCommerceCode($value){
        return Configuration::updateValue('ONECLICK_CHILD_COMMERCE_CODE', $value);
    }

    protected function setOneclickEnvironment($value){
        return Configuration::updateValue('ONECLICK_ENVIRONMENT', $value);
    }

    protected function setOneclickOrderAfterPayment($value){
        return Configuration::updateValue('ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT', $value);
    }

    protected function setOneclickActive($value){
        return Configuration::updateValue('ONECLICK_ACTIVE', $value);
    }

    protected function getDefaultOneclickMallCommerceCode(){
        return Oneclick::DEFAULT_COMMERCE_CODE;
    }

    protected function getDefaultOneclickChildCommerceCode(){
        return Oneclick::DEFAULT_CHILD_COMMERCE_CODE_1;
    }

    protected function getDefaultOneclickApiKey(){
        return Oneclick::DEFAULT_API_KEY;
    }

    protected function getDefaultOneclickEnvironment(){
        return  Options::DEFAULT_INTEGRATION_TYPE;
    }

    protected function getDefaultOneclickOrderAfterPayment(){
        return Configuration::get('PS_OS_PREPARATION');
    }

    protected function getDefaultOneclickActive(){
        return 1;
    }

    protected function loadDefaultOneclick()
    {
        $this->setOneclickActive($this->getDefaultOneclickActive());
        $this->setOneclickMallCommerceCode($this->getDefaultOneclickMallCommerceCode());
        $this->setOneclickChildCommerceCode($this->getDefaultOneclickChildCommerceCode());
        $this->setOneclickApiKey($this->getDefaultOneclickApiKey());
        $this->setOneclickEnvironment($this->getDefaultOneclickEnvironment());
        $this->setOneclickOrderAfterPayment($this->getDefaultOneclickOrderAfterPayment());
    }

    protected function getOneclickOkStatus(){
        $OKStatus = Configuration::get('ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
        if ($OKStatus === '0') {
            $OKStatus = Configuration::get('PS_OS_PREPARATION');
        }
        return $OKStatus;
    }

    protected function configOneclickIsOk(){
        $oneclickEnviroment = $this->getDefaultOneclickEnvironment();
        $oneclickMallCommerceCode = $this->getOneclickMallCommerceCode();
        $oneclickChildCommerceCode = $this->getOneclickChildCommerceCode();
        $oneclickApikey = $this->getOneclickApiKey();
        $oneclickDefaultOrderStateIdAfterPayment = $this->getDefaultOneclickOrderAfterPayment();

        if (
            StringUtils::isNotBlankOrNull($oneclickEnviroment) 
            && StringUtils::isNotBlankOrNull($oneclickMallCommerceCode)
            && StringUtils::isNotBlankOrNull($oneclickChildCommerceCode)
            && StringUtils::isNotBlankOrNull($oneclickApikey)
            && StringUtils::isNotBlankOrNull($oneclickDefaultOrderStateIdAfterPayment)
        ){
            return true;
        }
        return false;
    }


}

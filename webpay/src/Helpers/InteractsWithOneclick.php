<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;
use Configuration;
use Tools;
use Transbank\Webpay\Oneclick;
use Transbank\Webpay\Options;

/**
 * Trait InteractsWithOneclick.
 */
trait InteractsWithOneclick
{
    public function getGroupOneclickPaymentOption($base, $context)
    {
        if ($context->customer->isLogged()) {
            return $this->getOneclickPaymentOption($base, $context);
        }
        else {
            return [
                $this->getNewOneclickPaymentOption($base, $context)
            ];
        }
    }

    public function getOneclickPaymentOption($base, $context)
    {
        $result = [];

        $paymentController = $context->link->getModuleLink($base->name, 'oneclickpayvalidate', array(), true);
        $inscriptionsController = $context->link->getModuleLink($base->name, 'oneclickinscription', array(), true);

        $r = $this->getCardsByUserId($this->getUserId($context));
        foreach($r as $row){
            $po = new PaymentOption();
            $cardNumber = $row['card_number'];
            $environment = $row['environment']=='TEST' ? '[TEST] ' : '';
            array_push($result,
                $po->setCallToActionText($environment.$row['card_type'].' terminada en '.substr($cardNumber,- 4, 4))
                    ->setAction($paymentController)
                    ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/oneclick_80px.svg'))
                    ->setInputs([
                        'token' => [
                            'name' =>'inscriptionId',
                            'type' =>'hidden',
                            'value' => $row['id']
                        ],
                    ])
                );
        }

        $po = new PaymentOption();
        array_push($result,
                $po->setCallToActionText('Usar un nuevo método de pago')
                    ->setAction($inscriptionsController)
                    ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/oneclick_80px.svg'))
                    ->setInputs([
                        'token' => [
                            'name' =>'inscriptionId',
                            'type' =>'hidden',
                            'value' => 0
                        ],
                    ])
                );
        return $result;
    }

    public function getNewOneclickPaymentOption($base, $context)
    {
        $po = new PaymentOption();
        $controller = $context->link->getModuleLink($base->name, 'oneclickinscription', array(), true);

        return $po->setCallToActionText('Inscribe tu tarjeta de crédito, débito o prepago y luego paga con un solo click a través de Webpay Oneclick')
            ->setAction($controller)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/oneclick_80px.svg'));
    }

    public function getCardsByUserId($userId)
    {
        $r = null;
        try {
            $sql = 'SELECT * FROM '._DB_PREFIX_.TransbankInscriptions::TABLE_NAME.' WHERE `status` = "'.TransbankInscriptions::STATUS_COMPLETED.'" and `user_id` = "'.pSQL($userId).'"';
            $r = \Db::getInstance()->ExecuteS($sql);
        }
        catch(\Exception $e) {

        }
        if (!isset($r)){
            return [];
        }
        return $r;
    }

    public function getUserId($context){
        if ($context->customer->isLogged()) {
            return $context->customer->id;
        }
        return null;
    }

    private function loadDefaultConfigurationOneclick()
    {
        /* Si ya existe alguna configuracion la copiara */
        $oneclickEnviroment = $this->getDefaultOneclickEnvironment();
        $oneclickMallCommerceCode = $this->getOneclickMallCommerceCode();
        $oneclickChildCommerceCode = $this->getOneclickChildCommerceCode();
        $oneclickApikey = $this->getOneclickApiKey();
        $oneclickDefaultOrderStateIdAfterPayment = $this->getDefaultOneclickOrderAfterPayment();

        $oneclickEnviroment = isset($oneclickEnviroment) ? $oneclickEnviroment : Options::DEFAULT_INTEGRATION_TYPE;
        if ($oneclickEnviroment == Options::DEFAULT_INTEGRATION_TYPE){/* Si es el entorno de integración y no existen parámetros, cargamos los valores por defecto */
            $oneclickMallCommerceCode = $this->getDefaultOneclickMallCommerceCode();
            $oneclickChildCommerceCode = $this->getDefaultOneclickChildCommerceCode();
            $oneclickApikey = $this->getDefaultOneclickApiKey();
            $oneclickDefaultOrderStateIdAfterPayment = $this->getDefaultOneclickOrderAfterPayment();
        }

        $this->setOneclickEnvironment($oneclickEnviroment);
        $this->setOneclickMallCommerceCode($oneclickMallCommerceCode);
        $this->setOneclickChildCommerceCode($oneclickChildCommerceCode);
        $this->setOneclickApiKey($oneclickApikey);
        $this->setOneclickOrderAfterPayment($oneclickDefaultOrderStateIdAfterPayment);
    }

    public function oneclickUpdateSettings(){
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

}

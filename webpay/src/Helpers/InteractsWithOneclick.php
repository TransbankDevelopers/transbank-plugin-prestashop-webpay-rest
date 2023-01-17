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

/**
 * Trait InteractsWithOneclick.
 */
trait InteractsWithOneclick
{
    public function getGroupOneclickPaymentOption($base, $context)
    {
        if (!$context->customer->isLogged()){
            return [];
        }
        if ($this->getCountCardsByUserId($this->getUserId($context)) > 0){
            return $this->getOneclickPaymentOption($base, $context);
        }
        return [
            $this->getNewOneclickPaymentOption($base, $context)
        ];
    }

    public function getOneclickPaymentOption($base, $context)
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
                    ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/oneclick_80px.svg'))
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

    public function getNewOneclickPaymentOption($base, $context)
    {
        return $this->getOneclickInscriptionOption($base, $context, 'Inscribe tu tarjeta de crédito, débito o prepago y luego paga con un solo click a través de Webpay Oneclick');
    }

    public function getOneclickInscriptionOption($base, $context, $description)
    {
        $po = new PaymentOption();
        $controller = $context->link->getModuleLink($base->name, 'oneclickinscription', array(), true);
        return $po->setCallToActionText($description)
            ->setAction($controller)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/oneclick_80px.svg'))
            ->setInputs([
                'token' => [
                    'name' =>'inscriptionId',
                    'type' =>'hidden',
                    'value' => 0
                ],
            ]);
    }

    public function getCardsByUserId($userId)
    {
        $r = SqlHelper::executeSql('SELECT * FROM '._DB_PREFIX_.TransbankInscriptions::TABLE_NAME.' WHERE `status` = "'.TransbankInscriptions::STATUS_COMPLETED.'" and `user_id` = "'.pSQL($userId).'"');
        if (!isset($r)){
            return [];
        }
        return $r;
    }

    public function getCountCardsByUserId($userId)
    {
        return SqlHelper::getValue('SELECT count(1) FROM '._DB_PREFIX_.TransbankInscriptions::TABLE_NAME.' WHERE `status` = "'.TransbankInscriptions::STATUS_COMPLETED.'" and `user_id` = "'.pSQL($userId).'"');
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

<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Configuration;
use Tools;

/**
 * Trait InteractsWithOneclick.
 */
trait InteractsWithOneclick
{
    public function getOneclickPaymentOption($base, $context)
    {
        $WPOption = new PaymentOption();
        $paymentController = $context->link->getModuleLink($base->name, 'payment', array(), true);

        return $WPOption->setCallToActionText('Permite el pago de productos y/o servicios, con tarjetas de crédito, débito y prepago a través de Oneclick')
            ->setAction($paymentController)
            ->setLogo('/modules/webpay/images/webpay_plus.svg');
    }

    private function loadDefaultConfigurationOneclick()
    {
        $this->setOneclickMallCommerceCode($this->getDefaultOneclickMallCommerceCode());
        $this->setOneclickChildCommerceCode($this->getDefaultOneclickChildCommerceCode());
        $this->setOneclickApiKey($this->getDefaultOneclickApiKey());
        $this->setOneclickEnvironment($this->getDefaultOneclickEnvironment());
        $this->setOneclickOrderAfterPayment(Configuration::get('PS_OS_PREPARATION'));
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
            $this->setOneclickOrderAfterPayment($this->getFormOneclickOrderAfterPayment());
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
        return \Transbank\Webpay\Oneclick::DEFAULT_COMMERCE_CODE;
    }

    protected function getDefaultOneclickChildCommerceCode(){
        return \Transbank\Webpay\Oneclick::DEFAULT_CHILD_COMMERCE_CODE_1;
    }

    protected function getDefaultOneclickApiKey(){
        return \Transbank\Webpay\Oneclick::DEFAULT_API_KEY;
    }

    protected function getDefaultOneclickEnvironment(){
        return "TEST";
    }
}

<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Media;

trait InteractsWithWebpay
{
    protected function getWebpayPaymentOption($base, $context)
    {
        $tbkWebpayplus = TbkFactory::createTbkWebpayplusService($this->getCurrentStoreId());
        if (!$tbkWebpayplus->isActive()){
            return [];
        }
        $WPOption = new PaymentOption();
        $paymentController = $context->link->getModuleLink($base->name, 'webpaypluspayment', array(), true);

        return [ $WPOption->setCallToActionText('Permite el pago de productos y/o servicios, con tarjetas de crédito, débito y prepago a través de Webpay Plus')
            ->setAction($paymentController)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/wpplus_small.png')) ];
    }

}

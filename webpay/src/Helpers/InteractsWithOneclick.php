<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Media;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;

trait InteractsWithOneclick
{
    protected function getGroupOneclickPaymentOption($base, $context)
    {
        if (!$context->customer->isLogged()){
            return [];
        }
        $tbkOneclick = TbkFactory::createTbkOneclickService($this->getCurrentStoreId());
        if (!$tbkOneclick->isActive()){
            return [];
        }
        if ($tbkOneclick->getCountInscriptionByUserId($this->getUserId($context)) > 0){
            return $this->getOneclickPaymentOption($base, $context, $tbkOneclick);
        }
        return [
            $this->getNewOneclickPaymentOption($base, $context)
        ];
    }

    protected function getOneclickPaymentOption($base, $context, $tbkOneclick)
    {
        $result = [];
        $paymentController = $context->link->getModuleLink($base->name, 'oneclickpaymentvalidate', 
            array(), true);
        $cards = $tbkOneclick->getListInscriptionByUserId($this->getUserId($context));
        foreach($cards as $card){
            $po = new PaymentOption();
            $cardNumber = $card['card_number'];
            $environment = $card['environment']=='TEST' ? '[TEST] ' : '';
            array_push($result,
                $po->setCallToActionText(
                        $environment.$card['card_type'].' terminada en '.substr($cardNumber,- 4, 4))
                    ->setAction($paymentController)
                    ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . 
                        $this->name . '/views/img/oneclick_small.png'))
                    ->setInputs([
                        'token' => [
                            'name' =>'username',
                            'type' =>'hidden',
                            'value' => $card['username']
                        ],
                    ])
                );
        }

        array_push($result, $this->getOneclickInscriptionOption($base, $context, 
            'Usar un nuevo método de pago'));
        return $result;
    }

    protected function getNewOneclickPaymentOption($base, $context)
    {
        return $this->getOneclickInscriptionOption($base, $context,
            "Inscribe tu tarjeta de crédito, débito o prepago y 
                luego paga con un solo click a través de Webpay Oneclick");
    }

    protected function getOneclickInscriptionOption($base, $context, $description)
    {
        $po = new PaymentOption();
        $controller = $context->link->getModuleLink($base->name, 'oneclickinscription', array(), true);
        return $po->setCallToActionText($description)
            ->setAction($controller)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/oneclick_small.png'))
            ->setInputs([
                'token' => [
                    'name' =>'username',
                    'type' =>'hidden',
                    'value' => 0
                ],
            ]);
    }

    protected function getUserId($context){
        if ($context->customer->isLogged()) {
            return $context->customer->id;
        }
        return null;
    }

}

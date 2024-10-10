<?php

namespace PrestaShop\Module\WebpayPlus\Grid;

use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

class TransactionsProductChoiceProvider implements FormChoiceProviderInterface
{
    public function getChoices()
    {
        return [
            'Webpay Plus' => TransbankWebpayRestTransaction::PRODUCT_WEBPAY_PLUS,
            'Webpay Oneclick' => TransbankWebpayRestTransaction::PRODUCT_WEBPAY_ONECLICK
        ];
    }
}

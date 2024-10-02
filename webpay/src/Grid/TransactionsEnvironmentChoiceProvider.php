<?php

namespace PrestaShop\Module\WebpayPlus\Grid;

use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use Transbank\Webpay\Options;

class TransactionsEnvironmentChoiceProvider implements FormChoiceProviderInterface
{
    public function getChoices()
    {
        return [
            'Producción' => Options::ENVIRONMENT_PRODUCTION,
            'Integración' => Options::ENVIRONMENT_INTEGRATION
        ];
    }
}

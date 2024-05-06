<?php

namespace PrestaShop\Module\WebpayPlus\Grid;

use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

class TransactionsStatusChoiceProvider implements FormChoiceProviderInterface
{
    public function getChoices()
    {
        return [
            'Inicializada' => TransbankWebpayRestTransaction::STATUS_INITIALIZED,
            'Fallida' => TransbankWebpayRestTransaction::STATUS_FAILED,
            'Cancelada por el usuario' => TransbankWebpayRestTransaction::STATUS_ABORTED_BY_USER,
            'Aprobada' => TransbankWebpayRestTransaction::STATUS_APPROVED,
        ];
    }
}

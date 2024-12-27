<?php

namespace Transbank\Plugin\Helpers;

class TbkConstants
{
    const PAYMENT_TYPE_CODE = [
        "VD" => "Venta Débito",
        "VN" => "Venta Normal",
        "VC" => "Venta en cuotas",
        "SI" => "3 cuotas sin interés",
        "S2" => "2 cuotas sin interés",
        "NC" => "N cuotas sin interés",
        "VP" => "Venta Prepago"
    ];

    const PAYMENT_TYPE_CREDIT = "Crédito";
    const PAYMENT_TYPE_DEBIT = "Débito";
    const PAYMENT_TYPE_PREPAID = "Prepago";

    const PAYMENT_TYPE = [
        "VD" => self::PAYMENT_TYPE_DEBIT,
        "VN" => self::PAYMENT_TYPE_CREDIT,
        "VC" => self::PAYMENT_TYPE_CREDIT,
        "SI" => self::PAYMENT_TYPE_CREDIT,
        "S2" => self::PAYMENT_TYPE_CREDIT,
        "NC" => self::PAYMENT_TYPE_CREDIT,
        "VP" => self::PAYMENT_TYPE_PREPAID
    ];

    const INSTALLMENT_TYPE = [
        "VC" => "Venta en cuotas",
        "SI" => "3 cuotas sin interés",
        "S2" => "2 cuotas sin interés",
        "NC" => "N cuotas sin interés"
    ];

    const STATUS_DESCRIPTION =  [
        'INITIALIZED' => 'Inicializada',
        'AUTHORIZED' => 'Autorizada',
        'REVERSED' => 'Reversada',
        'FAILED' => 'Fallida',
        'NULLIFIED' => 'Anulada',
        'PARTIALLY_NULLIFIED' => 'Parcialmente anulada',
        'CAPTURED' => 'Capturada',
    ];
    const ECOMMERCE_PRESTASHOP = 'prestashop';
    const REPO_PRESTASHOP = 'TransbankDevelopers/transbank-plugin-prestashop-webpay-rest';
    const REPO_OFFICIAL_PRESTASHOP = 'PrestaShop/PrestaShop';
}

<?php

namespace Transbank\Plugin\Helpers;

class TbkConstans
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
    const ECOMMERCE_PRESTASHOP = 'prestashop';
    const REPO_PRESTASHOP = 'TransbankDevelopers/transbank-plugin-prestashop-webpay-rest';
    const REPO_OFFICIAL_PRESTASHOP = 'PrestaShop/PrestaShop';
}

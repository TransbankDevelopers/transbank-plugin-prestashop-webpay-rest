<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\Module\WebpayPlus\Helpers\TabsHelper;
use PrestaShop\Module\WebpayPlus\Controller\Admin\ConfigureController;
use Language;

/**
 * Trait InteractsWithTabs.
 */
trait InteractsWithTabs
{
    protected function uninstallTab()
    {
        TabsHelper::removeTab('WebPay');
    }

    protected function addTabs($base)
    {
        $base->tabs = [
            [
                'route_name' => 'ps_controller_webpay_configure',
                'class_name' => ConfigureController::TAB_CLASS_NAME,
                'visible' => true,
                'name' => $this->getNames('Configuración Webpay', 'Modules.WebpayPlus.Config'),
                'parent_class_name' => 'AdminParentPayment',
            ],
            [
                'route_name' => 'ps_controller_webpay_transaction_list',
                'class_name' => ConfigureController::TAB_CLASS_NAME.'transactions',
                'visible' => true,
                'name' => $this->getNames('Transacciones Webpay', 'Modules.WebpayPlus.Config'),
                'parent_class_name' => 'AdminParentPayment',
            ]
        ];
    }

    protected function getNames($name, $property)
    {
        $tabNames = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tabNames[$lang['locale']] = $this->trans($name, [], $property, $lang['locale']);
        }
        return $tabNames;
    }
}

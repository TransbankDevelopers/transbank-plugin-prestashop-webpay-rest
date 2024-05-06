<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\Module\WebpayPlus\Helpers\TabsHelper;
use PrestaShop\Module\WebpayPlus\Controller\Admin\ConfigureController;
use PrestaShop\Module\WebpayPlus\Utils\Utils;
use Language;

/**
 * Trait InteractsWithTabs.
 */
trait InteractsWithTabs
{
    protected function installTab()
    {

        if (Utils::isPrestashopEqualOrGreater_1_7_1()) {
            return;
        }
        TabsHelper::removeTab('WebPay');
        TabsHelper::AddTab(
            ConfigureController::TAB_CLASS_NAME,
            $this->getNamesToManualInstall('Configuración Webpay'),
            'WebPay',
            'AdminParentPayment'
        );
        TabsHelper::AddTab(
            ConfigureController::TAB_CLASS_NAME,
            $this->getNamesToManualInstall('Transacciones Webpay'),
            'WebPay',
            'AdminParentPayment'
        );
    }

    protected function uninstallTab()
    {
        TabsHelper::removeTab('WebPay');
    }

    protected function addTabs($base)
    {
        if (!Utils::isPrestashopEqualOrGreater_1_7_1()) {
            return;
        }

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

    protected function getNamesToManualInstall($tabName)
    {
        $tabNames = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tabNames[$lang['id_lang']] = $tabName;
        }
        return $tabNames;
    }
}

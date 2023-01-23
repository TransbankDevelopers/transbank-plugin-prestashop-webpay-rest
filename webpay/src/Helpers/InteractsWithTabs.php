<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\Module\WebpayPlus\Helpers\TabsHelper;
use PrestaShop\Module\WebpayPlus\Controller\Admin\ConfigureController;
use PrestaShop\Module\WebpayPlus\Controller\Admin\OrdersController;
use PrestaShop\Module\WebpayPlus\Utils\Utils;
use Language;
/**
 * Trait InteractsWithTabs.
 */
trait InteractsWithTabs
{
    protected function installTab(){
        
        if (Utils::isPrestashopEqualOrGreater_1_7_1()) {
            return;
        }
        TabsHelper::removeTab('WebPay');
        TabsHelper::AddTab(ConfigureController::TAB_CLASS_NAME, $this->getNamesToManualInstall('Configuración Webpay', 'Modules.WebpayPlus.Config'), 'WebPay', 'AdminParentPayment');
        //TabsHelper::AddTab(OrdersController::TAB_CLASS_NAME, $this->getNamesToManualInstall('Órdenes Webpay', 'Modules.WebpayPlus.Orders'), 'WebPay', 'AdminParentOrders');
    }
    
    protected function uninstallTab()
    {
        TabsHelper::removeTab('WebPay');
    }

    protected function addTabs($base){
        if (!Utils::isPrestashopEqualOrGreater_1_7_1()) {
            return;
        }

        $base->tabs = [
            [   /*'icon' => 'school',*/
                'route_name' => 'ps_controller_webpay_configure',
                'class_name' => ConfigureController::TAB_CLASS_NAME,
                'visible' => true,
                'name' => $this->getNames('Configuración Webpay', 'Modules.WebpayPlus.Config'),
                'parent_class_name' => 'AdminParentPayment',
            ],/*
            [
                'route_name' => 'ps_controller_webpay_orders',
                'class_name' => OrdersController::TAB_CLASS_NAME,
                'visible' => true,
                'name' => $this->getNames('Órdenes Webpay', 'Modules.WebpayPlus.Orders'),
                'parent_class_name' => 'AdminParentOrders',
            ],*/
        ];
    }

    protected function getNames($name, $property){
        $tabNames = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tabNames[$lang['locale']] = $this->trans($name, [], $property, $lang['locale']);
        }
        return $tabNames;
    }

    protected function getNamesToManualInstall($tabName, $property){
        $tabNames = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tabNames[$lang['id_lang']] = $tabName;
        }
        return $tabNames;
    }
}


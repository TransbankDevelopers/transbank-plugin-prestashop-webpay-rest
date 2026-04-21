<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use Tab;
use PrestaShopBundle\Entity\Repository\TabRepository;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class TabsHelper
{
    public static function AddTab($className, $tabName, $moduleName, $parentClassName, $icon = null)
    {
        $tab             = new Tab();
        $tab->active     = 1;
        $tab->class_name = $className;
        $tab->name       = $tabName;
        $tab->id_parent  = (int) static::getTabIdFromClassName($parentClassName);
        $tab->module     = $moduleName;
        if (!is_null($icon)) {
            $tab->icon = $icon;
        }
        $tab->add();
        return $tab;
    }

    public static function removeTab($className)
    {
        $id_tab = (int) static::getTabIdFromClassName($className);
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }
        return true;
    }

    private static function getTabIdFromClassName(string $className): int
    {
        $container = SymfonyContainer::getInstance();

        if ($container !== null) {
            $tabRepository = $container->get('webpay.repository.tab_repository');
            return (int) $tabRepository->findOneIdByClassName($className);
        }

        return (int) Tab::getIdFromClassName($className);
    }
}

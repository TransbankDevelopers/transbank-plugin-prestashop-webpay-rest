<?php
namespace PrestaShop\Module\WebpayPlus\Helpers;

use Tab;

class TabsHelper
{
    public static function AddTab($className, $tabName, $moduleName, $parentClassName, $icon = null)
    {
        $tab             = new Tab();
        $tab->active     = 1;
        $tab->class_name = $className;
        $tab->name       = $tabName;
        $tab->id_parent = (int)Tab::getIdFromClassName($parentClassName);
        $tab->module    = $moduleName;
        if (!is_null($icon)) {
            $tab->icon = $icon;
        }
        $tab->add();
        return $tab;
    }

    public static function removeTab($className)
    {
        $id_tab = (int)Tab::getIdFromClassName($className);
        if ($id_tab == true){
            $tab    = new Tab($id_tab);
            $tab->delete();
        }
        /*
        if ($tab->name !== '') {
            $tab->delete();
        }*/

        return true;
    }

    
}

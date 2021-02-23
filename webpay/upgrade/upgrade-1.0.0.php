<?php
/**
 * 2007-2017 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\Module\WebpayPlus\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_0($module)
{
    try {
        $installer = new Installer();
        $result = $installer->installWebpayOrdersTable();
    } catch (Exception $e) {
        $logger = new LogHandler();
        $logger->logError('Error:  '.$e->getMessage());
    }

    file_put_contents('log.log', Configuration::get('WEBPAY_ENVIRONMENT')."\n", FILE_APPEND);
    if (Configuration::get('WEBPAY_ENVIRONMENT') === 'LIVE') {
        $webpayModule = new WebPay();
        $config = $webpayModule->getConfigForHealthCheck();
        $webpayModule->sendPluginVersion($config);
    }

    return true;
}

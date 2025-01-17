<?php

use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclick;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithTabs;
use PrestaShop\Module\WebpayPlus\Hooks\DisplayAdminOrderSide;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use PrestaShop\Module\WebpayPlus\Hooks\DisplayPaymentReturn;

require_once __DIR__ . '/vendor/autoload.php';

class WebPay extends PaymentModule
{
    use InteractsWithWebpay;
    use InteractsWithOneclick;
    use InteractsWithWebpayDb;
    use InteractsWithTabs;

    protected $_errors = array();
    public $log;
    public $title = 'Pago con tarjetas de crédito o Redcompra';

    private const MODULE_HOOKS = [
        'paymentOptions',
        'paymentReturn',
        'displayBackOfficeHeader',
        'displayHeader',
        'displayPaymentReturn',
        'displayAdminOrderSide'
    ];

    public function __construct()
    {
        $this->name = 'webpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Transbank';
        $this->need_instance = 1;
        $this->bootstrap = true;
        $this->addTabs($this);
        parent::__construct();
        $this->displayName = 'Webpay Plus';
        $this->description = 'Recibe pagos en línea con tarjetas de crédito y Redcompra en tu Prestashop a través de Webpay Plus y Oneclick';
        $this->confirmUninstall = '¿Estás seguro/a que deseas desinstalar este módulo de pago?';
        $this->ps_versions_compliancy = array('min' => '1.7.6.0', 'max' => _PS_VERSION_);
        $this->pluginValidation();
        $this->log = TbkFactory::createLogger();
    }

    public function uninstall()
    {
        $this->uninstallTab();
        if (!parent::uninstall() || !Configuration::deleteByName("webpay")) {
            return false;
        }
        return true;
    }

    public function install()
    {
        $result = parent::install();
        /* carga la configuracion por defecto al instalar el plugin */
        $this->loadDefaultConfigurationWebpay();
        $this->loadDefaultConfigurationOneclick();

        /* Se instalan las tablas, si falla se sigue con la instalación */
        $resultInstallWebpayTable = $this->installWebpayTable();
        $this->logError("installWebpayTable => {$resultInstallWebpayTable}");
        $resultInstallOneclickTable = $this->installOneclickTable();
        $this->logError("installOneclickTable => {$resultInstallOneclickTable}");
        $this->installTab();

        /* Si algo falla aqui se muestran los errores */
        return $result && $this->registerHook(self::MODULE_HOOKS);
    }

    protected function installWebpayTable()
    {
        $installer = new \PrestaShop\Module\WebpayPlus\Install\WebpayPlusInstaller();
        return $installer->installWebpayOrdersTable();
    }

    private function installOneclickTable()
    {
        $installer = new \PrestaShop\Module\WebpayPlus\Install\OneclickInstaller();
        return $installer->installInscriptionsTable();
    }

    public function hookDisplayAdminOrderSide($params): ?string
    {
        try {
            $this->logInfo("Ejecutando hook displayAdminOrderSide");
            $displayAdminOrderSide = new DisplayAdminOrderSide();
            return $displayAdminOrderSide->execute($params);
        } catch (Exception | Error $e) {
            $this->logError("Error el ejecutar el hook: {$e->getMessage()}");
        }
    }

    public function hookDisplayBackOfficeHeader(): void
    {
        if ($this->context->controller->controller_name === 'AdminOrders') {
            $this->context->controller->addCSS('modules/' . $this->name . '/views/css/admin.css');
        }
    }

    public function hookDisplayHeader(): void
    {
        if ($this->context->controller->php_self === 'order-confirmation') {
            $this->context->controller->addCSS('modules/' . $this->name . '/views/css/front.css');
        }
    }

    public function hookDisplayPaymentReturn($params): ?string
    {
        try {
            $this->logInfo("Ejecutando hook displayPaymentReturn");
            $displayPaymentReturn = new DisplayPaymentReturn();
            return $displayPaymentReturn->execute($params);
        } catch (Exception | Error $e) {
            $this->logError("Error el ejecutar el hook: {$e->getMessage()}");
        }
    }

    /*
        Muestra la opciones de pago disponibles
    */
    public function hookPaymentOptions($params): ?array
    {

        $this->logInfo('*****************************************************');
        $this->logInfo('A.1. Mostrando medios de pago Webpay Plus');
        $this->logInfo(json_encode($params['cart']));
        $this->logInfo('-----------------------------------------------------');

        if (!$this->active) {
            return null;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return null;
        }
        $payment_options = [];
        if ($this->configWebpayIsOk()) {
            /*Agregamos la opcion de pago Webpay Plus */
            array_push($payment_options, ...$this->getWebpayPaymentOption($this, $this->context));
        } else {
            $this->logError("Configuración de WEBPAY PLUS incorrecta, revise los valores");
        }

        if ($this->configOneclickIsOk()) {
            /*Agregamos la opcion de pago Webpay Oneclick */
            array_push($payment_options, ...$this->getGroupOneclickPaymentOption($this, $this->context));
        } else {
            $this->logError("Configuración de ONECLICK incorrecta, revise los valores");
        }


        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }


    public function getContent()
    {
        $route = SymfonyContainer::getInstance()->get('router')->generate('ps_controller_webpay_configure');
        Tools::redirectAdmin($route);

    }

    private function pluginValidation()
    {
        $this->_errors = array();
    }

    protected function logError($msg)
    {
        $this->log->logError($msg);
    }

    protected function logInfo($msg)
    {
        $this->log->logInfo($msg);
    }

    public function updateSettings()
    {
        $this->oneclickUpdateSettings();
        return $this->webpayUpdateSettings();
    }

}



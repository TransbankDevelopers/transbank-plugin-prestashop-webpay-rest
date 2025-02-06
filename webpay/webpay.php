<?php

use PrestaShop\Module\WebpayPlus\Config\OneclickConfig;
use PrestaShop\Module\WebpayPlus\Config\WebpayConfig;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithTabs;
use PrestaShop\Module\WebpayPlus\Hooks\DisplayAdminOrderSide;
use PrestaShop\Module\WebpayPlus\Hooks\PaymentOptions;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use PrestaShop\Module\WebpayPlus\Hooks\DisplayPaymentReturn;
use Transbank\Plugin\Helpers\TbkConstants;

require_once __DIR__ . '/vendor/autoload.php';

class WebPay extends PaymentModule
{
    use InteractsWithWebpayDb;
    use InteractsWithTabs;

    protected $_errors = array();
    public $log;
    public $title = 'Pago con tarjetas de crédito o Redcompra';

    private const MODULE_HOOKS = [
        'paymentOptions',
        'displayBackOfficeHeader',
        'displayHeader',
        'displayPaymentReturn',
        'displayAdminOrderSide'
    ];

    public function __construct()
    {
        $this->name = TbkConstants::MODULE_NAME;
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
        WebpayConfig::initializeConfig();
        OneclickConfig::initializeConfig();

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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            $this->logError("Error el ejecutar el hook: {$e->getMessage()}");
        }
    }

    /*
        Muestra la opciones de pago disponibles
    */
    public function hookPaymentOptions($params): ?array
    {
        try {
            $this->logInfo("Ejecutando hook hookPaymentOptions");

            $cart = $params['cart'];
            $moduleCurrencies = $this->getCurrency($cart->id_currency);
            $paymentOptions = new PaymentOptions($moduleCurrencies);
            return $paymentOptions->execute($params);
        } catch (Throwable $e) {
            $this->logError("Error el ejecutar el hook: {$e->getMessage()}");
            return null;
        }
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
}

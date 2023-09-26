<?php

use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclick;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithTabs;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;


if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

class WebPay extends PaymentModule
{
    use InteractsWithWebpay;
    use InteractsWithOneclick;
    use InteractsWithTabs;

    public $title = 'Pago con tarjetas de crédito o Redcompra';
    protected $logger;

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
        $this->logger = TbkFactory::createLogger();
    }

    public function uninstall()
    {
        $this->logInfo("Ejecutando uninstall");
        $tbkAdminService = TbkFactory::createTbkAdminService();
        $tbkAdminService->deleteTables();
        $this->uninstallTab();
        if (!parent::uninstall() || !Configuration::deleteByName("webpay")) {
            return false;
        }
        return true;
    }

    public function install()
    {
        $this->logInfo("Ejecutando install");
        $result = parent::install();
        $tbkAdminService = TbkFactory::createTbkAdminService();
        $tbkAdminService->loadDefaultConfig();
        $tbkAdminService->createTables();
        $this->installTab();

        return  $result &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('displayAdminOrderLeft') &&
            $this->registerHook('actionOrderStatusUpdate') &&
            $this->registerHook($this->getDisplayOrderHookName());
    }

    public function hookActionOrderStatusUpdate($params) {
        $this->logInfo('hookActionOrderStatusUpdate');
        $this->logInfo(json_encode($params));
        if($params['newOrderStatus']->id == 2) {
            $this->logInfo("************************newOrderStatus->id==2");
        }
    }

    public function hookdisplayAdminOrderLeft($params)
    {
        return $this->showAdminDisplay($params);
    }

    public function hookdisplayAdminOrderTabContent($params)
    {
        return $this->showAdminDisplay($params);
    }

    private function showAdminDisplay($params)
    {
        $data = json_encode($params);
        $orderId = null;
        try {
            if (!$this->active) {
                return;
            }
            $orderId = $this->getOrderIdFromParam($params);
            $order = new Order((int)$orderId);
            if ($order->module != "webpay") {
                return;
            }
            $transactionService = TbkFactory::createTransactionService();
            $detail = $transactionService->getDetailForAdmin($orderId);
            return $this->get('twig')->render('@Modules/webpay/views/templates/admin/admin_order.html.twig',[
                'detail' => $detail
            ]);
        } catch (Exception $e) {
            $errorMessage = "ORDER_ID: {$orderId}, ERROR: ocurrió un error al ejecutar 'showAdminDisplay', PARAMS: {$data}, ORIGINAL_ERROR: {$e->getMessage()}";
            $this->logError($errorMessage);
        }
    }

    public function hookPaymentReturn($params)
    {
        $data = json_encode($params);
        $orderId = null;
        try {
            if (!$this->active) {
                return null;
            }
            $orderId = $this->getOrderIdFromParam($params);
            $transactionService = TbkFactory::createTransactionService();
            $detail = $transactionService->getDetailForClient($orderId);
            $this->smarty->assign(array(
                'detail' => $detail
            ));
            return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
        } catch (Exception $e) {
            $errorMessage = "ORDER_ID: {$orderId}, ERROR: ocurrió un error al ejecutar 'hookPaymentReturn', PARAMS: {$data}, ORIGINAL_ERROR: {$e->getMessage()}";
            $this->logError($errorMessage);
        }
    }

    /*
        Muestra la opciones de pago disponibles
    */
    public function hookPaymentOptions($params)
    {
        $data = json_encode($params);
        try {
            if (!$this->active) {
                return null;
            }
            if (!$this->checkCurrency($params['cart'])) {
                return null;
            }
            $paymentOptions = [];
            array_push($paymentOptions, ...$this->getWebpayPaymentOption($this, $this->context));
            array_push($paymentOptions, ...$this->getGroupOneclickPaymentOption($this, $this->context));
            return $paymentOptions;
        } catch (Exception $e) {
            $errorMessage = "ORDER_ID: {$this->getOrderIdFromParam($params)}, ERROR: ocurrió un error al ejecutar 'hookPaymentOptions', PARAMS: {$data}, ORIGINAL_ERROR: {$e->getMessage()}";
            $this->logError($errorMessage);
        }
    }

    private function checkCurrency($cart)
    {
        $currencyOrder = new Currency($cart->id_currency);
        $currenciesModule = $this->getCurrency($cart->id_currency);
        if (is_array($currenciesModule)) {
            foreach ($currenciesModule as $cm) {
                if ($currencyOrder->id == $cm['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getContent()
    {
        $route = SymfonyContainer::getInstance()->get('router')->generate('ps_controller_configure');
        Tools::redirectAdmin($route);
    }
    
    /**
     * @return string
     */
    private function getDisplayOrderHookName()
    {
        $displayOrder = 'displayAdminOrderLeft';
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $displayOrder = 'displayAdminOrderTabContent';
        }

        return $displayOrder;
    }

    public function updateSettings(){
        $this->logInfo("updateSettings");
        return true;
    }

    private function logError($msg){
        $this->logger->logError($msg);
    }

    private function logInfo($msg){
        $this->logger->logInfo($msg);
    }
    
    private function getOrderIdFromParam($params){
        $orderId = isset($params['id_order']) ? $params['id_order'] : null;
        if ($orderId == null){
            $orderId = isset($params['order']) ? $params['order']->id : null;
        }
        return $orderId;
    }

    private function getCurrentStoreId(){
        return Context::getContext()->shop->id;
    }
}

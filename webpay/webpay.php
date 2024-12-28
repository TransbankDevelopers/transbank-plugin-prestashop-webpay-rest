<?php

use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclick;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithTabs;
use PrestaShop\Module\WebpayPlus\Hooks\DisplayAdminOrderSide;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Helpers\TbkConstants;
use PrestaShop\Module\WebpayPlus\Utils\Template;

require_once __DIR__.'/vendor/autoload.php';

class WebPay extends PaymentModule
{
    use InteractsWithWebpay;
    use InteractsWithOneclick;
    use InteractsWithWebpayDb;
    use InteractsWithTabs;

    protected $_errors = array();
    public $log;
    public $title = 'Pago con tarjetas de crédito o Redcompra';

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
        $this->setDebugActive("");
        $this->loadDefaultConfigurationWebpay();
        $this->loadDefaultConfigurationOneclick();

        /* Se instalan las tablas, si falla se sigue con la instalación */
        $resultInstallWebpayTable = $this->installWebpayTable();
        $this->logError("installWebpayTable => {$resultInstallWebpayTable}");
        $resultInstallOneclickTable = $this->installOneclickTable();
        $this->logError("installOneclickTable => {$resultInstallOneclickTable}");
        $this->installTab();

        /* Si algo falla aqui se muestran los errores */
        return  $result &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('displayAdminOrderSide');
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

    public function hookdisplayAdminOrderSide($params)
    {
        $displayAdminOrderSide = new DisplayAdminOrderSide();
        return $displayAdminOrderSide->execute($params);
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        if ($this->context->controller->controller_name === 'AdminOrders') {
            $this->context->controller->addCSS('modules/'.$this->name.'/views/css/admin.css');
        }
    }

    private function getFormatTransbankWebpayRestTransactionByOrderId($orderId){
        $webpayTransaction = $this->getTransbankWebpayRestTransactionByOrderId($orderId);
        if (!$webpayTransaction) {
            $this->logError('Showing confirmation page, but there is no webpayTransaction object, so we cant find an approved transaction for this order.');
        }


        $this->logInfo('D.3. TransbankWebpayRestTransaction obtenida');
        $this->logInfo(isset($webpayTransaction) ? $webpayTransaction->transbank_response : 'No se encontro el registro');

        $transbankResponse = json_decode($webpayTransaction->transbank_response, true);
        $transactionDate = strtotime($transbankResponse['transactionDate']);
        $token = $webpayTransaction->token;

        if ($webpayTransaction->product == TransbankWebpayRestTransaction::PRODUCT_WEBPAY_PLUS){
            $amount = number_format($transbankResponse['amount'], 0, ',', '.');
            $paymentTypeCode = $transbankResponse['paymentTypeCode'];
            $cardNumber = $transbankResponse['cardDetail']['card_number'];
            $installmentsNumber = $transbankResponse['installmentsNumber'] ? $transbankResponse['installmentsNumber'] : "0";
            $authorizationCode = $transbankResponse['authorizationCode'];
            $buyOrder = $transbankResponse['buyOrder'];
            $installmentsAmount = $transbankResponse['installmentsAmount'] ? number_format($transbankResponse['installmentsAmount'], 0, ',', '.') : "0";
            $responseCode = $transbankResponse['responseCode'];
            $status = $transbankResponse['status'];
        }
        else{
            $cardNumber = $transbankResponse['cardNumber'];
            $detail = $transbankResponse['details'][0];/* Se asume que el valor se extrae del primer elemento del details */
            $amount = number_format($detail['amount'], 0, ',', '.');
            $paymentTypeCode = $detail['paymentTypeCode'];
            $installmentsNumber = $detail['installmentsNumber'] ? $detail['installmentsNumber'] : "0";
            $authorizationCode = $detail['authorizationCode'];
            $buyOrder = $detail['buyOrder'];
            $installmentsAmount = $detail['installmentsAmount'] ? number_format($detail['installmentsAmount'], 0, ',', '.') : "0";
            $responseCode = $detail['responseCode'];
            $status = $detail['status'];
        }

        if ($paymentTypeCode == "VD") {
            $paymentType = "Débito";
        } elseif ($paymentTypeCode == "VP") {
            $paymentType = "Prepago";
        } else {
            $paymentType = "Crédito";
        }
        if (in_array($paymentTypeCode, ["SI", "S2", "NC", "VC"])) {
            $installmentType = TbkConstants::PAYMENT_TYPE_CODE[$paymentTypeCode];
        } else {
            $installmentType = "Sin cuotas";
        }

        return [
            'product' => $webpayTransaction->product,
            'cardNumber' => $cardNumber,
            'paymentType' => $paymentType,
            'totalPago' => $amount,
            'transactionDate' => date("d-m-Y", $transactionDate),
            'transactionHour' => date("H:i:s", $transactionDate),
            'buyOrder' => $buyOrder,
            'authorizationCode' => $authorizationCode,
            'installmentType' => $installmentType,
            'installmentsNumber' => $installmentsNumber,
            'installmentsAmount' => $installmentsAmount,
            'responseCode' => $responseCode,
            'status' => $status,
            'token' => $token
        ];
    }

    public function hookPaymentReturn($params)
    {
        $this->logInfo('D.1. Retornando (hookPaymentReturn)');
        if (!$this->active) {
            return;
        }

        $nameOrderRef = isset($params['order']) ? 'order' : 'objOrder';
        $orderId = $params[$nameOrderRef]->id;

        $this->logInfo('D.2. Obteniendo TransbankWebpayRestTransaction desde la BD');
        $this->logInfo('nameOrderRef: '.$nameOrderRef.', orderId: '.$orderId);

        $tx = $this->getFormatTransbankWebpayRestTransactionByOrderId($orderId);

        $this->smarty->assign(array(
            'shop_name' => $this->context->shop->name,
            'total_to_pay' =>  $params[$nameOrderRef]->getOrdersTotalPaid(),
            'status' => 'ok',
            'id_order' => $orderId,
            'WEBPAY_RESULT_DESC' => "Transacción aprobada",
            'WEBPAY_VOUCHER_NROTARJETA' => $tx['cardNumber'],
            'WEBPAY_VOUCHER_TXDATE_FECHA' => $tx['transactionDate'],
            'WEBPAY_VOUCHER_TXDATE_HORA' => $tx['transactionHour'],
            'WEBPAY_VOUCHER_TOTALPAGO' => $tx['totalPago'],
            'WEBPAY_VOUCHER_ORDENCOMPRA' => $tx['buyOrder'],
            'WEBPAY_VOUCHER_AUTCODE' => $tx['authorizationCode'],
            'WEBPAY_VOUCHER_TIPOCUOTAS' => $tx['installmentType'],
            'WEBPAY_VOUCHER_TIPOPAGO' => $tx['paymentType'],
            'WEBPAY_VOUCHER_NROCUOTAS' => $tx['installmentsNumber'],
            'WEBPAY_VOUCHER_AMOUNT_CUOTAS' => $tx['installmentsAmount'],
            'WEBPAY_RESULT_CODE' => $tx['responseCode'],
        ));

        if (isset($params[$nameOrderRef]->reference) && !empty($params[$nameOrderRef]->reference)) {
            $this->smarty->assign('reference', $params[$nameOrderRef]->reference);
        }
        return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
    }

    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }

        $this->logInfo('*****************************************************');
        $this->logInfo('Ejecutando hookPayment');
        $this->logInfo(json_encode($params));
        $this->logInfo('-----------------------------------------------------');

        Context::getContext()->smarty->assign(array(
            'logo' => \Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/oneclick_80px.svg'),
            'title' => $this->title
        ));
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /*
        Muestra la opciones de pago disponibles
    */
    public function hookPaymentOptions($params)
    {

        $this->logInfo('*****************************************************');
        $this->logInfo('A.1. Mostrando medios de pago Webpay Plus');
        $this->logInfo(json_encode($params['cart']));
        $this->logInfo('-----------------------------------------------------');

        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $payment_options = [];
        if ($this->configWebpayIsOk()){
            /*Agregamos la opcion de pago Webpay Plus */
            array_push($payment_options, ...$this->getWebpayPaymentOption($this, $this->context));
        }
        else{
            $this->logError("Configuración de WEBPAY PLUS incorrecta, revise los valores");
        }

        if ($this->configOneclickIsOk()){
            /*Agregamos la opcion de pago Webpay Oneclick */
            array_push($payment_options, ...$this->getGroupOneclickPaymentOption($this, $this->context));
        }
        else{
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

    protected function logError($msg){
        $this->log->logError($msg);
    }

    protected function logInfo($msg){
        $this->log->logInfo($msg);
    }

    public function updateSettings(){
        $this->oneclickUpdateSettings();
        return $this->webpayUpdateSettings();
    }

}



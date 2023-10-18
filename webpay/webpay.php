<?php

use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithCommon;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclick;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithTabs;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclickLog;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayLog;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Exceptions\EcommerceException;
use Transbank\Plugin\Helpers\TbkConstans;

require_once __DIR__.'/vendor/autoload.php';

class WebPay extends PaymentModule
{
    use InteractsWithWebpayLog;
    use InteractsWithOneclickLog;
    use InteractsWithWebpay;
    use InteractsWithOneclick;
    use InteractsWithCommon;
    use InteractsWithWebpayDb;
    use InteractsWithTabs;
    

    //const DEBUG_MODE = true;
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
        try {
            $this->log = TbkFactory::createLogger();
        } catch (Exception $e) {
            throw new EcommerceException($e->getMessage(), 0, $e);
        }
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
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('displayAdminOrderLeft') &&
            $this->registerHook($this->getDisplayOrderHookName());
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

    public function hookdisplayAdminOrderLeft($params)
    {
        return $this->AdminDisplay($params);
    }
    public function hookdisplayAdminOrderTabContent($params)
    {
        return $this->AdminDisplay($params);
    }

    public function AdminDisplay($params)
    {
        if (!$this->active) {
            return;
        }

        $orderId = $params['id_order'];
        $bsOrder = new Order((int)$orderId);

        if ($bsOrder->module != "webpay") {
            return;
        }

        $tx = $this->getFormatTransbankWebpayRestTransactionByOrderId($orderId);
        $details = array(
            array(
                'desc' => $this->l('Producto'),
                'data' => $tx['product'] ,
            ),
            array(
                'desc' => $this->l('Fecha de Transacción'),
                'data' => $tx['transactionDate'] . " " . $tx['transactionHour'],
            ),
            array(
                'desc' => $this->l('Tipo de Tarjeta'),
                'data' => $tx['paymentType'],
            ),
            array(
                'desc' => $this->l('Tipo de Cuotas'),
                'data' => $tx['installmentType'],
            ),
            array(
                'desc' => $this->l('Cantidad de Cuotas'),
                'data' => $tx['installmentsNumber'],
            ),
            array(
                'desc' => $this->l('Tarjeta'),
                'data' => $tx['cardNumber'],
            ),
            array(
                'desc' => $this->l('Total Cobrado'),
                'data' => "$" . $tx['totalPago'],
            ),
            array(
                'desc' => $this->l('Código de Autorización'),
                'data' => $tx['authorizationCode'],
            ),
            array(
                'desc' => $this->l('Respuesta del Banco'),
                'data' => $tx['status'],
            ),
            array(
                'desc' => $this->l('Orden de Compra'),
                'data' => $tx['buyOrder'],
            ),
            array(
                'desc' => $this->l('Código de Resultado'),
                'data' => $tx['responseCode'],
            ),
            array(
                'desc' => $this->l('Token'),
                'data' => $tx['token'],
            ),
        );

        $this->context->smarty->assign($this->name, array(
            '_path' => $this->_path,
            'title' => $this->displayName,
            'details' => $details,
        ));
        return $this->display(__FILE__, 'views/templates/admin/admin_order.tpl');
    }

    private function getFormatTransbankWebpayRestTransactionByOrderId($orderId){
        $webpayTransaction = $this->getTransbankWebpayRestTransactionByOrderId($orderId);
        if (!$webpayTransaction) {
            $this->logError('Showing confirmation page, but there is no webpayTransaction object, so we cant find an approved transaction for this order.');
        }
        
        if($this->getDebugActive()==1){
            $this->logInfo('D.3. TransbankWebpayRestTransaction obtenida');
            $this->logInfo(isset($webpayTransaction) ? $webpayTransaction->transbank_response : 'No se encontro el registro');
        }

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
            $installmentType = TbkConstans::PAYMENT_TYPE_CODE[$paymentTypeCode];
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
        if($this->getDebugActive()==1){
            $this->logInfo('D.1. Retornando (hookPaymentReturn)');
        }
        if (!$this->active) {
            return;
        }

        $nameOrderRef = isset($params['order']) ? 'order' : 'objOrder';
        $orderId = $params[$nameOrderRef]->id;
        
        if($this->getDebugActive()==1){
            $this->logInfo('D.2. Obteniendo TransbankWebpayRestTransaction desde la BD');
            $this->logInfo('nameOrderRef: '.$nameOrderRef.', orderId: '.$orderId);
        }
        
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
        if($this->getDebugActive()==1){
            $this->logInfo('*****************************************************');
            $this->logInfo('A.1. Mostrando medios de pago Webpay Plus');
            $this->logInfo(json_encode($params['cart']));
            $this->logInfo('-----------------------------------------------------');
        }

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
            $this->logWebpayPlusConfigError();
        }

        if ($this->configOneclickIsOk()){
            /*Agregamos la opcion de pago Webpay Oneclick */
            array_push($payment_options, ...$this->getGroupOneclickPaymentOption($this, $this->context));
        }
        else{
            $this->logOneclickConfigError();
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
    
    /**
     * @return string
     */
    public function getDisplayOrderHookName()
    {
        $displayOrder = 'displayAdminOrderLeft';
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $displayOrder = 'displayAdminOrderTabContent';
        }

        return $displayOrder;
    }

    protected function logError($msg){
        $this->log->logError($msg);
    }

    protected function logInfo($msg){
        $this->log->logInfo($msg);
    }

    public function updateSettings(){
        $this->oneclickUpdateSettings();
        $this->commonUpdateSettings();
        return $this->webpayUpdateSettings();
    }
    
}



<?php

namespace PrestaShop\Module\WebpayPlus\Hooks;

use PrestaShop\Module\WebpayPlus\Utils\Template;
use PrestaShop\Module\WebpayPlus\Helpers\TbkResponseUtil;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\Module\WebpayPlus\Hooks\AbstractHookHandler;

class DisplayPaymentReturn extends AbstractHookHandler
{
    use InteractsWithWebpayDb;

    /**
     * @var Template Instance of the Template utility to render Twig templates.
     */
    private $template;

    /**
     * Constructor.
     * Initializes the class.
     */
    public function __construct()
    {
        parent::__construct();
        $this->template = new Template();
    }

    /**
     * Executes the hook logic to display payment details.
     *
     * @param array $params The parameters passed to the hook, including the order ID.
     * @return string|null Rendered Twig template as a string, or null if the order does not use the Webpay module.
     */
    public function execute(array $params): ?string
    {
        $this->logInfo('Ejecutando hook DisplayPaymentReturn');
        $this->logDebug('Parámetros recibidos: '. json_encode($params, JSON_UNESCAPED_UNICODE));

        $order = $params['order'];
        $this->logDebug('ID de la orden: ' . $order->id);

        $this->logInfo('Comprobando que la orden usa el módulo Webpay');
        if ($order->module != "webpay") {
            $this->logInfo('Orden no usa el módulo Webpay');
            return null;
        }

        $this->logInfo('Orden usa el módulo Webpay');
        
        $this->logInfo('Cargando respuesta de Transbank');
        $transbankTransaction = $this->getTransactionWebpayApprovedByOrderId($order->id);
        $transbankResponse = $transbankTransaction->transbank_response;
        $this->logInfo('Respuesta de Transbank ha sido cargada');

        $product = $transbankTransaction->product;
        $this->logDebug('Producto asociado: ' . $product);

        $objectResponse = json_decode($transbankResponse);

        $this->logDebug('Aplicando formato a respuesta recibida');
        $formattedResponse = [];
        if ($product === TransbankWebpayRestTransaction::PRODUCT_WEBPAY_ONECLICK) {
            $formattedResponse = TbkResponseUtil::getOneclickFormattedResponse($objectResponse);
            $this->logDebug('Respuesta formateada como transacción Oneclick');
        } else {
            $formattedResponse = TbkResponseUtil::getWebpayFormattedResponse($objectResponse);
            $this->logDebug('Respuesta formateada como transacción Webpay');
        }

        $this->logDebug('Respuesta formateada: ' . json_encode($formattedResponse, JSON_UNESCAPED_UNICODE));

        $this->logInfo('El hook DisplayPaymentReturn se ejecutó correctamente');

        return $this->template->render('hook/payment_return.html.twig', [
            'dataView' => $formattedResponse
        ]);
    }
}

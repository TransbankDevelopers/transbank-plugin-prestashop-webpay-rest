<?php

namespace PrestaShop\Module\WebpayPlus\Hooks;

use PrestaShop\Module\WebpayPlus\Utils\Template;
use PrestaShop\Module\WebpayPlus\Helpers\TbkResponseUtil;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\Module\WebpayPlus\Hooks\AbstractHookHandler;
use PrestaShop\Module\WebpayPlus\Repository\TransactionRepository;

class DisplayPaymentReturn extends AbstractHookHandler
{
    /**
     * @var Template Instance of the Template utility to render Twig templates.
     */
    private $template;

    /**
     * @var TransactionRepository
     */
    private $repository;

    /**
     * Constructor.
     * Initializes the class.
     */
    public function __construct()
    {
        parent::__construct();
        $this->template = new Template();
        $this->repository = new TransactionRepository();
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
        $this->logDebug('Parámetros recibidos: ' . json_encode($params, JSON_UNESCAPED_UNICODE));

        $order = $params['order'];
        $this->logDebug('ID de la orden: ' . $order->id);

        if ($order->module != "webpay") {
            $this->logInfo('Orden no usa el módulo Webpay');
            return null;
        }

        $transbankTransaction = $this->repository->getTransactionWebpayApprovedByOrderId($order->id);
        $transbankResponse = $transbankTransaction->transbank_response;

        $product = $transbankTransaction->product;
        $this->logDebug('Producto asociado: ' . $product);

        $objectResponse = json_decode($transbankResponse);

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

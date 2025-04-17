<?php

namespace PrestaShop\Module\WebpayPlus\Hooks;

use PrestaShop\Module\WebpayPlus\Helpers\AbstractLoggerHook;
use PrestaShop\Module\WebpayPlus\Utils\Template;
use PrestaShop\Module\WebpayPlus\Helpers\TbkResponseUtil;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

class DisplayPaymentReturn extends AbstractLoggerHook implements HookHandlerInterface
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
        parent::__construct(); // Initialize the logger
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
        $this->logInfo('Parámetros recibidos en el hook displayPaymentReturn: ' . json_encode($params));

        $order = $params['order'];
        $this->logInfo('ID de la orden: ' . $order->id);

        if ($order->module != "webpay") {
            return null;
        }

        $transbankTransaction = $this->getTransactionWebpayApprovedByOrderId($order->id);
        $transbankResponse = $transbankTransaction->transbank_response;

        $product = $transbankTransaction->product;
        $this->logInfo('Producto: ' . $product);

        $objectResponse = json_decode($transbankResponse);

        $formattedResponse = [];
        if ($product === TransbankWebpayRestTransaction::PRODUCT_WEBPAY_ONECLICK) {
            $formattedResponse = TbkResponseUtil::getOneclickFormattedResponse($objectResponse);
        } else {
            $formattedResponse = TbkResponseUtil::getWebpayFormattedResponse($objectResponse);
        }

        $this->logInfo('Respuesta de Transbank: ' . json_encode($formattedResponse, JSON_UNESCAPED_UNICODE));

        return $this->template->render('hook/payment_return.html.twig', [
            'dataView' => $formattedResponse
        ]);
    }
}

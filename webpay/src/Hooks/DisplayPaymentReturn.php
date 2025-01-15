<?php

namespace PrestaShop\Module\WebpayPlus\Hooks;

use PrestaShop\Module\WebpayPlus\Utils\Template;
use PrestaShop\Module\WebpayPlus\Helpers\TbkResponseUtil;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

class DisplayPaymentReturn implements HookHandlerInterface
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
        $order = $params['order'];

        if ($order->module != "webpay") {
            return null;
        }

        $transbankTransaction = $this->getTransactionWebpayApprovedByOrderId($order->id);
        $transbankResponse = $transbankTransaction->transbank_response;

        $product = $transbankTransaction->product;
        $objectResponse = json_decode($transbankResponse);

        $formattedResponse = [];
        if ($product === TransbankWebpayRestTransaction::PRODUCT_WEBPAY_ONECLICK) {
            $formattedResponse = TbkResponseUtil::getOneclickFormattedResponse($objectResponse);
        } else {
            $formattedResponse = TbkResponseUtil::getWebpayFormattedResponse($objectResponse);
        }

        return $this->template->render('hook/payment_return.html.twig', [
            'dataView' => $formattedResponse
        ]);
    }
}
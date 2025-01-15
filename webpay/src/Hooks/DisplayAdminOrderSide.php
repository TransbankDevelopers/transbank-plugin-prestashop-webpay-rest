<?php

namespace PrestaShop\Module\WebpayPlus\Hooks;

use Order;
use PrestaShop\Module\WebpayPlus\Utils\Template;
use PrestaShop\Module\WebpayPlus\Helpers\TbkResponseUtil;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use Transbank\Plugin\Helpers\TbkConstants;

/**
 * Class DisplayAdminOrderSide
 *
 * This class is responsible for displaying detailed payment information on the admin order side
 * when the payment was processed via the Webpay module. It renders the details of a transaction
 * using a custom Twig template.
 */
class DisplayAdminOrderSide
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
        $orderId = $params['id_order'];
        $order = new Order($orderId);

        if ($order->module != "webpay") {
            return null;
        }

        $transbankTransaction = $this->getTransactionWebpayApprovedByOrderId($orderId);
        $transbankResponse = $transbankTransaction->transbank_response;

        if (!isset($transbankResponse)) {
            return null;
        }

        $product = $transbankTransaction->product;
        $objectResponse = json_decode($transbankResponse);

        $formattedResponse = [];
        if ($product === TransbankWebpayRestTransaction::PRODUCT_WEBPAY_ONECLICK) {
            $formattedResponse = TbkResponseUtil::getOneclickStatusFormattedResponse($objectResponse);
            $status = $objectResponse->details[0]->status;
        } else {
            $formattedResponse = TbkResponseUtil::getWebpayStatusFormattedResponse($objectResponse);
            $formattedResponse['token'] = $transbankTransaction->token;
            $status = $objectResponse->status;
        }

        return $this->template->render('hook/payment_detail.html.twig', [
            'title' => $this->buildTitleText($product, $status),
            'isPsGreaterOrEqual177' => version_compare(_PS_VERSION_, '1.7.7.0', '>='),
            'dataView' => $this->buildDataForView($formattedResponse)
        ]);
    }

    /**
     * Builds the data array for rendering the Twig view.
     *
     * @param array $formattedResponse Formatted transaction response data.
     * @return array Array of data ready to be rendered in the Twig template.
     */
    private function buildDataForView(array $formattedResponse): array {
        $result = [];
        foreach ($formattedResponse as $key => $value) {
            $result[] = [
                'key' => $key,
                'label' => $this->getLabelTextFromKey($key),
                'value' => $value,
                'class' => $this->getClassForField($key, $value)
            ];
        }
        return $result;
    }

    /**
     * Retrieves a human-readable label for a given key.
     *
     * @param string $key The key to map to a label.
     * @return string The corresponding label, or the key itself if no mapping is found.
     */
    private function getLabelTextFromKey(string $key): string {
        $keyToLabelMap = [
            'status' => 'Estado',
            'responseCode' => 'Código de respuesta',
            'amount' => 'Monto',
            'authorizationCode' => 'Código de autorización',
            'accountingDate' => 'Fecha contable',
            'paymentType' => 'Tipo de pago',
            'installmentType' => 'Tipo de cuota',
            'installmentNumber' => 'Número de cuotas',
            'installmentAmount' => 'Monto cuota',
            'buyOrderMall' => 'Orden de compra mall',
            'buyOrderStore' => 'Orden de compra tienda',
            'buyOrder' => 'Orden de compra',
            'cardNumber' => 'Número de tarjeta',
            'transactionDate' => 'Fecha transacción',
            'transactionTime' => 'Hora transacción',
            'balance' => 'Balance',
            'vci' => 'VCI',
            'sessionId' => 'ID de Sesión',
            'token' => 'Token'
        ];

        return $keyToLabelMap[$key] ?? $key;
    }

    /**
     * Determines the CSS class for a given key-value pair.
     *
     * @param string $key The key of the field.
     * @param mixed $value The value of the field.
     * @return string The CSS class to apply.
     */
    private function getClassForField(string $key, $value): string {

        $valueToBadgeClass = [
            'Inicializada' => 'tbk-badge-warning',
            'Capturada' => 'tbk-badge-success',
            'Autorizada' => 'tbk-badge-success',
            'Fallida' => 'tbk-badge-error',
            'Anulada' => 'tbk-badge-info',
            'Reversada' => 'tbk-badge-info',
            'Parcialmente anulada' => 'tbk-badge-info'
        ];

        $class = [];

        if ($key === 'status') {
            $class[] = 'tbk-badge';
            $class[] = $valueToBadgeClass[$value] ?? 'tbk-badge-default';
        }

        if ($key === 'token') {
            $class[] = 'tbk-token';
        }

        return implode(' ', $class);
    }

    /**
     * Builds the title text for the payment detail.
     *
     * @param string $product The product type.
     * @param string $transactionStatus The transaction status.
     * @return string The formatted title text.
     */
    private function buildTitleText(string $product, string $transactionStatus)
    {
        $productTitle = TbkConstants::PRODUCT_TYPE[$product];
        $titleDetail = 'Pago exitoso';

        if ($transactionStatus === TransbankWebpayRestTransaction::STATUS_FAILED) {
            $titleDetail = 'Pago rechazado';
        }

        return "{$productTitle} {$titleDetail}";
    }
}

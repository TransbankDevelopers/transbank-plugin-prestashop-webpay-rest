<?php

use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\Module\WebpayPlus\Helpers\WebpayPlusFactory;
use PrestaShop\Module\WebpayPlus\Utils\TransbankSdkWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Exceptions\EcommerceException;

/**
 * Class WebPayWebpayplusPaymentModuleFrontController.
 */
class WebPayWebpayplusPaymentModuleFrontController extends BaseModuleFrontController
{
    /**
     * Constructor for the payment controller.
     * Initializes the logger instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->logger = TbkFactory::createLogger();
    }

    /**
     * Main entry point to initialize content and process Webpay Plus transactions.
     * Handles the creation of a Webpay Plus transaction and prepares the redirection template.
     */
    public function initContent(): void
    {
        parent::initContent();

        try {
            $cart = $this->getCartFromContext();
            $cartId = $cart->id;
            $amount = $this->getOrderTotalRound($cart);
            $randomNumber = $this->generateRandomId();

            $buyOrder = "ps:{$randomNumber}:{$cartId}";
            $sessionId = "ps:sessionId:{$randomNumber}:{$cartId}";

            $returnUrl = $this->getReturnUrl('webpaypluspaymentvalidate');

            $this->logger->logInfo("Creando transacción Webpay Plus. [Datos]:");
            $this->logInfo("amount: {$amount} sessionId: {$sessionId} buyOrder: {$buyOrder} returnUrl: {$returnUrl}");

            $webpaySdk = WebpayPlusFactory::create();
            $createResponse = $webpaySdk->createTransaction($amount, $sessionId, $buyOrder, $returnUrl);

            $this->logInfo("Transacción creada. [Respuesta]:");
            $this->logInfo(json_encode($createResponse));

            $this->createTransbankTransactionRecord(
                $webpaySdk,
                $sessionId,
                $cartId,
                $cart->id_currency,
                $createResponse['token_ws'],
                $buyOrder,
                $amount
            );

            $this->setRedirectionTemplate($createResponse, $amount);

        } catch (Throwable $e) {
            $this->logger->logError("Error al crear la transacción: " . $e->getMessage());
            $this->setPaymentErrorPage(
                "Se ha producido un error al momento de iniciar el pago. " .
                "Por favor, inténtelo nuevamente. Si el problema persiste, contacte al comercio."
            );
        }
    }

    /**
     * Saves the Webpay Plus transaction details in the database.
     *
     * @param TransbankSdkWebpay $webpay The Webpay SDK instance.
     * @param string $sessionId The unique session ID for the transaction.
     * @param int $cartId The cart ID associated with the transaction.
     * @param int $currencyId The currency ID for the transaction.
     * @param string $token The token received from Webpay Plus.
     * @param string $buyOrder The unique buy order identifier.
     * @param float $amount The transaction amount.
     *
     * @return TransbankWebpayRestTransaction The saved transaction record.
     *
     * @throws EcommerceException If the transaction cannot be saved in the database.
     */
    private function createTransbankTransactionRecord(
        TransbankSdkWebpay $webpay,
        string $sessionId,
        int $cartId,
        int $currencyId,
        string $token,
        string $buyOrder,
        float $amount
    ): void {

        $transaction = new TransbankWebpayRestTransaction();
        $transaction->amount = $amount;
        $transaction->cart_id = $cartId;
        $transaction->buy_order = $buyOrder;
        $transaction->session_id = $sessionId;
        $transaction->token = $token;
        $transaction->status = TransbankWebpayRestTransaction::STATUS_INITIALIZED;
        $transaction->created_at = date('Y-m-d H:i:s');
        $transaction->shop_id = (int) Context::getContext()->shop->id;
        $transaction->currency_id = $currencyId;

        $transaction->commerce_code = $webpay->getCommerceCode();
        $transaction->environment = $webpay->getEnviroment();
        $transaction->product = TransbankWebpayRestTransaction::PRODUCT_WEBPAY_PLUS;

        $this->logInfo("Creando registro en la tabla webpay_transactions [Datos]:");
        $this->logInfo(json_encode($transaction));

        $saved = $transaction->add();
        if (!$saved) {
            $message = "No se pudo crear la transacción en la tabla webpay_transactions";
            $this->logError($message);
            throw new EcommerceException($message);
        }
    }

    /**
     * Prepares the redirection template for the payment page.
     *
     * @param array $result The response from the Webpay Plus transaction creation.
     * @param float $amount The transaction amount.
     */
    protected function setRedirectionTemplate(array $result, $amount)
    {
        Context::getContext()->smarty->assign([
            'url' => isset($result['url']) ? $result['url'] : '',
            'token_ws' => $result['token_ws'],
            'amount' => $amount,
        ]);
        $this->setTemplate('module:webpay/views/templates/front/payment_execution.tpl');
    }
}

<?php

use PrestaShop\Module\WebpayPlus\Repository\TransactionRepository;
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
    /** @var TransactionRepository */
    private $repository;

    /**
     * Constructor for the payment controller.
     * Initializes the logger instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->logger = TbkFactory::createLogger();
        $this->repository = new TransactionRepository();
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

            $transactionData = [
                'amount' => $amount,
                'cart_id' => $cartId,
                'buy_order' => $buyOrder,
                'session_id' => $sessionId,
                'token' => $createResponse['token_ws'],
                'status' => TransbankWebpayRestTransaction::STATUS_INITIALIZED,
                'currency_id' => $cart->id_currency,
                'commerce_code' => $webpaySdk->getCommerceCode(),
                'environment' => $webpaySdk->getEnvironment(),
                'product' => TransbankWebpayRestTransaction::PRODUCT_WEBPAY_PLUS,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->logInfo("Creando registro en la tabla webpay_transactions [Datos]:");
            $this->logInfo(json_encode($transactionData));

            $this->repository->createTransaction($transactionData);


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
            'redirectType' => 'webpayplus'
        ]);
        $this->setTemplate('module:webpay/views/templates/front/redirect_to_payment_form.tpl');
    }
}

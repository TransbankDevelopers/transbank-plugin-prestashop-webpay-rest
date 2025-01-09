<?php

use PrestaShop\Module\WebpayPlus\Helpers\SqlHelper;
use PrestaShop\Module\WebpayPlus\Controller\PaymentModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\WebpayPlusFactory;
use Transbank\Webpay\WebpayPlus\Responses\TransactionCommitResponse;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Exceptions\EcommerceException;

/**
 * Class WebPayWebpayplusPaymentValidateModuleFrontController.
 */
class WebPayWebpayplusPaymentValidateModuleFrontController extends PaymentModuleFrontController
{
    use InteractsWithWebpay;
    use InteractsWithWebpayDb;

    const WEBPAY_NORMAL_FLOW = 'normal';
    const WEBPAY_TIMEOUT_FLOW = 'timeout';
    const WEBPAY_ABORTED_FLOW = 'aborted';
    const WEBPAY_ERROR_FLOW = 'error';
    const WEBPAY_INVALID_FLOW = 'invalid';

    const WEBPAY_FAILED_FLOW_MESSAGE = 'Tu transacción no pudo ser autorizada. Ningún cobro fue realizado.';
    const WEBPAY_CANCELED_BY_USER_FLOW_MESSAGE = 'Orden cancelada por el usuario. Por favor, reintente el pago.';
    const WEBPAY_TIMEOUT_FLOW_MESSAGE = 'Orden cancelada por inactividad del usuario en el formulario de pago. Por favor, reintente el pago.';
    const WEBPAY_ERROR_FLOW_MESSAGE = 'Orden cancelada por un error en el formulario de pago. Por favor, reintente el pago.';
    const WEBPAY_EXCEPTION_FLOW_MESSAGE = 'No se pudo procesar el pago. Si el problema persiste, contacte al comercio.';
    const WEBPAY_CART_MANIPULATED_MESSAGE = "El monto del carro ha cambiado mientras se procesaba el pago, la transacción fue cancelad. Ningún cobro fue realizado.";

    protected $responseData = [];

    public function __construct()
    {
        parent::__construct();
        $this->logger = TbkFactory::createLogger();
    }

    public function initContent(): void
    {
        parent::initContent();

        try {
            $requestMethod = $_SERVER['REQUEST_METHOD'];
            $request = $requestMethod === 'POST' ? $_POST : $_GET;
            $this->logger->logInfo('Procesando retorno desde formulario de Webpay.');
            $this->logger->logInfo('Request: method -> ' . $requestMethod);
            $this->logger->logInfo('Request: payload -> ' . json_encode($request));

            if (!$this->module->active) {
                throw new EcommerceException('El módulo de Webpay no está activo.');
            }

            $this->handleRequest($request);
        } catch (\Exception | \Error $e) {
            $this->logger->logError('Error en el proceso de validación de pago: ' . $e->getMessage());
            $this->setPaymentErrorPage(self::WEBPAY_EXCEPTION_FLOW_MESSAGE);
        }
    }

    private function handleRequest(array $request): void
    {
        $webpayFlow = $this->getWebpayFlow($request);

        if ($webpayFlow == self::WEBPAY_NORMAL_FLOW) {
            $this->handleNormalFlow($request['token_ws']);
        }

        if ($webpayFlow == self::WEBPAY_TIMEOUT_FLOW) {
            $this->handleFlowTimeout($request['TBK_ORDEN_COMPRA']);
        }

        if ($webpayFlow == self::WEBPAY_ABORTED_FLOW) {
            $this->handleFlowAborted($request['TBK_TOKEN']);
        }

        if ($webpayFlow == self::WEBPAY_ERROR_FLOW) {
            $this->handleFlowError($request['token_ws']);
        }

        if ($webpayFlow == self::WEBPAY_INVALID_FLOW) {
            throw new EcommerceException('Flujo de pago no reconocido.');
        }
    }

    private function getWebpayFlow(array $request): string
    {
        $tokenWs = $request['token_ws'] ?? null;
        $tbkToken = $request['TBK_TOKEN'] ?? null;
        $tbkIdSession = $request['TBK_ID_SESION'] ?? null;
        $webpayFlow = self::WEBPAY_INVALID_FLOW;

        if (isset($tokenWs) && isset($tbkToken)) {
            return self::WEBPAY_ERROR_FLOW;
        }

        if (isset($tbkIdSession) && isset($tbkToken) && !isset($tokenWs)) {
            $webpayFlow = self::WEBPAY_ABORTED_FLOW;
        }

        if (isset($tbkIdSession) && !isset($tbkToken) && !isset($tokenWs)) {
            $webpayFlow = self::WEBPAY_TIMEOUT_FLOW;
        }

        if (isset($tokenWs) && !isset($tbkToken) && !isset($tbkIdSession)) {
            $webpayFlow = self::WEBPAY_NORMAL_FLOW;
        }

        return $webpayFlow;
    }

    private function handleNormalFlow(string $token): void
    {
        $this->logger->logInfo("Procesando transacción por flujo Normal => token: {$token}");

        if ($this->checkTransactionIsAlreadyProcessed($token)) {
            $this->handleTransactionAlreadyProcessed($token);
            return;
        }

        $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($token);
        $cart = $this->getCart($webpayTransaction->cart_id);

        if ($webpayTransaction->amount != $this->getOrderTotalRound($cart)) {
            $this->handleCartManipulated($webpayTransaction);
            return;
        }

        $transbankSdk = WebpayPlusFactory::create();
        $commitResponse = $transbankSdk->commitTransaction($token);

        if ($commitResponse->isApproved()) {
            $this->handleAuthorizedTransaction(
                $cart,
                $webpayTransaction,
                $commitResponse
            );
        } else {
            $this->handleUnauthorizedTransaction($cart, $webpayTransaction, $commitResponse);
        }
    }

    private function handleFlowTimeout(string $buyOrder): void
    {
        $this->logger->logInfo("Procesando transacción por flujo timeout => Orden de compra: {$buyOrder}");

        $webpayTransaction = $this->getTransbankWebpayRestTransactionByBuyOrder($buyOrder);

        if ($this->checkTransactionIsAlreadyProcessedByStatus($webpayTransaction->status)) {
            $this->handleTransactionAlreadyProcessed($webpayTransaction->token);
            return;
        }

        $this->handleAbortedTransaction(
            $webpayTransaction,
            TransbankWebpayRestTransaction::STATUS_TIMEOUT,
            self::WEBPAY_TIMEOUT_FLOW_MESSAGE
        );
    }

    private function handleFlowAborted(string $token): void
    {
        $this->logger->logInfo("Procesando transacción por flujo de pago abortado => Token: {$token}");

        $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($token);

        if ($this->checkTransactionIsAlreadyProcessedByStatus($webpayTransaction->status)) {
            $this->handleTransactionAlreadyProcessed($token);
            return;
        }

        $this->handleAbortedTransaction(
            $webpayTransaction,
            TransbankWebpayRestTransaction::STATUS_ABORTED_BY_USER,
            self::WEBPAY_CANCELED_BY_USER_FLOW_MESSAGE
        );
    }

    private function handleFlowError(string $token): void
    {
        $this->logger->logInfo("Procesando transacción por flujo de error en formulario de pago => Token: {$token}");

        $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($token);

        if ($this->checkTransactionIsAlreadyProcessed($token)) {
            $this->handleTransactionAlreadyProcessed($token);
            return;
        }

        $this->handleAbortedTransaction(
            $webpayTransaction,
            TransbankWebpayRestTransaction::STATUS_ERROR,
            self::WEBPAY_ERROR_FLOW_MESSAGE
        );
    }

    private function handleAuthorizedTransaction(
        Cart $cart,
        TransbankWebpayRestTransaction $webpayTransaction,
        TransactionCommitResponse $commitResponse
    ): void {
        $token = $webpayTransaction->token;
        $this->logger->logInfo("Transacción autorizada por Transbank, procesando orden con token: {$token}");

        $webpayTransaction->transbank_response = json_encode($commitResponse);
        $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_APPROVED;
        $webpayTransaction->response_code = $commitResponse->getResponseCode();
        $webpayTransaction->card_number = $commitResponse->getCardNumber();
        $webpayTransaction->vci = $commitResponse->getVci();
        $saved = $webpayTransaction->save();

        if (!$saved) {
            $message = "No se pudo actualizar la transacción en la tabla webpay_transactions con token: {$token}";
            $this->logError($message);
            throw new EcommerceException($message);
        }

        $customer = $this->getCustomer($cart->id_customer);
        $currency = Context::getContext()->currency;
        $okStatus = $this->getWebpayOkStatus();

        $this->module->validateOrder(
            $cart->id,
            $okStatus,
            $webpayTransaction->amount,
            $this->module->displayName,
            'Pago autorizado',
            [],
            $currency->id,
            false,
            $customer->secure_key
        );

        $idOrder = Order::getIdByCartId($cart->id);
        $order = new Order($idOrder);

        $this->logger->logInfo("Orden creada. Order ID: {$order->id} Cart ID: {$cart->id} Token: {$token}");

        $webpayTransaction->order_id = $order->id;
        $webpayTransaction->save();

        $this->saveOrderPayment($order, $cart, $commitResponse->getCardNumber());

        $this->redirectToPaidSuccessPaymentPage($cart);
    }

    private function handleUnauthorizedTransaction(
        Cart $cart,
        TransbankWebpayRestTransaction $webpayTransaction,
        TransactionCommitResponse $commitResponse
    ): void {
        $token = $webpayTransaction->token;
        $this->logger->logInfo("Transacción rechazada por Transbank con token: {$token}");

        $webpayTransaction->transbank_response = json_encode($commitResponse);
        $webpayTransaction->response_code = $commitResponse->getResponseCode();
        $webpayTransaction->card_number = $commitResponse->getCardNumber();
        $webpayTransaction->vci = $commitResponse->getVci();
        $saved = $webpayTransaction->save();

        if (!$saved) {
            $message = "No se pudo actualizar la transacción en la tabla webpay_transactions con token: {$token}";
            $this->logError($message);
            throw new EcommerceException($message);
        }

        $this->handleAbortedTransaction(
            $webpayTransaction,
            TransbankWebpayRestTransaction::STATUS_FAILED,
            self::WEBPAY_FAILED_FLOW_MESSAGE
        );
    }

    private function handleAbortedTransaction(TransbankWebpayRestTransaction $webpayTransaction, int $status, string $message): void
    {
        $this->logger->logInfo("Error al procesar transacción por Transbank => token: {$webpayTransaction->token}");
        $this->logger->logInfo("Detalle: {$message}");

        $webpayTransaction->status = $status;
        $webpayTransaction->save();

        $this->setPaymentErrorPage($message);
    }

    private function handleTransactionAlreadyProcessed(string $token): void
    {
        $this->logger->logInfo("Transacción ya se encontraba procesada. Token: {$token}");

        $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($token);
        $status = $webpayTransaction->status;
        $message = self::WEBPAY_EXCEPTION_FLOW_MESSAGE;

        $this->logger->logInfo("Estado de la transacción => {$status}");

        if ($status == TransbankWebpayRestTransaction::STATUS_APPROVED) {
            $cart = $this->getCart($webpayTransaction->cart_id);
            $this->redirectToPaidSuccessPaymentPage($cart);
            return;
        }

        if ($status == TransbankWebpayRestTransaction::STATUS_FAILED) {
            $message = self::WEBPAY_FAILED_FLOW_MESSAGE;
        }

        if ($status == TransbankWebpayRestTransaction::STATUS_ABORTED_BY_USER) {
            $message = self::WEBPAY_CANCELED_BY_USER_FLOW_MESSAGE;
        }

        if ($status == TransbankWebpayRestTransaction::STATUS_TIMEOUT) {
            $message = self::WEBPAY_TIMEOUT_FLOW_MESSAGE;
        }

        if ($status == TransbankWebpayRestTransaction::STATUS_ERROR) {
            $message = self::WEBPAY_ERROR_FLOW_MESSAGE;
        }

        $this->setPaymentErrorPage($message);
    }

    private function handleCartManipulated($webpayTransaction): void
    {
        $this->logger->logInfo("El carro fue modificado mientras se procesaba el pago. Token: {$webpayTransaction->token}");

        $this->handleAbortedTransaction(
            $webpayTransaction,
            TransbankWebpayRestTransaction::STATUS_FAILED,
            self::WEBPAY_CART_MANIPULATED_MESSAGE
        );
    }

    private function checkTransactionIsAlreadyProcessed(string $token): bool
    {
        $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($token);

        if (is_null($webpayTransaction)) {
            return false;
        }

        return $webpayTransaction->status != TransbankWebpayRestTransaction::STATUS_INITIALIZED;
    }

    private function checkTransactionIsAlreadyProcessedByStatus(string $status): bool
    {
        return $status != TransbankWebpayRestTransaction::STATUS_INITIALIZED;
    }
}

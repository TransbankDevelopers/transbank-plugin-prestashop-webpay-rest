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

    const WEBPAY_FAILED_FLOW_MESSAGE = 'Tu transacción no pudo ser autorizada. Ningún cobro fue realizado.';
    const WEBPAY_CANCELED_BY_USER_FLOW_MESSAGE = 'Orden cancelada por el usuario.';
    const WEBPAY_TIMEOUT_FLOW_MESSAGE = 'Orden cancelada por inactividad del usuario en el formulario de pago.';
    const WEBPAY_ERROR_FLOW_MESSAGE = 'Orden cancelada por un error en el formulario de pago';
    const WEBPAY_EXCEPTION_FLOW_MESSAGE = 'No se pudo procesar el pago.';

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

            $this->handleRequest($request);
        } catch (\Exception | \Error $e) {
            $this->logger->logError('Error en el proceso de validación de pago: ' . $e->getMessage());
            // TODO: mejorar mensaje de error. Trabajar con uno genérico que llame a reintentar la operación o contactar al comercio en caso de problemas
            $this->setPaymentErrorPage($e->getMessage());
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
            // TODO: Implementar flujo de pago abortado
            // $this->handleFlowAborted($request['TBK_TOKEN']);
        }

        if ($webpayFlow == self::WEBPAY_ERROR_FLOW) {
            // TODO: Implementar flujo de error
            // $this->handleFlowError($request['token_ws']);
        }
    }

    private function getWebpayFlow(array $request): string
    {
        $tokenWs = $request['token_ws'] ?? null;
        $tbkToken = $request['TBK_TOKEN'] ?? null;
        $tbkIdSession = $request['TBK_ID_SESION'] ?? null;
        $webpayFlow = self::WEBPAY_ERROR_FLOW;

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

    private function handleNormalFlow(string $token)
    {
        $this->logger->logInfo('Procesando transacción por flujo Normal => token: ' . $token);

        if ($this->checkTransactionIsAlreadyProcessed($token)) {
            return $this->handleTransactionAlreadyProcessed($token);
        }

        $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($token);
        $cart = $this->getCart($webpayTransaction->cart_id);

        // TODO: Agregar flujo de carro manipulado

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

    private function handleFlowTimeout(string $buyOrder)
    {
        $this->logger->logInfo('Procesando transacción por flujo timeout => Orden de compra: ' . $buyOrder);

        $message = self::WEBPAY_TIMEOUT_FLOW_MESSAGE;

        $webpayTransaction = $this->getTransbankWebpayRestTransactionByBuyOrder($buyOrder);
        $token = $webpayTransaction->token;

        if ($this->checkTransactionIsAlreadyProcessedByStatus($webpayTransaction->status)) {
            return $this->handleTransactionAlreadyProcessed($token);
        }

        return $this->handleAbortedTransaction(
            $token,
            $message,
            $webpayTransaction,
            TransbankWebpayRestTransaction::STATUS_TIMEOUT
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

        $message = self::WEBPAY_FAILED_FLOW_MESSAGE;

        $webpayTransaction->transbank_response = json_encode($commitResponse);
        $webpayTransaction->status = TransbankWebpayRestTransaction::STATUS_FAILED;
        $webpayTransaction->response_code = $commitResponse->getResponseCode();
        $webpayTransaction->card_number = $commitResponse->getCardNumber();
        $webpayTransaction->vci = $commitResponse->getVci();
        $saved = $webpayTransaction->save();

        if (!$saved) {
            $message = "No se pudo actualizar la transacción en la tabla webpay_transactions con token: {$token}";
            $this->logError($message);
            throw new EcommerceException($message);
        }

        $this->setPaymentErrorPage(self::WEBPAY_FAILED_FLOW_MESSAGE);
    }

    private function handleAbortedTransaction(string $token, string $message, TransbankWebpayRestTransaction $webpayTransaction, int $status)
    {
        $this->logger->logInfo('Error al procesar transacción por Transbank => token: ' . $token);
        $this->logger->logInfo('Detalle: ' . $message);

        $webpayTransaction->status = $status;
        $webpayTransaction->save();

        return $this->setPaymentErrorPage($message);
    }

    private function handleTransactionAlreadyProcessed(string $token)
    {
        $this->logger->logInfo("Transacción ya se encontraba procesada. Token: {$token}");

        $webpayTransaction = $this->getTransbankWebpayRestTransactionByToken($token);
        $status = $webpayTransaction->status;
        $message = self::WEBPAY_EXCEPTION_FLOW_MESSAGE;

        $this->logger->logInfo('Estado de la transacción => ' . $status);

        if ($status == TransbankWebpayRestTransaction::STATUS_APPROVED) {
            $cart = $this->getCart($webpayTransaction->cart_id);
            $this->redirectToPaidSuccessPaymentPage($cart);
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

    // TODO: Validar si es necesario realizar esto.
    private function validateData($cart)
    {
        if (!$this->module->active) {
            $error = 'El módulo no esta activo';
            $this->logError($error);
            $this->throwErrorRedirect($error);
        }

        if ($cart->id == null) {
            $error = 'Cart id was null. Redirect to confirmation page of the last order';
            $this->logError($error);
            $id_usuario = Context::getContext()->customer->id;
            $sql = 'SELECT id_cart FROM ' . _DB_PREFIX_ . "cart p WHERE p.id_customer = $id_usuario ORDER BY p.id_cart DESC";
            $cart->id = SqlHelper::getValue($sql);
            $customer = $this->getCustomer($cart->id_customer);
            Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int) $cart->id . '&id_module='
                . (int) $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
        }

        if ($cart->id_customer == 0) {
            $error = 'id_customer es cero';
            $this->logError($error);
            $this->throwErrorRedirect($error);
        } elseif ($cart->id_address_delivery == 0) {
            $error = 'id_address_delivery es cero';
            $this->logError($error);
            $this->throwErrorRedirect($error);
        } elseif ($cart->id_address_invoice == 0) {
            $error = 'id_address_invoice es cero';
            $this->logError($error);
            $this->throwErrorRedirect($error);
        }

        $customer = $this->getCustomer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $error = 'Customer not load';
            $this->logError($error);
            $this->throwErrorRedirect($error);
        }
    }

    protected function handleCartManipulated($webpayTransaction)
    {
        $error = 'El monto del carro ha cambiado, la transacción no fue completada, ningún
        cargo será realizado en su tarjeta. Por favor, reintente el pago.';
        $message = 'Carro ha sido manipulado durante el proceso de pago';
        $this->updateTransactionStatus(
            $webpayTransaction,
            TransbankWebpayRestTransaction::STATUS_FAILED,
            json_encode(['error' => $message])
        );
        $this->setPaymentErrorPage($error);
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @return void
     */
    private function stopIfComingFromAnTimeoutErrorOnWebpay($sessionId)
    {
        $webpayTransaction = $this->getTransbankWebpayRestTransactionBySessionId($sessionId);
        $errorMessage = "Al parecer pasaron más de 15 minutos en el formulario de pago,
            por lo que la transacción se ha cancelado automáticamente";
        if (!isset($webpayTransaction)) {
            $this->setPaymentErrorPage($errorMessage);
        }

        if ($webpayTransaction->status == TransbankWebpayRestTransaction::STATUS_APPROVED) {
            $cart = $this->getCart($webpayTransaction->cart_id);
            $this->redirectToPaidSuccessPaymentPage($cart);
        }
        $this->updateTransactionStatus(
            $webpayTransaction,
            TransbankWebpayRestTransaction::STATUS_FAILED,
            json_encode(['error' => $errorMessage])
        );
        $this->setPaymentErrorPage($errorMessage);
    }

    private function updateTransactionStatus($tx, $status, $tbkResponse)
    {
        $tx->status = $status;
        $tx->transbank_response = $tbkResponse;
        $tx->save();
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

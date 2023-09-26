<?php

use PrestaShop\Module\WebpayPlus\Controller\PaymentModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Helpers\TbkConstans;
use Transbank\Plugin\Exceptions\Webpay\CommitTbkWebpayException;
use Transbank\Plugin\Exceptions\Webpay\TimeoutWebpayException;
use Transbank\Plugin\Exceptions\Webpay\UserCancelWebpayException;
use Transbank\Plugin\Exceptions\Webpay\DoubleTokenWebpayException;
use Transbank\Plugin\Exceptions\Webpay\InvalidStatusWebpayException;
use Transbank\Plugin\Exceptions\Webpay\AlreadyApprovedWebpayException;
use Transbank\Plugin\Exceptions\Webpay\OrderAlreadyPaidException;
use Transbank\Plugin\Exceptions\Webpay\RejectedCommitWebpayException;
use Transbank\Plugin\Model\TransbankTransactionDto;

class WebPayWebpayplusPaymentValidateModuleFrontController extends PaymentModuleFrontController
{
    protected $responseData = [];

    public function initContent()
    {
        parent::initContent();
        $this->logger = TbkFactory::createLogger();
        $cart = null;
        $transaction = null;
        try {
            $tbkWebpayplus = TbkFactory::createTbkWebpayplusService();
            $transaction = $tbkWebpayplus->processRequestFromTbkReturn($_SERVER, $_GET, $_POST);
            $cart = $this->getCart($transaction->getOrderId());
            $this->validateData($cart, $tbkWebpayplus, $transaction);
            $amount = $this->getOrderTotal($cart);
            $commitResponse = $tbkWebpayplus->commitTransaction($transaction->getToken());
            $customer = $this->getCustomer($cart->id_customer);
            $currency = Context::getContext()->currency;
            $this->module->validateOrder(
                (int) $cart->id,
                $tbkWebpayplus->getOrderStatusAfterPayment(),
                $amount,
                $this->module->displayName,
                'Pago exitoso',
                [],
                (int) $currency->id,
                false,
                $customer->secure_key
            );
            $order = new Order($this->module->currentOrder);
            $this->saveOrderPayment($order, $cart, $commitResponse->getCardNumber());
            if ($tbkWebpayplus->getOrderStatusAfterPayment() === $order->current_state){
                $tbkWebpayplus->commitTransactionEcommerce($transaction->getBuyOrder());
            }
            return $this->redirectToPaidSuccessPaymentPage($cart);
        } catch (TimeoutWebpayException $e) {
            $msg = 'Al parecer pasaron más de 15 minutos en el formulario de pago, por lo que la transacción se ha cancelado automáticamente';
            $this->setPaymentErrorPage($msg);
        } catch (UserCancelWebpayException $e) {
            $msg = 'Transacción abortada desde el formulario de pago. Puedes reintentar el pago. ';
            $this->setPaymentErrorPage($msg);
        } catch (DoubleTokenWebpayException $e) {
            $msg = 'Al parecer ocurrió un error durante el proceso de pago. Puedes volver a intentar. ';
            $this->setPaymentErrorPage($msg);
        } catch (InvalidStatusWebpayException $e) {
            $msg = 'Esta compra se encuentra en estado rechazado o cancelado y no se puede aceptar el pago';
            $this->setPaymentErrorPage($msg);
        } catch (OrderAlreadyPaidException $e) {
            $msg = 'Otra transacción de este carro de compras ya fue aprobada. Se rechazo este pago para no generar un cobro duplicado';
            $this->setPaymentErrorPage($msg);
        } catch (AlreadyApprovedWebpayException $e) {
            return $this->redirectToPaidSuccessPaymentPage($cart);
        } catch (RejectedCommitWebpayException $e) {
            $this->responseData['PAYMENT_OK'] = 'FAIL';
            $error = 'La transacción ha sido rechazada. Por favor, reintente el pago. ';
            $detail = 'Código de respuesta: '.$e->getCommitResponse()->getResponseCode().'. Estado: '.$e->getCommitResponse()->getStatus();
            $this->setPaymentErrorPage($error, $detail);
        } catch (CommitTbkWebpayException $e) {
            $this->responseData['PAYMENT_OK'] = 'FAIL';
            $error = 'Error en el pago';
            $detail = 'Indefinido';
            $this->setPaymentErrorPage($error, $detail);
        } catch (\Exception $e) {
            $msg = 'Al parecer ocurrió un error durante el proceso de pago. Puedes volver a intentar. ';
            $tbkWebpayplus->saveTransactiondFailed($transaction, TbkConstans::WEBPAYPLUS_COMMIT, $e->getMessage(), $msg);
            $this->setPaymentErrorPage($e->getMessage(), $msg);
        }

    }

    private function validateData($cart, $tbkWebpayplus, TransbankTransactionDto $transaction)
    {
        if (!$this->module->active) {
            $error = 'El módulo no esta activo';
            $tbkWebpayplus->saveTransactiondFailed($transaction, TbkConstans::WEBPAYPLUS_COMMIT, null, $error);
            $this->throwErrorRedirect($error);
        } 

        if ($cart->id == null) {
            $error = 'Cart id was null. Redirecto to confirmation page of the last order';
            $tbkWebpayplus->saveTransactiondFailed($transaction, TbkConstans::WEBPAYPLUS_COMMIT, null, $error);

            $id_usuario = Context::getContext()->customer->id;
            $sql = 'SELECT id_cart FROM '._DB_PREFIX_."cart p WHERE p.id_customer = $id_usuario ORDER BY p.id_cart DESC";
            $cart->id = Db::getInstance()->getValue($sql);
            $customer = $this->getCustomer($cart->id_customer);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
        }

        if ($cart->id_customer == 0) {
            $error = 'id_customer es cero';
            $tbkWebpayplus->saveTransactiondFailed($transaction, TbkConstans::WEBPAYPLUS_COMMIT, null, $error);
            $this->throwErrorRedirect($error);
        } 
        else if ($cart->id_address_delivery == 0) {
            $error = 'id_address_delivery es cero';
            $tbkWebpayplus->saveTransactiondFailed($transaction, TbkConstans::WEBPAYPLUS_COMMIT, null, $error);
            $this->throwErrorRedirect($error);
        }
        else if ($cart->id_address_invoice == 0) {
            $error = 'id_address_invoice es cero';
            $tbkWebpayplus->saveTransactiondFailed($transaction, TbkConstans::WEBPAYPLUS_COMMIT, null, $error);
            $this->throwErrorRedirect($error);
        }

        $customer = $this->getCustomer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $error = 'Customer not load';
            $tbkWebpayplus->saveTransactiondFailed($transaction, TbkConstans::WEBPAYPLUS_COMMIT, null, $error);
            $this->throwErrorRedirect($error);
        }

        if ($transaction->amount != $this->getOrderTotalRound($cart)) {
            $error = 'El monto del carro ha cambiado, la transacción no fue completada, ningún cargo será realizado en su tarjeta. Por favor, reintente el pago.';
            $tbkWebpayplus->saveTransactiondFailed($transaction, TbkConstans::WEBPAYPLUS_COMMIT, null,'Carro ha sido manipulado durante el proceso de pago');
            $this->setPaymentErrorPage($error);
        }
    }

}

<?php

use PrestaShop\Module\WebpayPlus\Controller\PaymentModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Exceptions\CreateTransactionDbException;
use Transbank\Plugin\Exceptions\Oneclick\AuthorizeTbkOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\RejectedAuthorizeOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\ConstraintsViolatedAuthorizeOneclickException;

/**
 * Class WebPayOneclickPaymentValidateModuleFrontController.
 */
class WebPayOneclickPaymentValidateModuleFrontController extends PaymentModuleFrontController
{
    protected $responseData = [];

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $this->logger = TbkFactory::createLogger();
        try {
            $cart = $this->context->cart;
            $customer = new Customer($cart->id_customer);
            $moduleId = $this->module->id;
            $this->validate($cart, $customer);
            $currency = $this->context->currency;
            $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
            $data = $_REQUEST;
            $tbkOneclick = TbkFactory::createTbkOneclickService($this->getCurrentStoreId());
            $authorizeResponse = $tbkOneclick->authorize($data['username'], $cart->id, $total);
            $this->module->validateOrder(
                (int) $cart->id,
                $tbkOneclick->getOrderStatusAfterPayment(),
                $total,
                'Webpay Oneclick',
                'Pago exitoso',
                []/* variables */,
                (int) $currency->id,
                false,
                $customer->secure_key
            );

            $order = new Order($this->module->currentOrder);
            $this->saveOrderPayment($order, $cart, $authorizeResponse->getCardNumber());
            if ($tbkOneclick->getOrderStatusAfterPayment() === $order->current_state){
                $tbkOneclick->commitTransactionEcommerce($authorizeResponse->getBuyOrder());
            }
            $this->redirectToPaidSuccessPaymentPage($cart);
        } catch (CreateTransactionDbException $e) {
            $this->throwErrorRedirect('Pago rechazado', $e->getMessage());
        } catch (AuthorizeTbkOneclickException $e) {
            $this->throwErrorRedirect('Pago rechazado', $e->getMessage());
        } catch (RejectedAuthorizeOneclickException $e) {
            $this->throwErrorRedirect('Pago rechazado', $e->getMessage());
        } catch (ConstraintsViolatedAuthorizeOneclickException $e) {
            $this->throwErrorRedirect('Pago rechazado', $e->getMessage());
        } catch (\Exception $e) {
            $this->throwErrorRedirect('Pago rechazado', $e->getMessage());
        }
    }
}

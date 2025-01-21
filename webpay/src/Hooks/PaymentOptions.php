<?php

namespace PrestaShop\Module\WebpayPlus\Hooks;

use Cart;
use Context;
use Currency;

use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclick;

class PaymentOptions implements HookHandlerInterface
{
    use InteractsWithWebpay;
    use InteractsWithOneclick;

    /**
     * @var Context Instance of the ecommerce Context.
     */
    private $context;

    /**
     * @var array Currencies supported for the module.
     */
    private $moduleCurrencies;

    /**
     * Constructor.
     * Initializes the class.
     *
     * @param array $moduleCurrencies Currencies supported for the module.
     */
    public function __construct(array $moduleCurrencies)
    {
        $this->context = Context::getContext();
        $this->moduleCurrencies = $moduleCurrencies;
    }

    /**
     * Executes the hook logic to display payment details.
     *
     * @param array $params The parameters passed to the hook..
     * @return array Array of payment options.
     */
    public function execute(array $params): array
    {
        $paymentOptions = [];

        if (!$this->checkCurrency($params['cart'])) {
            return $paymentOptions;
        }

        if ($this->configWebpayIsOk()) {
            $paymentOptions[] = $this->getWebpayPaymentOption();
        }

        if ($this->configOneclickIsOk()) {
            array_push($paymentOptions, ...$this->getGroupOneclickPaymentOption());
        }
        return $paymentOptions;
    }

    /**
     * Check if payment is valid for the current cart currency.
     *
     * @param Cart $cart The current cart.
     * @return bool true if is valid, otherwise false.
     */
    private function checkCurrency(Cart $cart): bool
    {
        $currency_order = new Currency($cart->id_currency);
        if (is_array($this->moduleCurrencies)) {
            foreach ($this->moduleCurrencies as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }
}

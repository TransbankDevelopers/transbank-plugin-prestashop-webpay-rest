<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

final class OneclickDataConfiguration implements DataConfigurationInterface
{
    public const ONECLICK_MALL_COMMERCE_CODE = 'ONECLICK_MALL_COMMERCE_CODE';
    public const ONECLICK_CHILD_COMMERCE_CODE = 'ONECLICK_CHILD_COMMERCE_CODE';
    public const ONECLICK_API_KEY = 'ONECLICK_API_KEY';
    public const ONECLICK_ENVIRONMENT = 'ONECLICK_ENVIRONMENT';
    public const ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT = 'ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT';
    public const ONECLICK_ACTIVE = 'ONECLICK_ACTIVE';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(): array
    {
        $oneclickActive = $this->configuration->get(static::ONECLICK_ACTIVE);
        $oneclickEnviroment = $this->configuration->get(static::ONECLICK_ENVIRONMENT);
        $oneclickMallCommerceCode = $this->configuration->get(static::ONECLICK_MALL_COMMERCE_CODE);
        $oneclickChildCommerceCode = $this->configuration->get(static::ONECLICK_CHILD_COMMERCE_CODE);
        $oneclickApikey = $this->configuration->get(static::ONECLICK_API_KEY);
        $oneclickDefaultOrderStateIdAfterPayment = $this->configuration->get(static::ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT);
        return [
            'form_oneclick_active' => $oneclickActive,
            'form_oneclick_environment' => $oneclickEnviroment,
            'form_oneclick_mall_commerce_code' => $oneclickMallCommerceCode,
            'form_oneclick_child_commerce_code' => $oneclickChildCommerceCode,
            'form_oneclick_api_key' => $oneclickApikey,
            'form_oneclick_order_after_payment' => $oneclickDefaultOrderStateIdAfterPayment
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updateConfiguration(array $configuration): array
    {
        $this->configuration->set(static::ONECLICK_ACTIVE, $configuration['form_oneclick_active']);
        $this->configuration->set(static::ONECLICK_MALL_COMMERCE_CODE, $configuration['form_oneclick_mall_commerce_code']);
        $this->configuration->set(static::ONECLICK_CHILD_COMMERCE_CODE, $configuration['form_oneclick_child_commerce_code']);
        $this->configuration->set(static::ONECLICK_API_KEY, $configuration['form_oneclick_api_key']);
        $this->configuration->set(static::ONECLICK_ENVIRONMENT, $configuration['form_oneclick_environment']);
        $this->configuration->set(static::ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT, $configuration['form_oneclick_order_after_payment']);
        return [];
    }

    /**
     * Ensure the parameters passed are valid.
     * This function can be used to validate updateConfiguration(array $configuration) data input.
     *
     * @param array $configuration
     *
     * @return bool Returns true if no exception are thrown
     */
    public function validateConfiguration(array $configuration): bool
    {
        return true;
    }

}

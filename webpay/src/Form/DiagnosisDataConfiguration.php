<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

final class DiagnosisDataConfiguration implements DataConfigurationInterface
{
    public const WEBPAY_DEBUG_ACTIVE = 'WEBPAY_DEBUG_ACTIVE';
    public const ENABLED = "1";
    public const DISABLED = "2";

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
        $return = [];

        if ($debugActive = $this->configuration->get(static::WEBPAY_DEBUG_ACTIVE)) {
            $return['form_debug_active'] = $debugActive;
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function updateConfiguration(array $configuration): array
    {
        $this->configuration->set(static::WEBPAY_DEBUG_ACTIVE, $configuration['form_debug_active']);
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

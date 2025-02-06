<?php

namespace PrestaShop\Module\WebpayPlus\Config;

use Configuration;

abstract class AbstractModuleConfig implements ModuleConfigInterface
{
    /**
     * Get a configuration value.
     *
     * @param string $key The key of the configuration value.
     * @return string|null The value of the configuration, or null if it does not exist.
     */
    protected static function getConfigValue(string $key): ?string
    {
        $value = Configuration::get($key);
        return $value ?: null;
    }

    /**
     * Set a configuration value.
     *
     * @param string $key The key of the configuration value.
     * @param string $value The value to set.
     */
    protected static function setConfigValue(string $key, string $value): void
    {
        Configuration::updateValue($key, $value);
    }
}

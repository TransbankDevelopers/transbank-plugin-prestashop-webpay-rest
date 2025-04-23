<?php

namespace PrestaShop\Module\WebpayPlus\Hooks;

use Transbank\Plugin\Helpers\PluginLogger;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;

abstract class AbstractHookHandler implements HookHandlerInterface
{
    /**
     * @var PluginLogger Instance of the logger.
     */
    protected $logger;

    public function __construct()
    {
        $this->logger = TbkFactory::createLogger();
    }

    protected function logInfo(string $message): void
    {
        $this->logger->logInfo($message);
    }

    protected function logDebug(string $message): void
    {
        if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
            $this->logger->logDebug($message);
        }
    }

    protected function logError(string $message): void
    {
        $this->logger->logError($message);
    }
}

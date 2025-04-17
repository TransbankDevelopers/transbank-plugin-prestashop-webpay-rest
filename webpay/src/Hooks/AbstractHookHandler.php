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

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->logger = TbkFactory::createLogger();
    }

    /**
     * Methods.
     */

    protected function logInfo(string $message): void
    {
        $this->logger->logInfo($message);
    }

    protected function logDebug(string $message): void
    {
        $this->logger->logDebug($message);
    }

    protected function logError(string $message): void
    {
        $this->logger->logError($message);
    }
}

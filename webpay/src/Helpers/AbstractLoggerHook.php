<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use Transbank\Plugin\Helpers\PluginLogger;

abstract class AbstractLoggerHook
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
     * INFO Log
     *
     * @param string $message
     */
    protected function logInfo(string $message): void
    {
        $this->logger->logInfo($message);
    }

    /**
     * DEBUG Log
     *
     * @param string $message
     */
    protected function logDebug(string $message): void
    {
        $this->logger->logDebug($message);
    }

    /**
     * ERROR Log
     *
     * @param string $message
     */
    protected function logError(string $message): void
    {
        $this->logger->logError($message);
    }
}

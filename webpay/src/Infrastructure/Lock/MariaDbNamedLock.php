<?php

namespace PrestaShop\Module\WebpayPlus\Infrastructure\Lock;

use Db;
use PrestaShop\Module\WebpayPlus\Exceptions\MariaDbNamedLockException;

/**
 * Thin MariaDB named lock adapter.
 *
 * It provides a reusable acquire/release primitive for flows that need to serialize work by key.
 */
class MariaDbNamedLock
{
    private const LOCK_PREFIX = 'transbank_webpay_lock_';

    public function acquire(string $key): bool
    {
        $lockName = $this->buildLockName($key);
        $escapedLockName = pSQL($lockName);
        $query = "SELECT GET_LOCK('$escapedLockName', 0)";
        $result = Db::getInstance()->getValue($query);

        if ($result === null) {
            throw new MariaDbNamedLockException(
                'No se pudo adquirir el lock de retorno de Webpay: error al consultar MariaDB.'
            );
        }

        return $result === 1;
    }

    public function release(string $key): bool
    {
        $lockName = $this->buildLockName($key);
        $escapedLockName = pSQL($lockName);
        $query = "SELECT RELEASE_LOCK('$escapedLockName')";
        $result = Db::getInstance()->getValue($query);

        if ($result === null) {
            throw new MariaDbNamedLockException(
                'No se pudo liberar el lock de retorno de Webpay: error al consultar MariaDB.'
            );
        }

        return $result === 1;
    }

    private function buildLockName(string $key): string
    {
        return self::LOCK_PREFIX . substr(hash('sha256', $key), 0, 40);
    }
}

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
    private const GET_LOCK_TIMEOUT_SECONDS = 5;

    public function acquire(string $key): bool
    {
        $escapedKey = pSQL($key);
        $timeout = self::GET_LOCK_TIMEOUT_SECONDS;
        $query = "SELECT GET_LOCK('$escapedKey', $timeout)";
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
        $escapedKey = pSQL($key);
        $query = "SELECT RELEASE_LOCK('$escapedKey')";
        $result = Db::getInstance()->getValue($query);

        if ($result === null) {
            throw new MariaDbNamedLockException(
                'No se pudo liberar el lock de retorno de Webpay: error al consultar MariaDB.'
            );
        }

        return $result === 1;
    }
}

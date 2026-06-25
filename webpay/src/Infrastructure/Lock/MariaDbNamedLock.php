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
    private const MAX_LOCK_NAME_LENGTH = 64;

    public function acquire(string $key): bool
    {
        $this->validateKeyLength($key);
        $escapedKey = pSQL($key);
        $timeout = self::GET_LOCK_TIMEOUT_SECONDS;
        $query = sprintf("SELECT GET_LOCK('%s', %d)", $escapedKey, $timeout);
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
        $this->validateKeyLength($key);
        $escapedKey = pSQL($key);
        $query = sprintf("SELECT RELEASE_LOCK('%s')", $escapedKey);
        $result = Db::getInstance()->getValue($query);

        if ($result === null) {
            throw new MariaDbNamedLockException(
                'No se pudo liberar el lock de retorno de Webpay: error al consultar MariaDB.'
            );
        }

        return $result === 1;
    }

    private function validateKeyLength(string $key): void
    {
        if (strlen($key) > self::MAX_LOCK_NAME_LENGTH) {
            throw new MariaDbNamedLockException(
                'El nombre del lock excede el límite de ' . self::MAX_LOCK_NAME_LENGTH . ' caracteres de MySQL.'
            );
        }
    }
}

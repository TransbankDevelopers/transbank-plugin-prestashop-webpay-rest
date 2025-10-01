<?php

namespace PrestaShop\Module\WebpayPlus\Repository;

use Db;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;

/**
 * Class InscriptionRepository.
 * This class is responsible for managing the inscription data.
 */
class InscriptionRepository
{
    /**
     * Get inscription by custom conditions.
     *
     * @param array $conditions Key-value pairs of column names and values.
     *
     * @return array Inscriptions data.
     */
    private function getInscriptionsByConditions(array $conditions): array
    {
        $tableName = pSQL(_DB_PREFIX_ . TransbankInscriptions::TABLE_NAME);

        $whereClauses = [];
        foreach ($conditions as $column => $value) {
            $sanitizedColumn = pSQL($column);
            $sanitizedValue = pSQL($value);
            $whereClauses[] = "`{$sanitizedColumn}` = '{$sanitizedValue}'";
        }

        $whereSql = implode(' AND ', $whereClauses);
        $sql = "SELECT * FROM {$tableName} WHERE {$whereSql}";

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Get inscription by user ID.
     *
     * @param int $userId User ID.
     *
     * @return array Inscriptions data.
     */
    public function getCardsByUserId(string $userId): array
    {
        return $this->getInscriptionsByConditions([
            'user_id' => $userId,
            'status' => TransbankInscriptions::STATUS_COMPLETED,
        ]);
    }

    /**
     * Get a single inscription by user ID and inscription ID.
     *
     * @param string $userId
     * @param string $inscriptionId
     */
    public function getOneByUserIdAndInscriptionId(string $userId, string $inscriptionId): ?array
    {
        $results = $this->getInscriptionsByConditions([
            'user_id' => $userId,
            'id' => $inscriptionId,
            'status' => TransbankInscriptions::STATUS_COMPLETED,
        ]);

        return !empty($results) ? $results[0] : null;
    }
}

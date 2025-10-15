<?php

namespace PrestaShop\Module\WebpayPlus\Repository;

use DB;
use Validate;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

/**
 * Class TransactionRepository
 * Handles database operations for TransbankWebpayRestTransaction entities.
 */
class TransactionRepository
{
    /**
     * Find a transaction by its ID.
     *
     * @param int $id The ID of the transaction.
     *
     * @return TransbankWebpayRestTransaction|null The transaction object or null if not found.
     */
    public function findById(int $id): ?TransbankWebpayRestTransaction
    {
        $obj = new TransbankWebpayRestTransaction($id);
        return (Validate::isLoadedObject($obj)) ? $obj : null;
    }

    /**
     * Create a new transaction record.
     *
     * @param array $data Key-value pairs of inscription data.
     *
     * @return int The ID of the newly created inscription, or 0 on failure.
     */
    public function createTransaction(array $data): int
    {
        $transaction = new TransbankWebpayRestTransaction();
        $transaction = $this->fillTransactionFields($transaction, $data);

        return $transaction->add() ? (int) $transaction->id : 0;
    }

    /**
     * Update an existing transaction record by ID.
     *
     * @param int $id The ID of the transaction to update.
     * @param array $fields Key-value pairs of fields to update.
     *
     * @return bool True on success, false on failure.
     */
    public function updateById(int $id, array $fields): bool
    {
        $transaction = $this->findById($id);
        if (!$transaction) {
            return false;
        }

        $transaction = $this->fillTransactionFields($transaction, $fields);
        return $transaction->update();
    }

    /**
     * Fill the transaction object with provided data.
     *
     * @param TransbankWebpayRestTransaction $transaction The transaction object to fill.
     * @param array $data Key-value pairs of data to fill.
     */
    private function fillTransactionFields(TransbankWebpayRestTransaction $transaction, array $data): TransbankWebpayRestTransaction
    {
        $intFields = ['amount', 'status', 'currency_id'];
        $fillable = [
            'cart_id',
            'order_id',
            'buy_order',
            'amount',
            'token',
            'session_id',
            'status',
            'response_code',
            'currency_id',
            'vci',
            'commerce_code',
            'child_commerce_code',
            'product',
            'environment',
            'card_number',
            'transbank_response',
            'created_at'
        ];

        foreach ($data as $key => $value) {
            if (!in_array($key, $fillable, true)) {
                continue;
            }
            if (in_array($key, $intFields, true)) {
                $transaction->{$key} = ($value === null || $value === '') ? null : (int) $value;
            } else {
                $transaction->{$key} = ($value === null) ? null : (string) $value;
            }
        }

        return $transaction;
    }

    /**
     * Get transactions by custom conditions.
     *
     * @param array $conditions Key-value pairs of column names and values.
     *
     * @return array Transactions data.
     */
    private function getTransactionsByConditions(array $conditions): array
    {
        $tableName = pSQL(_DB_PREFIX_ . TransbankWebpayRestTransaction::TABLE_NAME);

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
     * Get approved transaction by order ID.
     *
     * @param int $orderId Order ID.
     *
     * @return \PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction|null Transaction data.
     */
    public function getTransactionWebpayApprovedByOrderId($orderId): ?TransbankWebpayRestTransaction
    {
        $transaction = $this->getTransactionsByConditions([
            'order_id' => $orderId,
            'status' => TransbankWebpayRestTransaction::STATUS_APPROVED,
        ]);

        return !empty($transaction) ? new TransbankWebpayRestTransaction($transaction[0]['id']) : null;
    }

    /**
     * Get transaction by token.
     *
     * @param string $token Token.
     *
     * @return \PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction|null Transaction data.
     */
    public function getTransactionWebpayByToken($token): ?TransbankWebpayRestTransaction
    {
        $transaction = $this->getTransactionsByConditions([
            'token' => $token
        ]);

        return !empty($transaction) ? new TransbankWebpayRestTransaction($transaction[0]['id']) : null;
    }

    /**
     * Get transaction by buy order.
     *
     * @param string $buyOrder Buy order.
     *
     * @return \PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction|null Transaction data.
     */
    public function getTransactionWebpayByBuyOrder($buyOrder): ?TransbankWebpayRestTransaction
    {
        $transaction = $this->getTransactionsByConditions([
            'buy_order' => $buyOrder
        ]);

        return !empty($transaction) ? new TransbankWebpayRestTransaction($transaction[0]['id']) : null;
    }
}

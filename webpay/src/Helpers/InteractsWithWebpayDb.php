<?php
namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

/**
 * Trait InteractsWithWebpayDb.
 */
trait InteractsWithWebpayDb
{
    /**
     * Get transactions by custom conditions.
     *
     * @param array $conditions Key-value pairs of column names and values.
     *
     * @return \PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction|null Transactions data.
     */
    private function getTransactionsByConditions(array $conditions): ?array
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

        return \Db::getInstance()->getRow($sql) ?: null;
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

        return $transaction ? new TransbankWebpayRestTransaction($transaction['id']) : null;
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

        return $transaction ? new TransbankWebpayRestTransaction($transaction['id']) : null;
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

        return $transaction ? new TransbankWebpayRestTransaction($transaction['id']) : null;
    }
}

<?php
namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

/**
 * Trait InteractsWithWebpayDb.
 */
trait InteractsWithWebpayDb
{
    public function getTransactionByOrderIdAndStatus($orderId, $status)
    {
        $sanitizedTableName = pSQL(_DB_PREFIX_ . TransbankWebpayRestTransaction::TABLE_NAME);
        $sanitizedOrderId = pSQL($orderId);
        $sanitizedStatus = pSQL($status);

        $sql = "SELECT * FROM {$sanitizedTableName} WHERE `order_id` = '{$sanitizedOrderId}' AND `status` = {$sanitizedStatus}";
        return \Db::getInstance()->getRow($sql);
    }

    public function getTransactionApprovedByCartId($cartId)
    {
        $sanitizedTableName = pSQL(_DB_PREFIX_ . TransbankWebpayRestTransaction::TABLE_NAME);
        $sanitizedStatus = pSQL(TransbankWebpayRestTransaction::STATUS_APPROVED);
        $sanitizedCartId = pSQL($cartId);

        $sql = "SELECT * FROM {$sanitizedTableName} WHERE `cart_id` = '{$sanitizedCartId}' AND `status` = {$sanitizedStatus}";
        return \Db::getInstance()->getRow($sql);
    }

    public function getTransactionApprovedByOrderId($orderId)
    {
        return $this->getTransactionByOrderIdAndStatus($orderId, TransbankWebpayRestTransaction::STATUS_APPROVED);
    }

    public function getTransbankWebpayRestTransactionByOrderId($orderId)
    {
        $transaction = $this->getTransactionApprovedByOrderId($orderId);
        return new TransbankWebpayRestTransaction($transaction['id']);
    }

    public function getTransbankWebpayRestTransactionByToken($token)
    {
        $sanitizedTableName = pSQL(_DB_PREFIX_ . TransbankWebpayRestTransaction::TABLE_NAME);
        $sanitizedToken = pSQL($token);

        $sql = "SELECT * FROM {$sanitizedTableName} WHERE `token` = '{$sanitizedToken}'";
        $result = \Db::getInstance()->getRow($sql);
        if ($result === false) {
            return null;
        }
        return new TransbankWebpayRestTransaction($result['id']);
    }

    public function getTransbankWebpayRestTransactionBySessionId($sessionId): ?TransbankWebpayRestTransaction
    {
        $sanitizedTableName = pSQL(_DB_PREFIX_ . TransbankWebpayRestTransaction::TABLE_NAME);
        $sanitizedSessionId = pSQL($sessionId);

        $sql = "SELECT * FROM {$sanitizedTableName} WHERE `session_id` = '{$sanitizedSessionId}'";
        $result = \Db::getInstance()->getRow($sql);
        if ($result === false) {
            return null;
        }
        return new TransbankWebpayRestTransaction($result['id']);
    }

    public function getTransbankWebpayRestTransactionByBuyOrder($buyOrder): ?TransbankWebpayRestTransaction
    {
        $sanitizedTableName = pSQL(_DB_PREFIX_ . TransbankWebpayRestTransaction::TABLE_NAME);
        $sanitizedBuyOrder = pSQL($buyOrder);
        $sql = "SELECT * FROM {$sanitizedTableName} WHERE `buy_order` = '{$sanitizedBuyOrder}'";

        $result = \Db::getInstance()->getRow($sql);
        if ($result === false) {
            return null;
        }
        return new TransbankWebpayRestTransaction($result['id']);
    }
}

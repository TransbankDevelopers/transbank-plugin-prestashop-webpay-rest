<?php
namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;

/**
 * Trait InteractsWithWebpayDb.
 */
trait InteractsWithWebpayDb
{
    public function getTransactionByOrderIdAndStatus($orderId, $status){
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . TransbankWebpayRestTransaction::TABLE_NAME . ' WHERE `order_id` = "' . $orderId . '" AND status = ' . $status;
        return \Db::getInstance()->getRow($sql);
    }

    public function getTransactionApprovedByCartId($cartId)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.TransbankWebpayRestTransaction::TABLE_NAME.' WHERE `cart_id` = "'.pSQL($cartId).'" and status = '.TransbankWebpayRestTransaction::STATUS_APPROVED;
        return \Db::getInstance()->getRow($sql);
    }

    public function getTransactionApprovedByOrderId($orderId){
        return $this->getTransactionByOrderIdAndStatus($orderId, TransbankWebpayRestTransaction::STATUS_APPROVED);
    }

    public function getTransbankWebpayRestTransactionByOrderId($orderId){
        $transaction = $this->getTransactionApprovedByOrderId($orderId);
        return new TransbankWebpayRestTransaction($transaction['id']);
    }

    public function getTransbankWebpayRestTransactionByToken($token){
        $sql = 'SELECT * FROM '._DB_PREFIX_.TransbankWebpayRestTransaction::TABLE_NAME.' WHERE `token` = "'.pSQL($token).'"';
        $result = \Db::getInstance()->getRow($sql);
        if ($result === false) {
            return null;
        }
        return new TransbankWebpayRestTransaction($result['id']);
    }

    public function getTransbankWebpayRestTransactionBySessionId($sessionId){
        $sql = 'SELECT * FROM '._DB_PREFIX_.TransbankWebpayRestTransaction::TABLE_NAME.' WHERE `session_id` = "'.$sessionId.'"';
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

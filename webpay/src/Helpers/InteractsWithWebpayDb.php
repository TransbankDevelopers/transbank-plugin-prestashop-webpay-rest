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

    public function getTransactionApprovedByOrderId($orderId){
        return $this->getTransactionByOrderIdAndStatus($orderId, TransbankWebpayRestTransaction::STATUS_APPROVED);
    }

    public function getTransbankWebpayRestTransactionByOrderId($orderId){
        $transaction = $this->getTransactionApprovedByOrderId($orderId);
        return new TransbankWebpayRestTransaction($transaction['id']);
    }
}

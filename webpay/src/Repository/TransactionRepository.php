<?php

namespace PrestaShop\Module\WebpayPlus\Repository;

use Db;
use Exception;
use Transbank\Plugin\Repository\ITransactionRepository;
use Transbank\Plugin\Repository\BaseTransactionRepository;
use PrestaShop\Module\WebpayPlus\Model\TransbankTransaction;
use Transbank\Plugin\Helpers\TbkConstans;
use Transbank\Plugin\Model\TransbankTransactionDto;

class TransactionRepository extends BaseTransactionRepository implements ITransactionRepository {

    public function getTableName(){
        return _DB_PREFIX_.TbkConstans::TRANSACTION_TABLE_NAME;
    }

    public function createTableBase($tableName){
        $sql = null;
        $engine = _MYSQL_ENGINE_;
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `id`                   bigint(20) NOT NULL AUTO_INCREMENT,
            `store_id`             varchar(60) NOT NULL,
            `order_id`             varchar(60) NOT NULL,
            `buy_order`            varchar(60) NOT NULL,
            `child_buy_order`      varchar(60),
            `commerce_code`        varchar(60),
            `child_commerce_code`  varchar(60),
            `amount`               bigint(20) NOT NULL,
            `refund_amount`        bigint(20) NOT NULL,
            `token`                varchar(100),
            `transbank_status`     varchar(100),
            `session_id`           varchar(100),
            `status`               varchar(50) NOT NULL,
            `transbank_response`   LONGTEXT,
            `last_refund_type`     varchar(100),
            `last_refund_response` LONGTEXT,
            `all_refund_response`  LONGTEXT,
            `oneclick_username`    varchar(100),
            `product`              varchar(30),
            `environment`          varchar(20),
            `error`                varchar(255),
            `original_error`       LONGTEXT,
            `custom_error`         LONGTEXT,
            `created_at`           TIMESTAMP NOT NULL  DEFAULT NOW(),
            `updated_at`           TIMESTAMP NOT NULL  DEFAULT NOW(),
            PRIMARY KEY (id)
        ) ENGINE={$engine} DEFAULT CHARSET=utf8;";
        Db::getInstance()->execute($sql);
        return null;
    }

    public function deleteTableBase($tableName)
    {
        $sql = "DROP TABLE IF EXISTS `$tableName`";
        Db::getInstance()->execute($sql);
    }

    public function createWebpayplus(TransbankTransactionDto $data){
        $r = new TransbankTransaction();
        $r->store_id = $data->getStoreId();
        $r->product = $data->getProduct();
        $r->environment = $data->getEnvironment();
        $r->commerce_code = $data->getCommerceCode();
        $r->buy_order = $data->getBuyOrder();
        $r->order_id = $data->getOrderId();
        $r->amount = $data->getAmount();
        $r->refund_amount = $data->getRefundAmount();
        $r->status = $data->getStatus();

        $r->session_id = $data->getSessionId();
        $r->save();
        return $r;
    }

    public function createOneclick(TransbankTransactionDto $data){
        $r = new TransbankTransaction();
        $r->store_id = $data->getStoreId();
        $r->product = $data->getProduct();
        $r->environment = $data->getEnvironment();
        $r->commerce_code = $data->getCommerceCode();
        $r->buy_order = $data->getBuyOrder();
        $r->order_id = $data->getOrderId();
        $r->amount = $data->getAmount();
        $r->oneclick_username = $data->getOneclickUsername();
        $r->refund_amount = $data->getRefundAmount();
        $r->status = $data->getStatus();

        $r->child_buy_order = $data->getChildBuyOrder();
        $r->child_commerce_code =  $data->getChildCommerceCode();
        $r->save();
        return $r;
    }

    public function update(TransbankTransactionDto $data){
        $r = new TransbankTransaction();
        $r->id = $data->getId();
        $r->store_id = $data->getStoreId();
        $r->product = $data->getProduct();
        $r->environment = $data->getEnvironment();
        $r->commerce_code = $data->getCommerceCode();
        $r->buy_order = $data->getBuyOrder();
        $r->order_id = $data->getOrderId();
        $r->amount = $data->getAmount();
        $r->refund_amount = $data->getRefundAmount();
        $r->oneclick_username = $data->getOneclickUsername();
        $r->status = $data->getStatus();
        $r->session_id = $data->getSessionId();
        $r->child_buy_order = $data->getChildBuyOrder();
        $r->child_commerce_code =  $data->getChildCommerceCode();
        $r->token = $data->getToken();
        $r->transbank_status = $data->getTransbankStatus();
        $r->transbank_response = $data->getTransbankResponse();
        $r->last_refund_type = $data->getLastRefundType();
        $r->last_refund_response = $data->getLastRefundResponse();
        $r->all_refund_response = $data->getAllRefundResponse();
        $r->error = $data->getError();
        $r->original_error = $data->getOriginalError();
        $r->custom_error = $data->getCustomError();
        //$r->created_at = $data->getCreatedAt();
        $r->updated_at = $data->getUpdatedAt();
        $r->save();
    }

}


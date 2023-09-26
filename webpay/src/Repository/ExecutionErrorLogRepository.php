<?php

namespace PrestaShop\Module\WebpayPlus\Repository;

use Db;
use Exception;
use Transbank\Plugin\Repository\IExecutionErrorLogRepository;
use Transbank\Plugin\Repository\BaseRepository;
use PrestaShop\Module\WebpayPlus\Model\ExecutionErrorLog;
use Transbank\Plugin\Helpers\TbkConstans;
use Transbank\Plugin\Model\ExecutionErrorLogDto;

class ExecutionErrorLogRepository extends BaseRepository implements IExecutionErrorLogRepository {

    public function getTableName(){
        return _DB_PREFIX_.TbkConstans::EXECUTION_ERROR_LOG_TABLE_NAME;
    }

    public function createTableBase($tableName){
        $sql = null;
        $engine = _MYSQL_ENGINE_;
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `id`               bigint(20) NOT NULL AUTO_INCREMENT,
            `store_id`         varchar(60) NOT NULL,
            `buy_order`        varchar(60) NOT NULL,
            `service`          varchar(100) NOT NULL,
            `product`          varchar(30) NOT NULL,
            `environment`      varchar(20) NOT NULL,
            `commerce_code`    varchar(60) NOT NULL,
            `data`             LONGTEXT,
            `error`            varchar(255),
            `original_error`   LONGTEXT,
            `custom_error`     LONGTEXT,
            `created_at`       TIMESTAMP NOT NULL  DEFAULT NOW(),
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

    public function create(ExecutionErrorLogDto $data){
        $r = new ExecutionErrorLog();
        $r->store_id = $data->getStoreId();
        $r->product = $data->getProduct();
        $r->environment = $data->getEnvironment();
        $r->commerce_code = $data->getCommerceCode();
        $r->service = $data->getService();
        $r->buy_order = $data->getBuyOrder();
        $r->data = $data->getData();
        $r->error = $data->getError();
        $r->original_error = $data->getOriginalError();
        $r->custom_error = $data->getCustomError();
        $r->save();
        return $r;
    }

}

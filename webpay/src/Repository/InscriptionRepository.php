<?php

namespace PrestaShop\Module\WebpayPlus\Repository;

use Db;
use Exception;
use Transbank\Plugin\Repository\IInscriptionRepository;
use Transbank\Plugin\Repository\BaseInscriptionRepository;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscription;
use Transbank\Plugin\Helpers\TbkConstans;
use Transbank\Plugin\Model\TransbankInscriptionDto;

class InscriptionRepository extends BaseInscriptionRepository implements IInscriptionRepository {

    public function getTableName(){
        return _DB_PREFIX_.TbkConstans::INSCRIPTIONS_TABLE_NAME;
    }

    public function createTableBase($tableName){
        $sql = null;
        $engine = _MYSQL_ENGINE_;
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `id`                      bigint(20) NOT NULL AUTO_INCREMENT,
            `store_id`                varchar(60) NOT NULL,
            `token`                   varchar(100) NOT NULL,
            `username`                varchar(100),
            `email`                   varchar(50) NOT NULL,
            `user_id`                 varchar(50),
            `tbk_user`                varchar(100) NULL,
            `ecommerce_token_id`      varchar(100) NULL,
            `order_id`                varchar(60),
            `pay_after_inscription`   TINYINT(1) DEFAULT 0,
            `response_code`           varchar(50),
            `authorization_code`      varchar(50),
            `card_type`               varchar(50),
            `card_number`             varchar(50),
            `from`                    varchar(50),
            `status`                  varchar(50) NOT NULL,
            `environment`             varchar(20),
            `commerce_code`           varchar(60),
            `transbank_response`      LONGTEXT,
            `error`                   varchar(255),
            `original_error`          LONGTEXT,
            `custom_error`            LONGTEXT,
            `created_at`              TIMESTAMP NOT NULL  DEFAULT NOW(),
            `updated_at`              TIMESTAMP NOT NULL  DEFAULT NOW(),
            PRIMARY KEY (id)
        ) ENGINE={$engine} DEFAULT CHARSET=utf8;'";
        Db::getInstance()->execute($sql);
        return null;
    }

    public function deleteTableBase($tableName)
    {
        $sql = "DROP TABLE IF EXISTS `$tableName`";
        Db::getInstance()->execute($sql);
    }

    public function create(TransbankInscriptionDto $data){
        $r = new TransbankInscription();
        $r->store_id = $data->getStoreId();
        $r->token = $data->getToken();
        $r->username = $data->getUsername();
        $r->order_id = $data->getOrderId();
        $r->user_id = $data->getUserId();
        $r->pay_after_inscription = $data->getPayAfterInscription();
        $r->email =  $data->getEmail();
        $r->from = $data->getFrom();
        $r->status = $data->getStatus();
        $r->commerce_code =  $data->getCommerceCode();
        $r->environment = $data->getEnvironment();
        $r->save();
        return $r;
    }

    public function update(TransbankInscriptionDto $data){
        $r = new TransbankInscription();
        $r->id = $data->getId();
        $r->store_id = $data->getStoreId();
        $r->token = $data->getToken();
        $r->username = $data->getUsername();
        $r->order_id = $data->getOrderId();
        $r->user_id = $data->getUserId();
        $r->pay_after_inscription = $data->getPayAfterInscription();
        $r->email =  $data->getEmail();
        $r->from = $data->getFrom();
        $r->status = $data->getStatus();
        $r->commerce_code =  $data->getCommerceCode();
        $r->environment = $data->getEnvironment();
        $r->transbank_response = $data->getTransbankResponse();
        $r->ecommerce_token_id = $data->getEcommerceTokenId();
        $r->tbk_user = $data->getTbkUser();
        $r->response_code = $data->getResponseCode();
        $r->authorization_code = $data->getAuthorizationCode();
        $r->card_type = $data->getCardType();
        $r->card_number = $data->getCardNumber();
        $r->error = $data->getError();
        $r->original_error = $data->getOriginalError();
        $r->custom_error = $data->getCustomError();
        //$r->created_at = $data->getCreatedAt();
        $r->updated_at = $data->getUpdatedAt();
        $r->save();
    }
}

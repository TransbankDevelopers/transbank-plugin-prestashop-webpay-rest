<?php

namespace PrestaShop\Module\WebpayPlus\Model;

use ObjectModel;

class TransbankInscriptions extends ObjectModel
{
    const TABLE_NAME = 'transbank_inscriptions';
    const STATUS_INITIALIZED = 'initialized';
    const STATUS_FAILED = 'failed';
    const STATUS_COMPLETED = 'completed';

    public $id;
    public $token;
    public $username;
    public $email;
    public $user_id;
    public $tbk_token;
    public $order_id;
    public $pay_after_inscription;
    public $finished;
    public $response_code;
    public $authorization_code;
    public $card_type;
    public $card_number;
    public $from;
    public $status;
    public $environment;
    public $commerce_code;
    public $transbank_response;
    public $created_at;

    public static $definition = [
        'table'     => self::TABLE_NAME,
        'primary'   => 'id',
        'multilang' => false,
        'fields'    => [
            'token'                     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'username'                  => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'email'                     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'user_id'                   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'tbk_token'                 => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],

            'pay_after_inscription'     => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'allow_null' => true],
            'finished'                  => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => false],
            'response_code'             => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'authorization_code'        => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'card_type'                 => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'card_number'               => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'from'                      => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'status'                    => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'environment'               => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'commerce_code'             => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'transbank_response'        => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true]
        ],
    ];
}

/*

`id` bigint(20) NOT NULL AUTO_INCREMENT,
`token` varchar(100) NOT NULL,
`username` varchar(100),
`email` varchar(50) NOT NULL,

`user_id` bigint(20),
`token_id` bigint(20),
`order_id` bigint(20),

`pay_after_inscription` TINYINT(1) DEFAULT 0,
`finished` TINYINT(1) NOT NULL DEFAULT 0,
`response_code` varchar(50),
`authorization_code` varchar(50),
`card_type` varchar(50),
`card_number` varchar(50),
`from` varchar(50),
`status` varchar(50) NOT NULL,
`environment` varchar(20),
`commerce_code` varchar(60),
`transbank_response` LONGTEXT,
`created_at` TIMESTAMP NOT NULL  DEFAULT NOW(),
PRIMARY KEY (id)*/


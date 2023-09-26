<?php

namespace PrestaShop\Module\WebpayPlus\Model;

use ObjectModel;
use Transbank\Plugin\Helpers\TbkConstans;

class TransbankInscription extends ObjectModel
{
    public $id;
    public $store_id;
    public $token;
    public $username;
    public $email;
    public $user_id;
    public $tbk_user;
    public $ecommerce_token_id;
    public $order_id;
    public $pay_after_inscription;
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
    public $updated_at;
    public $error;
    public $original_error;
    public $custom_error;

    public static $definition = [
        'table'     => TbkConstans::INSCRIPTIONS_TABLE_NAME,
        'primary'   => 'id',
        'multilang' => false,
        'fields'    => [
            'store_id'                  => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'token'                     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'username'                  => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'email'                     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'user_id'                   => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'tbk_user'                  => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'ecommerce_token_id'        => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'order_id'                  => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'pay_after_inscription'     => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'allow_null' => true],
            'response_code'             => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'authorization_code'        => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'card_type'                 => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'card_number'               => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'from'                      => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'status'                    => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'environment'               => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'commerce_code'             => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'transbank_response'        => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'error'                     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'original_error'            => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'allow_null' => true],
            'custom_error'              => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'allow_null' => true]
        ],
    ];
}




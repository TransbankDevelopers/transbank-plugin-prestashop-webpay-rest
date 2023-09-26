<?php

namespace PrestaShop\Module\WebpayPlus\Model;

use ObjectModel;
use Transbank\Plugin\Helpers\TbkConstans;

class TransbankTransaction extends ObjectModel
{
    public $id;
    public $store_id;
    public $order_id;
    public $buy_order;
    public $child_buy_order;
    public $commerce_code;
    public $child_commerce_code;
    public $amount;
    public $refund_amount;
    public $token;
    public $transbank_status;
    public $session_id;
    public $status;
    public $transbank_response;
    public $last_refund_type;
    public $last_refund_response;
    public $all_refund_response;
    public $oneclick_username;
    public $product;
    public $environment;
    public $created_at;
    public $updated_at;
    public $error;
    public $original_error;
    public $custom_error;

    public static $definition = [
        'table'     => TbkConstans::TRANSACTION_TABLE_NAME,
        'primary'   => 'id',
        'multilang' => false,
        'fields'    => [
            'store_id'                => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'order_id'                => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'buy_order'               => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'child_buy_order'         => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'commerce_code'           => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'child_commerce_code'     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'amount'                  => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'refund_amount'           => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'token'                   => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'transbank_status'        => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'session_id'              => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'status'                  => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'transbank_response'      => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'allow_null' => true],
            'last_refund_type'        => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'last_refund_response'    => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'allow_null' => true],
            'all_refund_response'     => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'allow_null' => true],
            'oneclick_username'       => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'product'                 => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'environment'             => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'error'                   => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'original_error'          => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'allow_null' => true],
            'custom_error'            => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'allow_null' => true]
        ],
    ];
}

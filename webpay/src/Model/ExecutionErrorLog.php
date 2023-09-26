<?php

namespace PrestaShop\Module\WebpayPlus\Model;

use ObjectModel;
use Transbank\Plugin\Helpers\TbkConstans;

class ExecutionErrorLog extends ObjectModel
{
    public $id;
    public $store_id;
    public $buy_order;
    public $service;
    public $product;
    public $environment;
    public $commerce_code;
    public $data;
    public $error;
    public $original_error;
    public $custom_error;
    public $created_at;

    public static $definition = [
        'table'     => TbkConstans::EXECUTION_ERROR_LOG_TABLE_NAME,
        'primary'   => 'id',
        'multilang' => false,
        'fields'    => [
            'store_id'          => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'buy_order'         => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'service'           => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'product'           => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'environment'       => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'commerce_code'     => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'data'              => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'allow_null' => true],
            'error'             => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'allow_null' => true],
            'original_error'    => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'allow_null' => true],
            'custom_error'      => ['type' => self::TYPE_HTML, 'validate' => 'isString', 'allow_null' => true]
        ],
    ];
}

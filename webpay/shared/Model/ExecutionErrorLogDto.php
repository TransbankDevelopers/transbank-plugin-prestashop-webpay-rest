<?php

namespace Transbank\Plugin\Model;

class ExecutionErrorLogDto extends TbkLogDtoBase
{
    public $data;

    /**
     * @param array $data
     */
    public function __construct($data = null) {
        if (!is_null($data)){
            $this->setId($data['id']);
            $this->setProduct($data['product']);
            $this->setService($data['service']);
            $this->setEnvironment($data['environment']);
            $this->setCommerceCode($data['commerce_code']);
            $this->setBuyOrder($data['buy_order']);
            $this->setData($data['data']);
            $this->setError($data['error']);
            $this->setOriginalError($data['original_error']);
            $this->setCustomError($data['custom_error']);
            $this->setCreatedAt($data['created_at']);
        }
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
    }
}

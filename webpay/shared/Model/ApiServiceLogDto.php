<?php

namespace Transbank\Plugin\Model;

class ApiServiceLogDto extends TbkLogDtoBase
{
    public $input;
    public $response;

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
            $this->setInput($data['input']);
            $this->setResponse($data['response']);
            $this->setError($data['error']);
            $this->setOriginalError($data['original_error']);
            $this->setCustomError($data['custom_error']);
            $this->setCreatedAt($data['created_at']);
        }
    }

    public function getInput() {
        return $this->input;
    }

    public function setInput($input) {
        $this->input = $input;
    }

    public function getResponse() {
        return $this->response;
    }

    public function setResponse($response) {
        $this->response = $response;
    }

}

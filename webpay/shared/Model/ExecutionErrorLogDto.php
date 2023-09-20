<?php

namespace Transbank\Plugin\Model;

class ExecutionErrorLogDto
{
    public $id;
    public $buyOrder;
    public $service;
    public $product;
    public $environment;
    public $commerceCode;
    public $data;
    public $error;
    public $originalError;
    public $customError;
    public $createdAt;

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

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getBuyOrder() {
        return $this->buyOrder;
    }

    public function setBuyOrder($buyOrder) {
        $this->buyOrder = $buyOrder;
    }

    public function getService() {
        return $this->service;
    }

    public function setService($service) {
        $this->service = $service;
    }

    public function getProduct() {
        return $this->product;
    }

    public function setProduct($product) {
        $this->product = $product;
    }

    public function getEnvironment() {
        return $this->environment;
    }

    public function setEnvironment($environment) {
        $this->environment = $environment;
    }

    public function getCommerceCode() {
        return $this->commerceCode;
    }

    public function setCommerceCode($commerceCode) {
        $this->commerceCode = $commerceCode;
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function getError() {
        return $this->error;
    }

    public function setError($error) {
        $this->error = $error;
    }

    public function getOriginalError() {
        return $this->originalError;
    }

    public function setOriginalError($originalError) {
        $this->originalError = $originalError;
    }

    public function getCustomError() {
        return $this->customError;
    }

    public function setCustomError($customError) {
        $this->customError = $customError;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;
    }
}

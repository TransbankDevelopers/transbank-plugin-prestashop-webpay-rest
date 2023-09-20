<?php

namespace Transbank\Plugin\Model;

class PaginatedList  {
    public $data;
    public $total;

    public function __construct($data, $total) {
        $this->data = $data;
        $this->total = $total;
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function getTotal() {
        return $this->total;
    }

    public function setTotal($total) {
        $this->total = $total;
    }
}

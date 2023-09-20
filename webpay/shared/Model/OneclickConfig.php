<?php

namespace Transbank\Plugin\Model;

class OneclickConfig extends ProductConfig {

    public $childCommerceCode = null;

    public function getChildCommerceCode()
    {
        return $this->childCommerceCode;
    }

    public function setChildCommerceCode($childCommerceCode)
    {
        $this->childCommerceCode = $childCommerceCode;
    }

}

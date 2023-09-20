<?php

namespace Transbank\Plugin\Model;

class LogConfig  {
    public $logDir = null;
    public function getLogDir()
    {
        return $this->logDir;
    }

    public function setLogDir($logDir)
    {
        $this->logDir = $logDir;
    }
}

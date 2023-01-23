<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

use PrestaShop\Module\WebpayPlus\Utils\LogHandler;
use PrestaShop\Module\WebpayPlus\Utils\ReportPdf;

class ReportPdfLog
{
    public function __construct($document)
    {
        $this->document = $document;
    }

    public function getReport($myJSON)
    {
        $log = new LogHandler();
        $json = json_decode($log->getLastLog(), true);

        $obj = json_decode($myJSON, true);

        if (isset($json['log_content']) && $this->document == 'report') {
            $html = str_replace("\r\n", '<br>', $json['log_content']);
            $html = str_replace("\n", '<br>', $json['log_content']);
            $text = explode('<br>', $html);
            $html = '';
            foreach ($text as $row) {
                $html .= '<b>'.substr($row, 0, 21).'</b> '.substr($row, 22).'<br>';
            }
            $obj += ['logs' => ['log' => $html]];
        }

        $report = new ReportPdf();
        $report->getReport(json_encode($obj));
    }
}

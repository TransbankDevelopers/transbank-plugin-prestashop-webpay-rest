<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

require_once 'LogHandler.php';

$logHandler = new LogHandler();
$logHandler->setLockStatus($_POST['status'] == 'true' ? true : false);
$logHandler->setnewconfig((int) $_POST['max_days'], (int) $_POST['max_weight']);

$response = [
    'success' => true,
];

echo json_encode($response);

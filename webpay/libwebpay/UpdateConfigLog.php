<?php
require_once('LogHandler.php');

$logHandler = new LogHandler();
$logHandler->setLockStatus($_POST['status'] == 'true' ? true : false);
$logHandler->setnewconfig((integer)$_POST['max_days'], (integer)$_POST['max_weight']);

$response = [
   'success' => true
];

echo json_encode($response);

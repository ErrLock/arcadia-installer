<?php
require_once(__DIR__ .'/bootstrap.php');

$uuid = null;
isset($_REQUEST['uuid']) && $uuid = $_REQUEST['uuid'];

require_once('preseed.class.php');
$preseed = new Preseed($db, $uuid);

echo $preseed;
?>

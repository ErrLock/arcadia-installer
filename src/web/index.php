<?php
require_once(__DIR__ .'/bootstrap.php');

if(preg_match('#^iPXE/.*$#', $_SERVER['HTTP_USER_AGENT']))
{
	require_once('ipxe.php');
	exit;
}
?>

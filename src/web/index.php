<?php
require_once(__DIR__ .'/bootstrap.php');

if(preg_match('#^iPXE/.*$#', $_SERVER['HTTP_USER_AGENT']))
{
	require_once('ipxe.php');
	exit;
}

session_start();
var_dump($_SERVER);
var_dump($_REQUEST);
var_dump($_FILES);
var_dump($_COOKIE);
var_dump($_SESSION);
var_dump($_ENV);
var_dump(file_get_contents("php://input"));
?>

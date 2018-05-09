<?php
require_once(__DIR__ .'/config.php');

$db_dsn = 'sqlite:'. ARCADIA_SHAREDSTATEDIR .'/'. ARCADIA_PKGNAME .'.db';

$conf_file = ARCADIA_SYSCONFDIR ."/". ARCADIA_PKGNAME .".ini";
if(file_exists($conf_file)) {
	$ini_conf = parse_ini_file($conf_file);
	isset($ini_conf['database']) && $db_dsn = $ini_conf['database'];
}

$db = new PDO($db_dsn);

require_once('config.class.php');
$conf = new Config($db);
?>

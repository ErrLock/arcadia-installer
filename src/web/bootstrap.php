<?php
require_once(__DIR__ .'/config.php');

$conf_file = ARCADIA_SYSCONFDIR ."/". ARCADIA_PKGNAME .".ini";
if(!file_exists($conf_file)) {
	throw new \Error("No config file");
}

$ini_conf = parse_ini_file($conf_file);

if(!isset($ini_conf['web_host']) || !isset($ini_conf['database']))
{
	throw new \Error("No web_host configured");
}
define('ARCADIA_WEB_HOST', $ini_conf['web_host']);
define('ARCADIA_DB_DSN', $ini_conf['database']);

$db = new PDO(ARCADIA_DB_DSN);

require_once('config.class.php');
$conf = new Config($db);
?>

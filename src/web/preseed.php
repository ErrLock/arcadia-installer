<?php
require_once('./config.php');

$conf_file = $sysconfdir ."/arcadia-installer.ini";
if(!file_exists($conf_file)) {
	throw new \Error("Not found: ". $conf_file);
}

require_once('./db.class.php');
$conf = new DB($conf_file);

if(!$conf->isset('server', 'salt'))
{
	throw new \Error("salt not set");
}

$preseed_file = $sysconfdir ."/".
	crypt(
		$_SERVER['REMOTE_ADDR'],
		'$6$rounds=5000$'. $conf->get('server', 'salt') .'$'
	) .
	'.ini';
if(!is_file($preseed_file))
{
	touch($preseed_file);
}
$preseed_db = new DB($preseed_file);

require_once('./preseed.class.php');
$preseed = new Preseed($preseed_db);

echo $preseed;
?>

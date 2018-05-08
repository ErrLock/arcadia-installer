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
	exit;
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

if(!$preseed->isset('mirror/http/hostname') && $conf->isset('apt', 'proxy'))
{
		$preseed->set('mirror/country', 'manual');
		$preseed->set('mirror/http/hostname', $conf->get('apt', 'proxy'));
		$preseed->set('mirror/http/directory', '/ftp.debian.org/debian');
		$preseed->set('mirror/http/proxy', '');
		$preseed->set('base-installer/includes', 'auto-apt-proxy');
}

if(!$preseed->isset('netcfg/get_hostname'))
{
	$hostname = 'arcadia';
	if($conf->isset('server', 'default_hostname'))
	{
		$hostname = $conf->get('server', 'default_hostname');
	}
	$preseed->set('netcfg/get_hostname', $hostname);
}

echo $preseed;
?>

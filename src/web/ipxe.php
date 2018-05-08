#!ipxe
<?php
require_once('./config.php');

function error(string $message)
{
	echo "echo [ERROR] ". $message ."\n";
	echo "exit\n";
	exit;
}

$conf_file = $sysconfdir ."/arcadia-installer.ini";
if(!file_exists($conf_file)) {
	error("Not found: ". $conf_file);
}

require_once('./db.class.php');
$conf = new DB($conf_file);

if(!$conf->isset('server', 'base'))
{
	error("server/base not configured");
}

function menu(
	string $var, string $title = null, array $item_list=array(),
	string $goto=null
)
{
	$id = "menu_". $var;
	if(!isset($title))
	{
		$title = "Select ". $var;
	}
	
	echo ":". $id ."\n";
	echo "menu ". $title ."\n";
	foreach($item_list as $key => $value)
	{
		echo "item ". $key ." ". $value ."\n";
	}
	echo "choose ". $var ." || goto \${menu_previous}\n";
	echo "set menu_previous ". $id ."\n";
	if(isset($goto))
	{
		echo "goto ". $goto ."\n";
	}
	else
	{
		echo "goto \${". $var ."}\n";
	}
}

function menu_boot_method()
{
	menu(
		'boot_method',
		"Select boot method",
		array(
			'boot_install' => "Install",
			'boot_rescue' => "Rescue",
			'boot_default' => "Default",
			'boot_shell' => "iPXE shell"
		)
	);
}

function menu_arch()
{
	$archs = array_map('basename', glob('netboot/*', GLOB_ONLYDIR));
	$items = array_combine($archs, $archs);
	menu(
		'arch',
		"Select architecture",
		$items,
		'boot_netboot'
	);
}

$boot_params = array(
	'netcfg/disable_autoconfig' => 'true',
	'netcfg/confirm_static' => 'true'
);

function boot_params($params)
{
	$result = '';
	foreach($params as $key => $value)
	{
		if(!empty($result))
		{
			$result .= " ";
		}
		$result .= $key ."=". $value;
	}
	
	return $result;
}

if($conf->isset('server', 'boot'))
{
	echo "set boot_method ". $conf->get('server', 'boot') ."\n";
}
if($conf->isset('server', 'arch'))
{
	echo "set arch ". $conf->get('server', 'arch') ."\n";
}
if($conf->isset('apt', 'proxy'))
{
		$boot_params['mirror/country'] = 'manual';
		$boot_params['mirror/http/hostname'] = $conf->get('apt', 'proxy');
		$boot_params['mirror/http/directory'] = '/ftp.fr.debian.org/debian';
		$boot_params['mirror/http/proxy'] = '';
		$boot_params['base-installer/includes'] = 'auto-apt-proxy';
}

echo "set base ". $conf->get('server', 'base') ."\n";
echo "set boot_params ". boot_params($boot_params) ."\n";
?>
set boot_params ${boot_params} netcfg/get_ipaddress=${net0/ip}
set boot_params ${boot_params} netcfg/get_netmask=${net0/netmask}
set boot_params ${boot_params} netcfg/get_gateway=${net0/gateway}
set boot_params ${boot_params} netcfg/get_nameservers=${net0/dns}
isset ${net0/domain} && set boot_params ${boot_params} netcfg/get_domain=${net0/domain} ||
isset ${hostname} && set boot_params ${boot_params} netcfg/get_hostname=${hostname} ||

set menu_previous menu_boot_method

iseq ${buildarch} arm32 && set arch armel ||
isset ${arch} || iseq ${buildarch} arm64 && set arch arm64 ||
isset ${arch} || iseq ${buildarch} x86_64 && set arch amd64 ||
isset ${arch} || iseq ${buildarch} i386 && set arch i386 ||
isset ${arch} && iseq ${arch} i386 && cpuid --ext 29 && set arch amd64 ||

isset ${boot_method} || goto ${menu_previous}

:boot_default
exit

:boot_install
:boot_netboot
isset ${arch} || goto menu_arch
set netboot ${base}/netboot/${arch}
kernel ${netboot}/linux initrd=rd.gz ${boot_params}
initrd --name rd.gz ${netboot}/initrd.gz
show boot_params
prompt Press any key to boot ${boot_method} || goto ${menu_previous}
boot

:boot_rescue
set boot_params ${boot_params} rescue/enable=true
goto boot_netboot

:boot_shell
shell

<?php
	menu_boot_method();
	menu_arch();
?>

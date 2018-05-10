#!ipxe
<?php
require_once(__DIR__ .'/bootstrap.php');

function error(string $message)
{
	echo "echo [ERROR] ". $message ."\n";
	echo "exit\n";
	exit;
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

function menu_frontend()
{
	menu(
		'frontend',
		"Select frontend",
		array(
			'newt' => 'Newt',
			'text' => 'Text',
			'default' => 'Default'
		),
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

if($conf->isset('boot_method'))
{
	echo "set boot_method ". $conf->get('boot_method') ."\n";
}
if($conf->isset('arch'))
{
	echo "set arch ". $conf->get('arch') ."\n";
}
if(
	$conf->isset('confirm_boot')
	&& $conf->get('confirm_boot') == 'true'
)
{
	echo "set confirm_boot 1\n";
}
if($conf->isset('frontend'))
{
	echo "set frontend ". $conf->get('frontend') ."\n";
}

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
goto ${boot_method}

:boot_default
exit

:boot_install
:boot_netboot
isset ${arch} || goto menu_arch
isset ${frontend} || goto menu_frontend
iseq ${frontend} default || set boot_params ${boot_params} DEBIAN_FRONTEND=${frontend}
set netboot netboot/${arch}
kernel ${netboot}/linux initrd=rd.gz initrd=preseed ${boot_params} || goto menu_arch
initrd --name rd.gz ${netboot}/initrd.gz
initrd --name preseed preseed.php?uuid=${uuid} preseed.cfg
show boot_params
isset ${confirm_boot} || prompt Press any key to boot ${boot_method} || goto ${menu_previous}
boot

:boot_rescue
set boot_params ${boot_params} rescue/enable=true
goto boot_netboot

:boot_shell
shell

<?php
	menu_boot_method();
	menu_arch();
	menu_frontend();
?>

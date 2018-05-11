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
		'select_frontend'
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
		'select_report'
	);
}

function menu_report()
{
	menu(
		'report',
		"Report installation progress to a URL?",
		array(
			'true' => "Yes",
			'false' => "No"
		),
		'select_report_url'
	);
}

function menu_report_url()
{
	echo ":menu_report_url\n";
	echo "echo -n Report URL: \${}\n";
	echo "read report_url:string || goto ${menu_previous}\n";
	echo "goto do_netboot\n";
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
if($conf->isset('confirm_boot'))
{
	echo "set confirm_boot ". $conf->get('confirm_boot') ."\n";
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

# isset ${arch} || iseq ${buildarch} foo && set arch bar
# would set arch to bar when arch is set: (a || b) && c
# there doesn't seem to be a way to set precedence, so use goto
isset ${arch} && goto init_arch_set ||
iseq ${buildarch} arm32 && set arch armel && goto init_arch_set ||
iseq ${buildarch} arm64 && set arch arm64 && goto init_arch_set ||
iseq ${buildarch} x86_64 && set arch amd64 && goto init_arch_set ||
iseq ${buildarch} i386 && cpuid --ext 29 && set arch amd64 && goto init_arch_set ||
iseq ${buildarch} i386 && set arch i386 && goto init_arch_set ||
:init_arch_set

isset ${confirm_boot} || set confirm_boot false

isset ${boot_method} || goto ${menu_previous}
goto ${boot_method}

:boot_default
exit

:boot_rescue
set boot_params ${boot_params} rescue/enable=true

:boot_install
:boot_netboot
:select_arch
isset ${arch} || goto menu_arch

:select_frontend
isset ${frontend} || goto menu_frontend

:select_report
isset ${report} || goto menu_report

:select_report_url
iseq ${report} true || goto do_netboot
isset ${report_url} || goto menu_report_url

:do_netboot
set netboot netboot/${arch}
iseq ${report} true && set boot_params ${boot_params} debconf/report_url=${report_url} ||
iseq ${report} true && set boot_params ${boot_params} debconf/report_frontend=${frontend} ||
iseq ${report} true && set frontend report ||
iseq ${frontend} default || set boot_params ${boot_params} DEBIAN_FRONTEND=${frontend}
kernel ${netboot}/linux initrd=rd.gz initrd=preseed ${boot_params} || goto menu_arch
initrd --name rd.gz ${netboot}/initrd.gz
initrd --name preseed preseed.php?uuid=${uuid} preseed.cfg
show boot_params
iseq ${confirm_boot} true || prompt Press any key to boot ${boot_method} || goto ${menu_previous}
boot

:boot_shell
shell

<?php
	menu_boot_method();
	menu_arch();
	menu_frontend();
	menu_report();
	menu_report_url();
?>

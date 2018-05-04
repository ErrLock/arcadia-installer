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
echo "set base ". $conf->get('server', 'base') ."\n";

session_start();

echo "echo SERVER:\n";
foreach($_SERVER as $key => $value)
{
	echo "echo ". $key ."=". $value ."\n";
}

echo "echo SESSION:\n";
foreach($_SESSION as $key => $value)
{
	echo "echo ". $key ."=". $value ."\n";
}

echo "echo REQUEST:\n";
foreach($_REQUEST as $key => $value)
{
	echo "echo ". $key ."=". $value ."\n";
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
			'boot_default' => "Default"
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

if($conf->isset('server', 'boot'))
{
	echo "set boot_method ". $conf->get('server', 'boot') ."\n";
}
if($conf->isset('server', 'arch'))
{
	echo "set arch ". $conf->get('server', 'arch') ."\n";
}
?>
set params 
set menu_previous menu_boot_method

isset ${boot_method} || goto ${menu_previous}

:boot_default
exit

:boot_install
:boot_netboot
isset ${arch} || goto menu_arch
set netboot ${base}/netboot/${arch}
kernel ${netboot}/linux initrd=rd.gz ${params}
initrd --name rd.gz ${netboot}/initrd.gz
boot

:boot_rescue
set params ${params} rescue/enable=true
goto boot_netboot

<?php
	menu_boot_method();
	menu_arch();
?>

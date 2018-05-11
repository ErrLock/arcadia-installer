#!ipxe
<?php
require_once(__DIR__ .'/bootstrap.php');

/*
 * The booted system can get its uuid from /sys/class/dmi/id/product_uuid
 */

function menu(string $id, string $title = null, array $item_list=array())
{
	if(!isset($title))
	{
		$title = "Select ". $id;
	}
	$id = "menu_". $id;
	
	echo "menu --name ". $id ." ". $title ."\n";
	foreach($item_list as $key => $value)
	{
		echo "item --menu ". $id ." ". $key ." ". $value ."\n";
	}
}

menu('boot_method', "Select boot method", array(
	'install' => "Install",
	'rescue' => "Rescue",
	'bios' => "Normal",
	'shell' => "iPXE shell"
));
menu('report', "Report installation progress to remote URL?", array(
	'true' => "Yes",
	'false' => "No"
));
menu('arch', "Select architecture", array(
	'x86_64' => 'PC (64 bits)',
	'i386' => 'PC (32 bits)',
	'arm64' => 'ARM (64 bits)',
	'arm32' => 'ARM (32 bits)'
));
menu('frontend', "Select frontend", array(
	'newt' => 'Newt',
	'text' => 'Text',
	'default' => 'Default'
));
menu('boot_failed', "!!!! BOOT FAILED !!!!", array('dummy' => "OK"));

echo "set web_host ". ARCADIA_WEB_HOST ."\n";

foreach($conf->get_all() as $setting)
{
	if($setting['key'] != 'auto')
	{
		$setting['key'] = 'default_'. $setting['key'];
	}
	echo "set ". $setting['key'] ." ". $setting['value'] ."\n";
}
?>
# Set defaults
isset ${default_boot_method} || set default_boot_method bios
isset ${default_boot_params} || set default_boot_params ${}
isset ${default_report} || set default_report true
isset ${default_report_url} || set default_report_url ${web_host}/report.php
isset ${default_arch} && goto default_arch_set ||
# isset ${default_arch} || iseq ${buildarch} foo && set default_arch bar
# would set default_arch to bar when default_arch is set: (a || b) && c
# there doesn't seem to be a way to set precedence, so use goto
iseq ${buildarch} i386 && cpuid --ext 29 && set default_arch x86_64 && goto default_arch_set ||
set default_arch ${buildarch}
:default_arch_set
isset ${default_frontend} || set default_frontend default

:init
# reset boot params
set installer_boot_params ${}
set installer_boot_params ${installer_boot_params} netcfg/disable_autoconfig=true
set installer_boot_params ${installer_boot_params} netcfg/confirm_static=true
set installer_boot_params ${installer_boot_params} netcfg/get_ipaddress=${net0/ip}
set installer_boot_params ${installer_boot_params} netcfg/get_netmask=${net0/netmask}
set installer_boot_params ${installer_boot_params} netcfg/get_gateway=${net0/gateway}
set installer_boot_params ${installer_boot_params} netcfg/get_nameservers=${net0/dns}
isset ${net0/domain} && set installer_boot_params ${installer_boot_params} netcfg/get_domain=${net0/domain} ||
isset ${hostname} && set installer_boot_params ${installer_boot_params} netcfg/get_hostname=${hostname} ||

iseq ${auto} true || goto init_auto_false
set boot_method ${default_boot_method}
set boot_params ${default_boot_params}
set report ${default_report}
set report_url ${default_report_url}
set arch ${default_arch}
set frontend ${default_frontend}
goto ${boot_method}

:init_auto_false




:select_boot_method
isset ${boot_method} || choose --menu menu_boot_method --default ${default_boot_method} --keep boot_method || goto boot_reset
goto ${boot_method}




:bios
exit




:rescue
set installer_boot_params ${installer_boot_params} rescue/enable=true
goto netboot




:install

:select_report
isset ${report} || choose --menu menu_report --default ${default_report} --keep report || goto boot_reset
iseq ${report} true || goto select_report_no
set report_url ${default_report_url}
echo -n Report URL: ${}
read report_url:string || goto boot_reset
set installer_boot_params ${installer_boot_params} debconf/report_url=${report_url}
set installer_boot_params ${installer_boot_params} DEBIAN_FRONTEND=report
:select_report_no




:netboot

:select_arch
isset ${arch} || choose --menu menu_arch --default ${default_arch} --keep arch || goto boot_reset

:select_frontend
isset ${frontend} || choose --menu menu_frontend --default ${default_frontend} --keep frontend || goto boot_reset
iseq ${frontend} default && goto select_frontend_set ||
iseq ${boot_method} install && iseq ${report} true && set installer_boot_params ${installer_boot_params} debconf/report_frontend=${frontend} && goto select_frontend_set ||
set installer_boot_params ${installer_boot_params} DEBIAN_FRONTEND=${frontend}
:select_frontend_set

:select_boot_params
iseq ${auto} true && goto select_boot_params_set ||
set boot_params ${default_boot_params}
echo -n Boot parameters: ${}
read boot_params:string || goto boot_reset
:select_boot_params_set




:do_netboot
set netboot netboot/${arch}
kernel ${netboot}/linux initrd=rd.gz initrd=preseed ${installer_boot_params} ${boot_params} || goto boot_failed
initrd --name rd.gz ${netboot}/initrd.gz || goto boot_failed
initrd --name preseed preseed.php?uuid=${uuid} preseed.cfg || goto boot_failed
# We only ask for that if not in auto mode
iseq ${auto} false || goto do_netboot_confirm
menu Boot with the selected options?
item --gap Boot method: ${boot_method}
item --gap Architecture: ${arch}
item --gap Frontend: ${frontend}
iseq ${boot_method} install && item --gap Report: ${report} ||
iseq ${boot_method} install && iseq ${report} true && item --gap Report URL: ${report_url} ||
item --gap Boot parameters: ${boot_params}
item --gap
item true Yes
item false No
choose confirm_boot && iseq ${confirm_boot} true || goto boot_reset
:do_netboot_confirm
boot || goto boot_failed




:shell
shell || goto boot_failed
goto init




:boot_failed
choose --menu menu_boot_failed --keep dummy ||
# auto boot (if set) failed, do not try it again
set auto false
:boot_reset
# change defaults
isset ${boot_method} && set default_boot_method ${boot_method} ||
isset ${boot_params} && set default_boot_params ${boot_params} ||
isset ${report} && set default_report ${report} ||
isset ${report_url} && set default_report_url ${report_url} ||
isset ${arch} && set default_arch ${arch} ||
isset ${frontend} && set default_frontend ${frontend} ||
# make sure questions are asked again
clear boot_method
clear boot_params
clear report
clear report_url
clear arch
clear frontend
goto init

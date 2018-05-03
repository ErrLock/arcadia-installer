#!ipxe
<?php
require_once('./config.php');

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

echo "set base ". $base ."\n";
echo "set arch ". $arch ."\n";
?>
set installer ${base}/netboot/${arch}

kernel ${installer}/linux initrd=rd.gz
initrd --name rd.gz ${installer}/initrd.gz

prompt --key i Press 'i' to start install || exit
boot


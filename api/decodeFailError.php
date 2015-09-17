<?php
	REQUIRE_ONCE($_SERVER['DOCUMENT_ROOT']."/api/ErrorLogs.class.php");
	
	$file = __FILE__;
	$line = "CLIENT";
	$type = 800;
	$error = "";
	if( isset($_REQUEST['info']))
	{	$error=htmlspecialchars($_REQUEST["info"]);	}

    $log = new errorLoger();
    $log->logError($type, $error, $file, $line);
?>

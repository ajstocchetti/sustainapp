<?php
	REQUIRE_ONCE($_SERVER['DOCUMENT_ROOT']."/api/CSRSearch.class.php");

	$searchStr = "<image>";
	if( isset($_REQUEST['info'])) {
		// $error=htmlspecialchars($_REQUEST["info"]);		
		$searchStr=htmlspecialchars($_REQUEST["info"]);		
	}

    $failer = new CSRSearch($searchStr,"IMAGE");
	$failer->decoderFailure();
	
	// REQUIRE_ONCE($_SERVER['DOCUMENT_ROOT']."/api/ErrorLogs.class.php");
	// $file = __FILE__;
	// $line = "CLIENT";
	// $type = 800;
	// $error = "";
	// $log = new errorLoger();
    // $log->logError($type, $error, $file, $line);
?>

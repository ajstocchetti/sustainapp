<?php
	$test = "013562000043";	// Annie's Mac and Cheese
	require '../api/lookup.php';
	$queryResp = getParamsPHP($test,"");
	//var_dump( $queryResp);
	$prog = $queryResp['PROGRESS'];
	$message;
	if( isset( $queryResp['MSG']))
	{	$message = $queryResp['MSG'];	}
	$score;
	if( isset( $queryResp['RATING']))
	{	$score = $queryResp['RATING'];	}
	$company;
	if( isset( $queryResp['COMPANY']))
	{	$company = $queryResp['COMPANY'];	}
	$upcRet;
	if( isset( $queryResp['UPC']))
	{	$upcRet = $queryResp['MSG'];	}
	$desc;
	if( isset( $queryResp['DESCRIPTION']))
	{	$desc = $queryResp['DESCRIPTION'];	}
	
	if( isset($prog))
	{	echo '<div id="searchresult" class="two-thirds column">';
		echo '</div>';
		//<div id="wrongresults" class="one-third column"></div>
	}
?>
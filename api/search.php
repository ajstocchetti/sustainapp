<?php
	REQUIRE_ONCE($_SERVER['DOCUMENT_ROOT']."/api/CSRSearch.class.php");

	$upcCode = NULL;
	if( isset($_REQUEST['upcc']))
	{	$upcCode=htmlspecialchars($_REQUEST["upcc"]);	}
	$searchMethod = "";
	if( isset($_REQUEST['searchtype']))
	{	$searchMethod=strtoupper($_REQUEST['searchtype']);	}
	
	$searcher = new CSRSearch($upcCode,$searchMethod);
	$searcher->search();
	$searcher->getResults();
?>
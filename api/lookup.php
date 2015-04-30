<?php
REQUIRE_ONCE($_SERVER['DOCUMENT_ROOT']."/../jm2_sustainapp_db.php");


/*  ***** getParams *****
	description: reads HTML variables and begins score lookup
	params: none
	return: nothing (JSON string is be echoed)
	*************** */
function getParams()
{	// get parameters
	$upcCode = NULL;
	if( isset($_REQUEST['upcc']))
	{	$upcCode=htmlspecialchars($_REQUEST["upcc"]);	}
	
	$searchMethod = "";
	if( isset($_REQUEST['searchtype']))
	{	$searchMethod=strtoupper($_REQUEST['searchtype']);	}
	
	processParams($upcCode, $searchMethod);
}



/*  ***** getParamsPHP *****
	description: begins score lookup from PHP variables
	params: upc - upc code to search for
			type [opt] - if upc is a upc or company name
	return: php array with search results
	*************** */
function getParamsPHP($upc, $type)
{	return processParams($upc, $type, "PHP");
}



/*  ***** processParams *****
	description: analyse input and begin UPC or company query
	params: upcCode - UPC or company to search for
			type [opt] - if search string is a UPC or company name
			dataType [opt] - format to return. See doResponse for details
	return: JSON echoed or PHP array
	*************** */
function processParams($upcCode, $searchMethod, $dataType=NULL)
{	if( empty($upcCode))	// quit now if no UPC or company to lookup
	{	return noData($dataType);	}
	if($searchMethod == "UPC")
	{	getProductDigitEyes($upcCode);	}
	elseif($searchMethod == "COMPANY")
	{	return getScoreForCompany($upcCode);	}
	else // search type not set or junk value
	{	return isCompOrUPC($upcCode, $dataType);	}
}



/*  ***** noData *****
	description: reads HTML variables and begins score lookup
	params: dataType [opt] - format to return. See doResponse for details
	return: JSON echoed or PHP array
	*************** */
function noData($dataType=NULL)
{	// TODO: make a real HTTP response when do the same for $retData
	doResponse(0, "No data given. Please try again",NULL,NULL,NULL,NULL,NULL,$dataType);
}



/*  ***** doResponse ***** 
	description: creates JSON response (for device)
					and sends (or echoes for now)
	params: progress - the return value
			message - message to send along
			score [opt] - company CSR score
			company [opt] - company name
			upc [opt] - UPC code of product that was looked up
			desc [opt] - description of product that was looked up
			alias [opt] - another name of the company
			dataType [opt] - format to return data in
							null -> JSON
							PHP -> php array
	return: PHP array or echo JSON
	*************** */
function doResponse($progress, $message, $score=NULL, $company=NULL, $upc=NULL, $desc=NULL, $alias=NULL, $dataType=NULL)
{	// consideration: return status code
	// consideration: log error for certain failures
	// consideration: create optional param to prevent sending http response
	/*	***** Response Array *****
		"PROGRESS" => {
			<0 - always display message on device
			0-99: didn't try UPC lookup
				0 - no input parameters specified
			100-199: failed UPC lookup
				100 - error looking up UPC from digitEyes/etc (HTTP error)
				105 - UPC not found in digiteyes/etc database
				115 - cURL not enabled - cannot make HTTP request
			200-299: SQL errors
				200 - error connecting to JM2 score database
				201 - error with database query
			300-399: errors with JM2 data - non SQL
				300 - could not find company
			900-999: testing purposes only, not to be displayed to end user
			1000 - success
				1000 - have score/success
		}
		"MSG" => error/success message [opt]
		"COMPANY" => company [opt]
		"COMPALIAS" => other name for company [opt]
		"RATING" => company score [opt]
		"UPC" => upc passed in [opt]
		"DESCRIPTION" => description of product [opt]
	*/
	$array;
	if($progress == 1000)	// success
	{	$message = "Success!";	}
	// TODO: add messages for all progress codes
	$array["PROGRESS"] = $progress;
	$array["MSG"] = $message;
	if( !empty($score))
		$array["RATING"] = $score;
	if( !empty($company))
		$array["COMPANY"] = $company;
	if( !empty($upc))
		$array["UPC"] = $upc;
	if( !empty($desc))
		$array["DESCRIPTION"] = $desc;
	if( !empty($alias))
		$array["COMPALIAS"] = $alias;

	if( $dataType = "PHP")
	{	return $array;	}
	else
	{	$jsonResp = json_encode($array);
		// TODO: check for encode error and make a note of it

		// ********** HTTP RESPONSE **********
		// TODO: make a real HHTP response
		// for now, just echo JSON
			echo $jsonResp;
		/* need pecl_http library...
		HttpResponse::status(200);
		HttpResponse::setContentType('application/json');
		HttpResponse::setData($jsonResp);
		HttpResponse::Send();
		*/
	}
}



/*  ***** isCompOrUPC *****
	description: determine if input is UPC or company name
	params: upcCode - UPC to look up
			dataType [opt] - format to return. See doResponse for details
	return: JSON echoed or PHP array
*/
function isCompOrUPC($input, $dataType)
{	// TODO: test this in detail, mainly the regex part
	$temp = preg_replace("/[^0-9]/", "", $input);
	$inputLen = strlen($temp);
	if(($inputLen>4) && ($inputLen<21))
	{	return getProductDigitEyes($temp,$dataType);	}
	else
	{	return getScoreForCompany($input);	}
}



/*  ***** getProductDigitEyes *****
	description: queries DigitEyes API for UPC
	params: upcCode - UPC to look up
			dataType [opt] - format to return. See doResponse for details
	return: JSON echoed or PHP array
*/
function getProductDigitEyes($upcCode,$dataType)
{	// TODO: make sure UPC code is only numbers
	// if searchtype=UPC we haven't done any validation on the upc code
	// ********** Step 1: Generate URL for digit-eyes HTTP call **********
	$baseURL = "http://www.digit-eyes.com/gtin/";
	$respType = "json"; // "xml";
	$DEVersion = "v2_0"; // digiteyes version 2.0
	$lang = "en";	// english
	// https://www.digit-eyes.com/cgi-bin/digiteyes.fcgi?action=myAccount
	$app_key = "/3PE8EMB+Iq9"; // K code
	$authKey = "Sj52B5q7e6Vk9Kg7"; // M code
	$signature = base64_encode(hash_hmac('sha1', $upcCode, $authKey, true));
	
	$fullUrl = $baseURL . $DEVersion . "/" . $respType
		. "/?" .
		"&upc_code=" . $upcCode .
		"&app_key=" . $app_key .
		"&signature=" . $signature .
		"&language=" . $lang;

	// ********** Step 2: Make HTTP Call **********
	/*	tried httpRequest - issues with installing the extension
		tried file_get_contents - issues with errors when given a bad UPC code
			$temp = file_get_contents($fullUrl);
		now onto cURL
	*/
	if( !function_exists('curl_version'))	// make sure cURL is enabled before trying to make call
	{	return doResponse(115,"Curl not enabled",NULL,NULL,NULL,NULL,NULL,$dataType);
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $fullUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // get a 1 if set to false
	curl_setopt($ch, CURLOPT_HEADER, false);
	$httpResponse = curl_exec($ch);
	$httpGetRespCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if( $httpGetRespCode != 200) // TODO: make this a real error message
	{	return doResponse(100,"Response code: " . $httpGetRespCode,NULL,NULL,NULL,NULL,NULL,$dataType);
	}
	
	// ********** Step 3: Parse JSON **********
	$JSONObject = json_decode($httpResponse, true);
	$mfctCompany = NULL;
	$gcpCompany = NULL;
	$brand = NULL;
	$description = NULL;
	
	if(isset($JSONObject["manufacturer"]["company"]))
		$mfctCompany = $JSONObject["manufacturer"]["company"];
	if(isset($JSONObject["gcp"]["company"]))
		$gcpCompany = $JSONObject["gcp"]["company"];
	if(isset($JSONObject["brand"]))
		$brand = $JSONObject["brand"];
	if(isset($JSONObject["description"]))
		$description = $JSONObject["description"];
	if( empty($gcpCompany) && empty($mfctCompany) && empty($brand) && empty($description))	// no usefull data returned
	{	// TODO: make call to query knowProducts table before giving up...maybe do this beofre digiteyes?
		return doResponse(105,"No product info returned",NULL,NULL,NULL,NULL,NULL,$dataType);
		// TODO: parse through JSON to get error message
		// or maybe its not worth it...
	}
	
	// ********** Step 4: Query Scores DB **********
	$upcReturned = $upcCode;
	if( isset($JSONObject["upc_code"]))
		$upcReturned = $JSONObject["upc_code"];
	return getScoreForData($gcpCompany, $mfctCompany, $brand, $description, $upcReturned, $dataType);
}



/*  ***** getScoreForData *****
	description: looks up score given search data
	params: company [opt] - company name
			companyOther [opt] - other possible name for company
			brand [opt] - name of brand of product
			description [opt] - description of product that company makes
			upc [opt] - UPC code that was queried
			dataType [opt] - format to return. See doResponse for details
	return: JSON echoed or PHP array
	*************** */
function getScoreForData($company, $companyOther="", $brand="", $description="", $upc="", $dataType=NULL)
{	$ourScore = FALSE;
	// if a company's name is 0 (the digit zero) then this will always fail. cest la vie
	if( !$ourScore && !empty($company))
		$ourScore = getScoreForCompany($company);
	if(!$ourScore && !empty($companyOther))
		$ourScore = getScoreForCompany($companyOther);
	// TODO: somehow get company name from brand
	if( !$ourScore && !empty($upc))
		$ourScore = getScoreForUPC($upc);
	
	$replyCode=300;	// could not find score in database
	if($ourScore)
		$replyCode = 1000;	// success
	// TODO: do something about return messages?
	return doResponse($replyCode,"",$ourScore,$company,$upc,$description,$companyOther,$dataType);
}



/*  ***** getScoreForCompany *****
	description: looks up score given company name
					checks both the score table and the alias table
	params: company - company name
	return: score for company if found
			FALSE if not found (yay php and mixed data types)
	*************** */
function getScoreForCompany($company)
{	$con = getDBCon();
	if( $con == "")	// could not connect
	{	//doResponse(201, "Could not connect to database");
		return FALSE;
	}
	// if here, we have a PDO connection
	global $scoreTable;
	$stmt = $con->prepare("SELECT * FROM $scoreTable WHERE UPPER(company) = ?");
	$stmt->bindParam(1, strtoupper($company), PDO::PARAM_STR);
	$stmt->execute();
	if( $stmt->rowCount() > 0)	// TODO: drop error if more than 1 result
	{	$rslt = $stmt->fetch(PDO::FETCH_OBJ);
		return $rslt->score;
	}
	// not on main score table, now check alias
	global $aliasTable;
	$stmt = $con->prepare("SELECT * FROM $scoreTable WHERE companyID in (SELECT companyID from $aliasTable WHERE UPPER(alias) = ?)");
	$stmt->bindParam(1, strtoupper($company), PDO::PARAM_STR);
	$stmt->execute();
	if( $stmt->rowCount() > 0)	// TODO: drop error if more than 1 result
	{	$rslt = $stmt->fetch(PDO::FETCH_OBJ);
		return $rslt->score;
	}
	// not found on either table, quit sad :(
	return FALSE;
}



/*  ***** getScoreForUPC *****
	description: looks up company from knownProducts then score for company
					this searches JM2 database
	params: upc - upc code to search for
	return: score for company if found
			FALSE if not found (yay php and mixed data types)
	*************** */
function getScoreForUPC($upc)
{	$con = getDBCon();
	if( $con == "")	// could not connect
	{	//doResponse(201, "Could not connect to database");
		return FALSE;
	}
	// if here, we have a PDO connection
	global $upcTable, $scoreTable; // TODO: add upcTable to base level file
	// TODO: 
	$query = "SELECT * FROM $scoreTable where companyID in (SELECT companyID from $upcTable where upccode = ?)";
	$stmt = $con->prepare($query);
	$stmt->bindParam(1, strtoupper($upc), PDO::PARAM_STR);
	$stmt->execute();
	if( $stmt->rowCount() > 0)	// TODO: drop error if more than 1 result
	{	$rslt = $stmt->fetch(PDO::FETCH_OBJ);
		return $rslt->score;
	}
}



/*  ***** getDBCon *****
	description: creates a PDO connection, view only
	params: none
	return: PDO object on success, "" on failure
	*************** */
function getDBCon()	{
	try {
		global $sql_SA, $database_SA, $user_SA, $pwd_SA;
		$db = new PDO("mysql:host=$sql_SA;dbname=$database_SA", $user_SA, $pwd_SA);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $db;
	}
	catch(PDOException $e) {
		// TODO: log error somewhere
		return "";
	}
}
?>
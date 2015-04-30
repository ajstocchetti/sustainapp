<?php
REQUIRE_ONCE($_SERVER['DOCUMENT_ROOT']."/../jm2_sustainapp_db.php");

processParams();


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
	return: nothing, echo to screen
	*************** */
function doResponse($progress, $message, $score=NULL, $company=NULL, $upc=NULL, $desc=NULL, $alias=NULL)
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
	$array;// = array();
	if($progress == 1000)	// success
	{	$message = "Success!";	}
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
	
	// TODO: should php/json return type be moved to processParams()?
	$retType = NULL;
	if( isset($_REQUEST['returntype'])
	{	$retType = $_REQUEST['returntype'];	}
	if( $retType = "PHP")
		return $array;
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





/*  ***** processParams *****
	description: reads user input parameters and begins score lookup
	params: none
	return: nothing, all paths call into doResponse
	*************** */
function processParams()
{	// get parameters
	$upcCode = NULL;
	if( isset($_REQUEST['upcc']))
	{	$upcCode=htmlspecialchars($_REQUEST["upcc"]);	}
	if( empty($upcCode))
	{	// quit now if no UPC or company to lookup
		// no need to worry if all 0's passed in - this is a private code, not an actual UPC
		// TODO: make a real HTTP response when do the same for $retData
		doResponse(0, "No data given. Please try again");
		return;
	}
	$searchMethod = "";
	if( isset($_REQUEST['searchtype']))
	{	$searchMethod=strtoupper($_REQUEST['searchtype']);	}
	if($searchMethod == "UPC")
	{	getProductDigitEyes($upcCode);	}
	elseif($searchMethod == "COMPANY")
	{	getScoreForCompany($upcCode);	}
	else // search type not set or junk value
	{	isCompOrUPC($upcCode);	}
}

function isCompOrUPC($input)
{	// TODO: test this in detail, mainly the $temp part
	$temp = preg_replace("/[^0-9]/", "", $input);
	$inputLen = strlen($temp);
	if(($inputLen>4) && ($inputLen<21))
	{	getProductDigitEyes($temp);	}
	else
	{	getScoreForCompany($input);	}
}





/*  ***** getProductDigitEyes *****
	description: queries DigitEyes API for UPC
	params: upcCode - UPC to look up
	return: nothing, calls doResponse on failure,
			getScoreForCompany on success
*/
function getProductDigitEyes($upcCode)
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
	{	doResponse(115,"Curl not enabled");
		return;
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $fullUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // get a 1 if set to false
	curl_setopt($ch, CURLOPT_HEADER, false);
	$httpResponse = curl_exec($ch);
	$httpGetRespCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if( $httpGetRespCode != 200) // TODO: make this a real error message
	{	doResponse(100,"Response code: " . $httpGetRespCode);
		return;
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
		doResponse(105,"No product info returned");
		return;
		// TODO: parse through JSON to get error message
		// or maybe its not worth it...
	}
	
	// ********** Step 4: Query Scores DB **********
	$upcReturned = $upcCode;
	if( isset($JSONObject["upc_code"]))
		$upcReturned = $JSONObject["upc_code"];
	getScoreForData($gcpCompany, $mfctCompany, $brand, $description, $upcReturned);
}





/*  ***** getScoreForData *****
	description: looks up score given search data
	params: company [opt] - company name
			companyOther [opt] - other possible name for company
			brand [opt] - name of brand of product
			description [opt] - description of product that company makes
			upc [opt] - UPC code that was queried
	return: nothing, calls doResponse
	*************** */
function getScoreForData($company, $companyOther="", $brand="", $description="", $upc="")
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
	doResponse($replyCode,"",$ourScore,$company,$upc,$description,$companyOther);
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
		return "";
		// TODO: log error somewhere
	}
}
?>
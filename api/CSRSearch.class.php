<?php
REQUIRE_ONCE($_SERVER['DOCUMENT_ROOT']."/../jm2_sustainapp_db.php");
REQUIRE_ONCE($_SERVER['DOCUMENT_ROOT']."/api/ErrorLogs.class.php");

class CSRSearch
{	
	/*	(1) initiate object
		(2) search
		(3)	getResults
	*/
	// TODO: general todo: add productDescription to UPC table logging and retrevial (need to modify sql table)

	private $userInput;	// set once, NEVER MODIFY
	private $searchTypeInput;	// set once, NEVER MODIFY
	private $searchMethod;	// UPC or COMPANY
	private $processedUPC;	// what we search on, may have been modified by this code
	private $returnedUPC;	// what is returned from 3rd party UPC lookup 
	private $companyName;
	private $companyAlias;
	private $responseCode;
	private $responseMessage;
	private $productDescription;
	private $score;
	
	private $dbRead="";
	private $dbWrite="";
	
	
	// constructor
	function CSRSearch($input, $type=NULL)
	{	$this->userInput = $input;
		$this->searchTypeInput = $type;
	}



	// TODO: header
	function search()
	{	$this->setSearchMethod();
		if( isset($this->processedUPC))	// search type is UPC
		{	$this->getCompanyFromUPC();	}
		if( isset($this->companyName))	// search type is company or UPC lookup was successful
		{	$this->getScoreForCompany();	}
	}



	// TODO: header
	function getResults()
	{	// TODO: this
		if(empty($this->responseCode))
		{	$this->responseAndMessage(0,"An unexpected error occurred");
			$text = "No response code calculated for search term: " . $this->userInput;
			$text .= " and search type: " . $this->searchTypeInput;
			$this->logError(0, $text, __FILE__, __LINE__);
		}
		$array;
		$array["PROGRESS"] = 	$this->responseCode;
		$array["MSG"] = 		$this->responseMessage;
		$array["RATING"] = 		$this->score;
		$array["COMPANY"] = 	$this->companyName;
		$array["UPC"] = 		$this->returnedUPC;
		if( empty($array["UPC"]))	{
			$array["UPC"] = 	$this->processedUPC;
		}	
		$array["DESCRIPTION"] = $this->productDescription;
		$array["COMPALIAS"] = 	$this->companyAlias;
		$jsonResp = json_encode($array);
		// TODO: check for encode error and make a note of it
		 echo $jsonResp;
	}





	/* ********** database connection methods ********** */


	/*  ***** getDBCon *****
	description: creates a PDO connection
	params: write (opt) - set to TRUE to get write access
	return: PDO object on success, "" on failure
	sets:	nothing
	*************** */
	private function getDBCon($write=FALSE)	{
		try {
			global $sql_SA, $database_SA, $user_SA, $pwd_SA, $user_write_SA, $pwd_write_SA;
			$dbLogin;
			$dbPassword;
			if($write==TRUE)
			{	$dbLogin = $user_write_SA;
				$dbPassword = $pwd_write_SA;
			}
			else
			{	$dbLogin = $user_SA;
				$dbPassword = $pwd_SA;
			}
			$db = new PDO("mysql:host=$sql_SA;dbname=$database_SA", $dbLogin, $dbPassword);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $db;
		}
		catch(PDOException $e) {
			$this->logError(1, $e->getMessage(), __FILE__, __LINE__);
			return NULL;
		}
	}
	

	/*  ***** setDBRead *****
	description: establishes PDO read connection
	params: none
	return: nothing
	sets:	dbRead (on success)
	*************** */
	private function setDBRead()
	{	if( !$this->canReadDB())
			$this->dbRead = $this->getDBCon();
	}
	
	
	
	/*  ***** setDBWrite *****
	description: establishes PDO write connection
	params: none
	return: nothing
	sets:	dbWrite (on success)
	*************** */
	private function setDBWrite()
	{	if( !$this->canWriteDB())
			$this->dbWrite = $this->getDBCon(TRUE);
	}
	
	
	
	/*  ***** canReadDB *****
	description: accessor method for dbRead - determines if dbRead is set
	params: none
	return: true if dbRead is set, false if not
	sets:	nothing
	*************** */
	function canReadDB()
	{	return !(empty($this->dbRead));	}



	/*  ***** canWriteB *****
	description: accessor method for dbWrite - determines if dbWrite is set
	params: none
	return: true if dbWrite is set, false if not
	sets:	nothing
	*************** */
	function canWriteDB()
	{	return !(empty($this->dbWrite));	}





	/* ********** UPC and Company lookup methods ********** */


	/*  ***** setSearchMethod *****
	description: set search params based on user input
	params: none
	return: nothing
	sets:	searchMethod and (processedUPC or companyName)
	*************** */
	private function setSearchMethod()
	{	if(!empty($this->userInput))	// sanity check - this will fail if '0' is passed in
		{	$type=$this->searchTypeInput;
			if( $type=="UPC")
			{	$this->searchMethod="UPC";
				$this->processedUPC = $this->userInput;
			}
			elseif( $type=="COMPANY")
			{	$this->searchMethod="COMPANY";
				$this->companyName=$this->userInput;
			}
			else
				$this->determineSearchType($this->userInput);
		}
		else
		{	$this->responseAndMessage(1);	}
	}



	/*  ***** determineSearchType *****
	description: determine if input is UPC or company name
	params: input - the search string
	return: nothing
	sets:	searchMethod and (processedUPC or companyName)
	*************** */
	private function determineSearchType($input)
	{	// TODO: test this in detail, mainly the regex part
		// this will turn a company into a UPC if it has 5-20 numbers in it. so be careful
		$temp = preg_replace("/[^0-9]/", "", $input);
		$inputLen = strlen($temp);
		if(($inputLen>4) && ($inputLen<21))
		{	$this->searchMethod="UPC";
			$this->processedUPC = $temp;
		}
		else
		{	$this->searchMethod="COMPANY";
			$this->companyName=$input;
		}
	}



	/*  ***** getScoreForCompany *****
	description: looks up score for company name
				checks both the score table and the alias table
	params:	none
	return: nothing
	sets:	score (on success)
	*************** */
	private function getScoreForCompany()
	{	$company = $this->companyName;
		if( !empty($company))	// sanity check
		{	$this->setDBRead();
			if( $this->canReadDB())
			{	global $scoreTable;
				$con = $this->dbRead;
				$stmt = $con->prepare("SELECT * FROM $scoreTable WHERE UPPER(company) = ?");
				$stmt->bindParam(1, strtoupper($company), PDO::PARAM_STR);
				$stmt->execute();
				if( $stmt->rowCount() > 0)	// TODO: drop error if more than 1 result
				{	$rslt = $stmt->fetch(PDO::FETCH_OBJ);
					$this->score = $rslt->score;
					$this->checkCompanyName($rslt->company);
					$this->responseAndMessage(1000);
				}
				else
				{	global $aliasTable;
					$stmt = $con->prepare("SELECT * FROM $scoreTable WHERE companyID in (SELECT companyID from $aliasTable WHERE UPPER(alias) = ?)");
					$stmt->bindParam(1, strtoupper($company), PDO::PARAM_STR);
					$stmt->execute();
					if( $stmt->rowCount() > 0)	// TODO: drop error if more than 1 result
					{	$rslt = $stmt->fetch(PDO::FETCH_OBJ);
						$this->score = $rslt->score;
						$this->checkCompanyName($rslt->company);
						$this->responseAndMessage(1000);
					}
					else	// no match at score or alias table
					{	$this->responseAndMessage(300);	}
				}	// end alias search
			}	// end database search
			else	// could not connect to database
			{	// SQL error logged when attempting to get PDO connection
				$this->responseAndMessage(200);
			}
		}	// end have company
	}



	// TODO: header
	private function checkCompanyName($scoreCompany)
	{	$inputName = $this->companyName;
		if( $inputName != $scoreCompany)
		{	$this->companyAlias = $inputName;
			$this->companyName = $scoreCompany;
		}
	}



	/*  ***** getCompanyFromUPC *****
	description: searches UPC table for company name
					calls into digit-eyes lookup if none found
	params:	none
	return: nothing
	sets:	companyName (on success)
	*************** */
	private function getCompanyFromUPC()
	{	$upc = $this->processedUPC;
		if( !empty($upc))	// sanity check
		{	$this->setDBRead();
			if( $this->canReadDB())
			{	global $upcTable;
				$con = $this->dbRead;
				$query = "SELECT * from $upcTable where upccode = ?";
				// TODO: update to check how recent this is, check if company ID exists
				$stmt = $con->prepare($query);
				$stmt->bindParam(1, strtoupper($upc), PDO::PARAM_STR);
				$stmt->execute();
				if( $stmt->rowCount() > 0)	// TODO: drop error if more than 1 result
				{	$rslt = $stmt->fetch(PDO::FETCH_OBJ);
					$this->companyName = $rslt->companyName;
					$this->productDescription = $rslt->description;
				}
				else	// UPC not in our table
				{	$this->lookupUPCDigiteyes($upc);	}
			}
			else	// can not connect to database
			{	// database error logged when attempting to get PDO connection
				// set status in case digit-eyes call fails
				$this->responseAndMessage(200);
				$this->lookupUPCDigiteyes($upc);
				// TODO: make sure this works with final program flow
			}
		}	// end have UPC
	}



	/*  ***** lookupUPCDigiteyes *****
	description: queries digit-eyes for upc code
	params:	upcCode - the upc code to search for
	return: nothing
	sets:	processedUPC, companyName, companyAlia, productDescription (on success)	
	*************** */
	private function lookupUPCDigiteyes($upcCode)
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
		if( !function_exists('curl_version'))	// make sure cURL is enabled before trying to make call
		{	$this->logError(600, "Curl not enabled", __FILE__, __LINE__);
			$this->responseAndMessage(115);	// curl not enabled
		}
		else
		{	$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $fullUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // get a 1 if set to false
			curl_setopt($ch, CURLOPT_HEADER, false);
			$httpResponse = curl_exec($ch);
			$httpGetRespCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if( $httpGetRespCode != 200)
			{	$this->logError(700, "Response code: " . $httpGetRespCode, __FILE__, __LINE__);
				// TODO: parse through result, maybe give response code 105
				$this->responseAndMessage(100);
			}
			else	// successful curl call
			{	// ********** Step 3: Parse JSON **********
				$JSONObject = json_decode($httpResponse, true);
				/* have (some of) the following to work with:
					$JSONObject["upc_code"]
					$JSONObject["manufacturer"]["company"]
					$JSONObject["gcp"]["company"]
					$JSONObject["brand"]
					$JSONObject["description"]			*/
				if(isset($JSONObject["upc_code"]))
					$this->returnedUPC = $JSONObject["upc_code"];
				if(isset($JSONObject["manufacturer"]["company"]))
				{	$this->companyName = $JSONObject["manufacturer"]["company"];
					$this->companyAlias = $JSONObject["manufacturer"]["company"];
				}
				if(isset($JSONObject["gcp"]["company"]))
					$this->companyName = $JSONObject["gcp"]["company"];
				if(isset($JSONObject["description"]))
					$this->productDescription = $JSONObject["description"];
					
				// Log results to UPC table for faster lookup next time
				if( !empty($this->companyName))	// got info from digit-eyes
				{	$this->setDBWrite();
					if( $this->canWriteDB())
					{	$this->updateUPCTable($this->dbWrite,$this->processedUPC,$this->returnedUPC,$this->companyName,$this->productDescription,"Digiteyes");
						if($this->processedUPC !== $this->returnedUPC) {
							// need type check (!==) - need strings due to leading zeroes in barcodes will drop if integers
							$this->updateUPCTable($this->dbWrite,$this->returnedUPC,$this->returnedUPC,$this->companyName,$this->productDescription,"Digiteyes");
						}
					}
					else	// can't make connection
					{	$errMessage = "Could not update UPC table with UPC: " . $this->returnedUPC;
						$errMessage .= " and company: " . $this->companyName;
						$this->logError(3, $errMessage , __FILE__, __LINE__);
					}
				}	// end of updating UPC table
				else	// no useful data from digit-eyes
				{	$this->responseAndMessage(105);
				}
			}	// end of curl result processing
		}	// end of curl call
	}



	/*  ***** updateUPCTable *****
	description: updates UPC table with results from digit-eyes query
	params:	connection
			upc
			company
			desc - description of product
			source - where getting the UPC from
			compID
	return: nothing
	sets:	nothing
	*************** */
	private function updateUPCTable($connection,$upc,$retUPC,$company,$desc=NULL,$source=NULL,$compID=NULL)
	{	global $upcTable;
		// replace into will update, insert ignore will not update (the ignore silences the error) 
		$query = "REPLACE INTO $upcTable (upccode, properUPC, companyName, description, source, companyID) VALUES (?, ?, ?, ?, ?, ?)";
		// $query = "INSERT IGNORE INTO $upcTable (upccode, properUPC, companyName, description, source, companyID) VALUES (?, ?, ?, ?, ?, ?)";
		
		try	{
			$stmt = $connection->prepare($query);
			$stmt->bindParam(1, strtoupper($upc), PDO::PARAM_STR);
			$stmt->bindParam(2, strtoupper($retUPC), PDO::PARAM_STR);
			$stmt->bindParam(3, $company, PDO::PARAM_STR);
			$stmt->bindParam(4, $desc, PDO::PARAM_STR);
			$stmt->bindParam(5, $source, PDO::PARAM_STR);
			$stmt->bindParam(6, $compID, PDO::PARAM_STR);
			$stmt->execute();
		} catch(PDOException $e) {
			$this->logError(3,$e->getMessage(),__FILE__,__LINE__);
		}
	}





	/* ********** methods for preparing response ********** */


	/*  ***** responseAndMessage *****
	description: 
	params:	respCode - the response code
			message [opt] - the message to display for custom response code
	return: nothing
	sets:	responseCode, responseMessage
	
	codes:
		<0 - always display message on device
		1-99: didn't try UPC lookup
			1 - no input parameters specified
		100-199: failed UPC lookup
			100 - error looking up UPC from digitEyes/etc (HTTP error)
			105 - UPC not found in digiteyes/etc database
			115 - cURL not enabled - cannot make HTTP request
		200-299: SQL errors
			200 - error connecting to JM2 score database
			201 - error with database query
		300-399: errors with JM2 data - non SQL
			300 - could not find company
		1000 - success
			1000 - have score/success
		1100-1199: testing purposes only, not to be displayed to end user
	*************** */
	private function responseAndMessage($respCode,$message="")
	{	switch($respCode)
		{	case 1:
				$message = "No data given. Please scan a UPC or enter a company and try again";
				break;
			case 100:
				//$message = "Unable to access company lookup service at this time.";
				$message = "We could not find a product that matches " . $this->userInput . ". Please try again.";
				break;
			case 105:
				$message = "We could not find a product that matches " . $this->userInput . ". Please try again.";
				break;
			case 115:
				$message = "Unable to look up barcode at this time.";
				break;
			case 200:
				$message = "We were unable to look up a score at this time. Please try again.";
				break;
			case 201:
				$message = "We were unable to look up a score at this time. Please try again.";
				break;
			case 300:
				$message = "Unfortunately, we do not have a Social Responsibility rating for " . $this->companyName . " at this time. Please bear with us while we grow our database of companies.";
				break;
			case 1000:
				$message = "Rating: " . $this->score;
				break;
		}
		if( strlen($message)==0)	// passed in non-defined code with no message
			$message = "Could not find a CSR rating";
		$this->responseCode = $respCode;
		$this->responseMessage = $message;
	}





	/* ********** error logging methods ********** */

	// TODO: header
	private function logError($errorType, $errorText, $file, $lineNum)
	{	$errLog = new errorLoger();
		$errLog->logError($errorType, $errorText, $file, $lineNum);
	}


}
?>
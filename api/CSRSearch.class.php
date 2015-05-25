<?php
REQUIRE_ONCE($_SERVER['DOCUMENT_ROOT']."/../jm2_sustainapp_db.php");

class CSRSearch
{	
	private $userInput;	// set once, NEVER MODIFY
	private $searchTypeInput;	// set once, NEVER MODIFY
	private $searchMethod;	// UPC or COMPANY
	private $processedUPC;
	private $companyName;
	private $companyAlias;
	private $responseCode;
	private $responseMessage;
	private $productDescription;
	private $score;
	
	private $dbRead="";
	private $dbWrite="";
	
	
	// constructor
	function scoreSearcher($input, $type)
	{	$this->userInput = $input;
		$this->searchTypeInput = $type;
	}
	
	
	/*	setSearchMethod ~> determineSearchType	<sets searchMethod>
		if upc -> getCompanyFromUPC ~> lookupUPCDigiteyes <sets companyName>
		if $this->companyName is set ->getScoreForCompany	<sets score>
		some sort of return function
	*/




	
	/*  ***** getDBCon *****
	description: creates a PDO connection, view only
	params: write (opt) - set to yes to get write access
	return: PDO object on success, "" on failure
	*************** */
	private function getDBCon($write)	{
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
			// TODO: log error somewhere
			return "";
		}
	}
	
	
	private function setDBRead()
	{	if( $this->canReadDB())
			$this->dbRead = $this->getDBCon();
	}
	
	private function setDBWrite()
	{	if( $this->canWriteDB())
			$this->dbWrite = $this->getDBCon(TRUE);
	}
	
	function canReadDB()
	{	return !empty($this->dbRead);	}
	function canWriteDB()
	{	return !empty($this->dbWrite);	}
	
	

	
	
	
	
	
	
	
	

	/*  ***** determineSearchType *****
	description: determine if input is UPC or company name
	params: input - the search string
	*/
	function determineSearchType($input)
	{	// TODO: test this in detail, mainly the regex part
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
	
	
	
	function setSearchMethod()
	{	if(!empty($this->userInput))	// sanity check
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
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/*  ***** getScoreForCompany *****
	description: looks up score given company name
					checks both the score table and the alias table
	params: 
	return: 
	sets:
	*************** */
	function getScoreForCompany()
	{	$company = $this->companyName;
		if( !empty($company))	// sanity check
		{	$this->setDBRead();
			if( $this->canReadDB())
			{	global $scoreTable;
				$stmt = $con->prepare("SELECT * FROM $scoreTable WHERE UPPER(company) = ?");
				$stmt->bindParam(1, strtoupper($company), PDO::PARAM_STR);
				$stmt->execute();
				if( $stmt->rowCount() > 0)	// TODO: drop error if more than 1 result
				{	$rslt = $stmt->fetch(PDO::FETCH_OBJ);
					$this->score = $rslt->score;
				}
				else
				{	global $aliasTable;
					$stmt = $con->prepare("SELECT * FROM $scoreTable WHERE companyID in (SELECT companyID from $aliasTable WHERE UPPER(alias) = ?)");
					$stmt->bindParam(1, strtoupper($company), PDO::PARAM_STR);
					$stmt->execute();
					if( $stmt->rowCount() > 0)	// TODO: drop error if more than 1 result
					{	$rslt = $stmt->fetch(PDO::FETCH_OBJ);
						$this->score = $rslt->score;
					}
				}	// end alias search
			}	// end database search
			else	// could not connect to database
			{	// TODO: create function to log error, set status and message
			}
		}	// end have company
	}
	
	
	
	
	
	function getCompanyFromUPC()
	{	$upc = $this->processedUPC;
		if( !empty($upc))	// sanity check
		{	$this->setDBRead();
			if( $this->canReadDB())
			{	global $upcTable;
				$query = "SELECT * from $upcTable where upccode = ?)";
				// TODO: update to check how recent this is, check if company ID exists
				$stmt = $con->prepare($query);
				$stmt->bindParam(1, strtoupper($upc), PDO::PARAM_STR);
				$stmt->execute();
				if( $stmt->rowCount() > 0)	// TODO: drop error if more than 1 result
				{	$rslt = $stmt->fetch(PDO::FETCH_OBJ);
					$this->companyName = $rslt->companyName;
				}
				else	// UPC not in our table
				{	lookupUPCDigiteyes($upc);	}
			}
			else	// can not connect to database
			{	// TODO: create function to log error, set status and message
				lookupUPCDigiteyes($upc);
			}
		}	// end have UPC
	}

	
	
	
	
	function lookupUPCDigiteyes($upcCode)
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
		{	// TODO: log error here
			// TODO: set response code, message
		}
		else
		{	$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $fullUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // get a 1 if set to false
			curl_setopt($ch, CURLOPT_HEADER, false);
			$httpResponse = curl_exec($ch);
			$httpGetRespCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if( $httpGetRespCode != 200) // TODO: make this a real error message
			{	// TODO: log error here
				// TODO: set message, response code
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
					$this->processedUPC = $JSONObject["upc_code"];
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
					{	updateUPCTable($this->dbWrite,$this->processedUPC,$this->companyName);
						if($this->processedUPC != $this->userInput)
							updateUPCTable($this->dbWrite,$this->userInput,$this->companyName);
					}
					else	// can't make connection
					{	// TODO: log error, do NOT set message, status
					}
				}
			}	// end of curl result processing
		}	// end of curl call
}
		
		
		
		
function updateUPCTable($connection,$upc,$company,$compID=NULL)
{	global $upcTable;
	// replace into will update, insert ignore will not update (the ignore silences the error) 
	$query = "REPLACE INTO $upcTable (upccode, companyName, companyID) VALUES (?, ?, ?)";
	// $query = "INSERT IGNORE INTO $upcTable (upccode, companyName, companyID) VALUES (?, ?, ?)";
	
	$stmt = $connection->prepare($query);
	$stmt->bindParam(1, strtoupper($upc), PDO::PARAM_STR);
	$stmt->bindParam(2, $company, PDO::PARAM_STR);
	$stmt->bindParam(3, $compID, PDO::PARAM_STR);
	$stmt->execute();
	// TODO: log error if stmt fails		
}
		
		
		



	// need two error functions, one to handle when can't connect to DB results in not being able to lookup score
	//		this also sets message and status
	// and another when can't connect to db doesn't cause score issue (such as when logging digiteyes results)
	//		this does not set message/status
	
	
	// TODO: general todo: add productDescription to UPC table logging and retrevial (need to modify sql table)
	
	
	
	
	
	

}




$ajs = new scoreSearcher("1","1");


?>
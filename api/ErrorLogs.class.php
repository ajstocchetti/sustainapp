<?php

/*
	TODO: 
		create variables for: directory, delimiter (tab), filename
		convert date to appropriate time zone
*/

class errorLoger
{	
	/* ********************
		errTypes:
			(0) other
			(1) connection error
			(2) invalid conn
			(3) SQL insert error
			(4) SQL select error
			(5) Misc PDO error
			[section specific errors]
			(500) Non-specific (could be SQL or bad input)
			(501) Audit Trail Update Error
			(600) Curl Error
			(700) Digit-eyes Error
	*/
	function logError( $errType, $error, $file, $errLine)
	{	$today = date("Y_m_d");
		//$timeNow = date("H:i:s");
		
		$tab = "\t";
		$line = "";
		$header = "";
		
		$errTypeText = "";
		switch($errType)
		{	case 0:
				$errTypeText = "Other";
				break;
			case 1:
				$errTypeText = "Connection Error";
				break;
			case 2:
				$errTypeText = "Invalid Connection";
				break;
			case 3:
				$errTypeText = "SQL Insert Error";
				break;
			case 4:
				$errTypeText = "SQL Select Error";
				break;
			case 5:
				$errTypeText = "Miscellaneous PDO Error";
				break;
			case 500:
				$errTypeText = "SQL or Input Error";
				break;
			case 501:
				$errTypeText = "Audit Trail Update Error";
				break;
			case 600:
				$errTypeText = "Curl Error";
				break;
			case 700:
				$errTypeText = "Digit-eyes Error";
				break;
			default:
				$errTypeText = "Unknown";
		}	
			
		
		$line .= "2".$tab;	// error log version #2
		$header .= "Error Log Version".$tab;
		$line .= $today.$tab;	// date
		$header .= "DATE".$tab;
		$line .= date("H:i:s").$tab;	// time
		$header .= "TIME".$tab;
		$line .= $errType.$tab;	// error type
		$header .= "ERROR TYPE".$tab;
		$line .= $errTypeText.$tab; // error type text
		$header .= "ERROR TYPE DESC".$tab;
		$line .= $error.$tab;	// error info
		$header .= "DETAILS".$tab;
		$line .= $file.$tab;	// file
		$header .= "FILE".$tab;
		$line .= $errLine.$tab;	// line
		$header .= "LINE".$tab;
		if(getenv('HTTP_CLIENT_IT'))
			$line .= $_SERVER['HTTP_CLIENT_IP'];	// client IP
		$line .= $tab;
		$header .= "IP - CLIENT".$tab;
		if( getenv('REMOTE_ADDR'))
			$line .= $_SERVER['REMOTE_ADDR'];	// connecting party IP
		$line .= $tab;
		$header .= "IP - CONNECTING".$tab;
		if( getenv('HTTP_X_FORWARDED_FOR'))
			$line .= $_SERVER['HTTP_X_FORWARDED_FOR'];	// forwarded IP
		$line .= $tab;
		$header .= "IP - FORWARDED".$tab;
		
		$line .= "\n";
		$header .= "\n";
		
		$filePath = $_SERVER['DOCUMENT_ROOT'].'/error/log/';
		$fileNm = $today.'.csv';

		$this->writeError($filePath, $fileNm, $line, $header);
	}

	// checks if the directory exists
	// if not, create the directory
	// so it is available to create error log-file
	private function createDir($dirPath)
	{	$status = 0;
		if(! file_exists($dirPath))
		{	//create directory
			if(mkdir($dirPath, 0777, true))
				$status = 10;
			else
				$status = -10;
		}
		else
		{	if(is_dir($dirPath))
				$status = 15;
			else
				$status = -1;
		}
		return $status;
	}

	// checks if the file exists
	// if not, create file
	// and add header
	private function createFile($filepath, $header)
	{	if( !file_exists($filepath))
		{	touch($filepath);
			if( $fp = fopen($filepath, "w"))	// TODO: change to append once sure it works right, just to be safe
			{	fwrite($fp, $header);
				fclose($fp);
			}
		}
	}

	// wrapper function to check if error file exists
	// and create if it doesnt,
	// then write error
	//
	// called by error handling code
	private function writeError($dirPath, $fileName, $info, $header)
	{	if( $this->createDir($dirPath) > 0)
		{	$dirPath = rtrim($dirPath, '/') . '/';
			$fullPath = $dirPath.$fileName;
			$this->createFile($fullPath, $header);
			
			// file created, now log error
			if( $fp = fopen( $fullPath, 'a'))
			{	fwrite($fp, $info);
				fclose($fp);
			}
		}
		// TODO: error handle if we can't create the file
		// (in our error handling code....buuuuuh)
	}


}
?>
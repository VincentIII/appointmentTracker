<?php
	session_start();
	set_error_handler("messageError");
	// DEV NOTES---------------------------------------------------------------------------
	//	created by: 	Vincent Agresti
	//	program name:	Check-In Program
	//	short summary:	Program allows for documentation of checked in PCs and checking out PCs in TechRow
	//
	// GLOBALS-----------------------------------------------------------------------------
	$connection = new mysqli("localhost","root","","checkout");
	if ($connection->connect_error)
	{
		trigger_error('Database connection failed: '.$connection->connect_error,E_USER_ERROR);
	}
	$messages = "";
	$break = FALSE;
	$instanceID = "";
	$pageName = "index.php";
	$defaultDisplay = FALSE;
	$loginSuccess = FALSE;
	
		//Used for Daylight Saving Time Adjustments
	if (date("I", time()) == TRUE)
	{
		$rightNow = date('Y-m-d H:i:s', time()-21600);	//21600 seconds - 6 hours (DST)
	}
	else
	{
		$rightNow = date('Y-m-d H:i:s', time()-18000);	//18000 seconds - 5 hours
	}
	
		//Verify POSTs
	if (!empty($_POST["action"]))
	{
		$fAction = $_POST["action"];
	}
	else
	{
		$fAction = "N/A";
	}
	if (!empty($_POST["instanceID"]))
	{
		$instanceID = $_POST["instanceID"];
	}
	
		//Verify SESSIONs
	if (!empty($_SESSION["USER"]))
	{
		$userName = $_SESSION["USER"];
	}
	else
	{
		$userName = "";
	}
	if (!empty($_SESSION["TYPE"]))
	{
		$userType = $_SESSION["TYPE"];
	}
	else
	{
		$userType = "";
	}
	
		//Verify GETs
	if (!empty($_GET["menu"]))
	{
		$fAction = $_GET["menu"];
	}
	if (!empty($_GET["instance"]))
	{
		$instanceID = $_GET["instance"];
	}
	
	// FUNCTIONS---------------------------------------------------------------------------
	//Display HTML Header
	function displayHead()
	{
		echo "<!DOCTYPE html>
				<head>
					<meta http-equiv='Content-type' content='text/html;charset=UTF-8' />
					<title>Service Drop Off Form</title>
					<link rel='stylesheet' type='text/css' href='reset.css'/>
					<link rel='stylesheet' type='text/css' href='css.css'/>
					<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js'></script>";
		//JQuery/JavaScript for hiding popup messages and clearing pre-filled text boxes
	?>
		<script>
			$(document).ready(function(){
				$(".errorM").click(function(event){
					$(this).hide();
				});
				$(".resultM").click(function(event){
					$(this).hide();
				});
			});
			function clearThis(target){target.value= "";}
		</script>
	<?php
		echo "</head>
				<body>
				<header>TechRow Check-In</header>
					<div class='body'>\n";
	}
	
	//Makes the drop-down form selection boxes for the Search Form
	function generateDropDowns($type)	
	{
		global $connection;
		if (strpos($type, 'consultant') !== FALSE)
		{
			$query = "SELECT consultantUserName AS code, CONCAT_WS(', ', consultantLastName, consultantFirstName) AS name FROM consultants WHERE consultantActive = 1 ORDER BY consultantLastName ASC ";
		}
		if (strpos($type, 'staff') !== FALSE)
		{
			$query = "SELECT consultantUserName AS code, CONCAT_WS(', ', consultantLastName, consultantFirstName) AS name FROM consultants WHERE consultantActive = 1 AND consultantType = 2 ORDER BY consultantLastName ASC ";
		}
		else{}
		
		if ($type == "consultantSearch")
		{
			$dropDownExport = "<select name='consultant'>";
			$dropDownExport .= "<option value='IGNORE'>All</option>\n";
			$type == "consultant";
		}
		else
		{
			$dropDownExport = "<select name='$type'>";
		}
		if ($stmtDD = $connection->prepare($query))
		{
			$stmtDD ->execute();
			$stmtDD ->store_result();
			$stmtDD ->bind_result($code,$name);
			while ($stmtDD ->fetch())
			{
				if ($_POST[$type] == $code && !empty($_POST[$type]))
				{
					$dropDownExport .= "<option value='$code' selected>$name</option>\n";
				}
				else
				{
					$dropDownExport .= "<option value='$code'>$name</option>\n";
				}
			}
		}
		$dropDownExport .= "</select>\n";
		return $dropDownExport;
	}	
	
	//Displays HTML Footer
	function displayFooter()				
	{
		global $userName;
		global $pageName;
		global $loginSuccess;
		echo "</div>
		<footer>
			<div class='footLinks'>
				<a href='$pageName'>[home]</a> <a href='$pageName?menu=create'>[create]</a> <a href='$pageName?menu=search'>[search]</a>
			</div>
			<div class='adminLinks'>";
		if (verifySessions() == 0 && $loginSuccess == FALSE)
		{
			echo"<a href='$pageName?menu=login'>[login]</a>";
		}
		else
		{
			echo"<a href='$pageName?menu=password'>[reset password]</a> <a href='$pageName?menu=logout'>[logout]</a>";
		}
		echo"</div>
		</footer>
			</body>
			</html>";
	}
	
	// Function for basic field validation (present and neither empty nor only white space)
	function isNullOrEmptyString($value)
	{
		return (!isset($value) || trim($value)==='');
	}
	
	//Custom Error Handler
	function messageError($errno,$errstr)
	{
		global $messages;
		$errorTrimmed = str_replace(':','-',$errstr);
		$messages .= "ERROR:[$errno] $errorTrimmed::";
	}
	
	//Displays Success & Error Messages\
	//Takes a the string $messages and breaks it down into individual messages.  Not an array but instead a single string
	function displayMessages()
	{
		global $messages;
		echo "<div class='messages'>";
		$continue = TRUE;
		while ($continue == TRUE)
		{
			$end = strpos($messages, "::");	//If there is no more '::', the custom delimiter I'm using, it will say the message string has ended
			if ($end === FALSE)
			{
				$continue = FALSE;
			}
			else
			{
				$thisMessage = strstr($messages,"::",TRUE);
				$messages = strstr($messages,"::",FALSE);
				$messages = substr($messages,2);
				$type = strstr($thisMessage,":",TRUE);
				$message = strstr($thisMessage,":",FALSE);
				$message = substr($message,1);
				if ($type == "ERROR")
				{
					echo "<div class='errorM'>$message</div>";
				}
				else if ($type == "RESULT")
				{
					echo "<div class='resultM'>$message</div>";
				}
				else{}
			}
		}
		echo "</div>";
	}

	//Used to verify that consultant is logged in and what their status is
	//Returns 0 if invalid/not logged in.  Returns 1 
	function verifySessions()
	{
		global $userName;
		global $userType;
		global $connection;
		if (isNullOrEmptyString($userName) || isNullOrEmptyString($userType))
		{
			return 0;
		}
		else
		{
			if ($stmt = $connection->prepare("SELECT consultantType FROM consultants WHERE consultantUserName = ?"))
			{	
				$stmt->bind_param("s",$userName);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($consultantType);
				while ($stmt->fetch())
				{
					if ($consultantType == $userType)
					{
						return $userType;
					}
					else
					{
						return 0;
					}
				}
			}
			else
			{
				return 0;
			}
		}
	}
	
	//Looks up to see if there is already a previous sheet with a ticket number and creates a instanceID
	//Due to how the first instance of a ticket will have an ID of 0, the number pulled that match become the new instanceID
	//EG: If ticket 1234421 has 2 sheets made in its name already, the new instanceID will be 2
	function determineInstance()
	{
		global $connection;
		global $instanceID;
		if ($stmt = $connection->prepare("SELECT COUNT(instanceID) as newInstance FROM sheets WHERE ticketNo = ?"))
		{	
			$stmt->bind_param("i",$_POST['ticketNo']);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($newInstance);
			while ($stmt->fetch())
			{
				$instanceID = $newInstance;
			}
		}
	}
	
	//Used to validate staff passcodes against the database
	function validateStaff($userpass)
	{
		global $connection;
		$result = "No Match";
		if ($stmt = $connection->prepare("SELECT consultantUserName,consultantPasscode FROM consultants "))
		{	
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($staffUserName,$passHash);
			while ($stmt->fetch())
			{
				if (password_verify($userpass,$passHash))
				{
					$result = $staffUserName;
				}
			}
		}
		else
		{
			$result = "No Match";
		}
		return $result;
	}

	//Displays 10 most recent tickets
	function displayRecent($type)
	{
		global $connection;
		global $messages;
		global $userName;
		$whereTrigger = TRUE;
		$andTrigger = FALSE;
		if ($type == 1)
		{
			$query = "SELECT ticketNo,instanceID,customerFirstName,customerLastName,dateDropOff,datePickup,customerUserName,computerMake,computerModel,cssdDriveOut FROM sheets WHERE consultantDropOff = ? OR consultantPickUp = ? OR staffDropOff = ? OR staffPickUp = ? ORDER BY dateDropOff DESC LIMIT 5";
			echo "<div class='subhead'>Personal Recent Tickets</div>";
		}
		else
		{
			$query = "SELECT ticketNo,instanceID,customerFirstName,customerLastName,dateDropOff,datePickup,customerUserName,computerMake,computerModel,cssdDriveOut FROM sheets ORDER BY dateDropOff DESC LIMIT 5";
			echo "<div class='subhead'>Recent Tickets</div>";
		}
		if ($stmt = $connection->prepare($query))
		{
			if ($type == 1)
			{
				$stmt->bind_param("ssss",$userName,$userName,$userName,$userName);
			}
			$stmt->execute();
			$stmt->bind_result($ticketNo,$instanceID,$customerFirstName,$customerLastName,$dateDropOff,$datePickup,$customerUserName,$computerMake,$computerModel,$cssdDriveOut);
			while ($stmt->fetch())
			{
				if ($cssdDriveOut == 1)
				{
					echo "<div class='searchResultsDO'>";
				}
				else
				{
					echo "<div class='searchResults'>";
				}
				if ($instanceID > 0)
				{
					$ticketDisp = $ticketNo."-".$instanceID;
					echo "<div class='ticketLink'><a href='index.php?menu=searchT&ticket=$ticketNo&instance=$instanceID'>$ticketDisp</a></div>";
				}
				else
				{
					echo "<div class='ticketLink'><a href='index.php?menu=searchT&ticket=$ticketNo'>$ticketNo</a></div>";
				}
				echo "<div class='searchInfo'><div class='name'>$customerFirstName $customerLastName</div><div class='auxInfo'><em>$computerMake $computerModel</em></div><br><div class='date'>";
				if (isNullOrEmptyString($datePickup))
				{
					echo "$dateDropOff - ACTIVE";
				}
				else
				{
					echo "$dateDropOff - $datePickup";
				}
				echo "</div></div></div>";
			}
		}
		echo "<br>";
	}
	
	// INCLUDES-----------------------------------------------------------------------------
	require 'display.php';		//Display Functions
	require 'action.php';		//Form-Action Functions
	require 'admin.php';		//Admin Functions
	
	// PAGE RUN-----------------------------------------------------------------------------
	displayHead();
	if ($fAction == "Submit Sheet")
	{
		displayLegal();
		submitFormInfo();
		if ($break == FALSE)
		{
			editForm($_POST["ticketNo"]);
		}
		else
		{
			displayForm();
		}
	}
	else if ($fAction == "Update Sheet")
	{
		displayLegal();
		editFormInfo();
		editForm($_POST["ticketNo"]);
	}
	else if ($fAction == "Delete Sheet")
	{
		if (verifySessions() == 2)
		{
			displayLegal();
			deleteFormInfo();
			displayForm();
		}
		else
		{
			displayLegal();
			editForm($_POST["ticketNo"]);
		}
	}
	else if ($fAction == "search")
	{
		searchForm();
	}
	else if ($fAction == "Search Sheets")
	{
		if (empty($_POST["startDate"]))
		{
			$_POST["startDate"] = "IGNORE";
		}
		if (empty($_POST["endDate"]))
		{
			$_POST["endDate"] = "IGNORE";
		}
		searchFormInfo();
	}
	else if ($fAction == "searchT")
	{
		if (!empty($_GET["ticket"]))
		{
			displayLegal();
			editForm($_GET["ticket"]);
		}
		else
		{
			searchForm();
		}
	}
	else if ($fAction == "create")
	{
		displayLegal();
		displayForm();
	}
	else if ($fAction == "login")
	{
		if (verifySessions() == 0)
		{
			loginForm();
		}
		else
		{
			$defaultDisplay = TRUE;
		}
	}
	else if ($fAction == "Log in")
	{
		if (verifySessions() == 0)
		{
			if (login())
			{
				$messages .= "RESULT:You have logged in successfully::";
				$loginSuccess = TRUE;
				$defaultDisplay = TRUE;
			}
			else
			{
				$messages .= "ERROR:Login Invalid. Try Again::";
				loginForm();
			}
		}
	}
	else if ($fAction == "logout")
	{
		if (verifySessions() != 0)
		{
			logout();
		}
		$defaultDisplay = TRUE;
	}
	else if ($fAction == "password")
	{
		if (verifySessions() != 0)
		{
			resetPasscode();
		}
		else
		{
			$defaultDisplay = TRUE;
		}
	}
	else if ($fAction == "Reset")
	{
		if (verifySessions() != 0)
		{
			setPasscode();
		}
		resetPasscode();
	}
	else
	{
		$defaultDisplay = TRUE;
	}
	
	if ($defaultDisplay == TRUE)
	{
		if (verifySessions() != 0)
		{
			displayRecent(1);
		}
		displayRecent(0);
	}
	displayMessages();
	displayFooter();
?>
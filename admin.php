<?php
	session_start();
	set_error_handler("messageError");
	// DEV NOTES---------------------------------------------------------------------------
	//	created by: 	Vincent Agresti
	//	program name:	Check-In Program Admin
	//	short summary:	Admin Panel for maintenance and monitoring of Check-In Program
	//
	// GLOBALS-----------------------------------------------------------------------------
	$connection = new mysqli("localhost","root","","checkout");
	if ($connection->connect_error)
	{
		trigger_error('Database connection failed: '.$connection->connect_error,E_USER_ERROR);
	}
	$messages = "";
	
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
		//Verify GETs
	if (!empty($_GET["menu"]))
	{
		$fAction = $_GET["menu"];
	}
	
	// FUNCTIONS---------------------------------------------------------------------------
	//Display HTML Header
	function displayHead()
	{
		echo "<!DOCTYPE html>
				<head>
					<meta http-equiv='Content-type' content='text/html;charset=UTF-8' />
					<title>Service Drop Off Admin</title>
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
	
	//Displays HTML Footer
	function displayFooter()				
	{
		echo "</div>
		<footer>
			<div class='footLinks'>
				<a href='index.php'>[home]</a> <a href='index.php?menu=create'>[create]</a> <a href='index.php?menu=search'>[search]</a> 
			</div>
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
	function displayMessages()
	{
		global $messages;
		echo "<div class='messages'>";
		$continue = TRUE;
		while ($continue == TRUE)
		{
			$end = strpos($messages, "::");
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
	
	//Makes the drop-down form selection boxes for the Search Form
	function generateDropDowns($type)	
	{
		global $connection;
		if ($type = "consultant")
		{
			$query = "SELECT consultantUserName AS code, CONCAT_WS(', ', consultantLastName, consultantFirstName) AS name FROM consultants WHERE consultantActive = 1 ORDER BY consultantLastName ASC ";
		}
		else{}
		
		$dropDownExport = "<select name='$type'>";
		if ($stmtDD = $connection->prepare($query))
		{
			$stmtDD ->execute();
			$stmtDD ->store_result();
			$stmtDD ->bind_result($code,$name);
			while ($stmtDD ->fetch())
			{
				$dropDownExport .= "<option value='$code'>$name</option>\n";
			}
		}
		$dropDownExport .= "</select>\n";
		return $dropDownExport;
	}	
	
	//Form used to set passcodes for the first time
	function registerPasscode()
	{
		echo "<form action='admin.php' id='submitForm' method='post' autocomplete='off'>".generateDropDowns("consultant")."<input type='text' name='password' value='Password' onfocus='clearThis(this)'><input type='submit' name='action' value='Submit'/><input type='submit' name='action' value='Test'/><input type='submit' name='action' value='Validate'/></form>";
	}
	
	//Takes results from form and sets the passcode in the database if it hasn't been set already
	function setPasscode()
	{
		global $connection;
		$hash = password_hash($_POST["password"],PASSWORD_DEFAULT);
		echo $hash;
		if ($stmt = $connection->prepare("UPDATE consultants SET consultantPasscode = ? WHERE consultantUserName = ?"))
		{
			$stmt->bind_param("ss",$hash,$_POST["consultant"]);
			$stmt->execute();
			$stmt->store_result();
		}
	}
	
	function testPasscode()
	{
		global $connection;
		if ($stmt = $connection->prepare("SELECT consultantPasscode FROM consultants WHERE consultantUserName = ?"))
		{
			$stmt->bind_param("s",$_POST["consultant"]);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($recievedPassword);
			while ($stmt->fetch())
			{
				if (password_verify($_POST["password"],$recievedPassword))
				{
					echo "Match!";
				}
			}
		}
	}

	
	// PAGE RUN-----------------------------------------------------------------------------
	displayHead();
	
	registerPasscode();
	if ($fAction == "Submit")
	{
		setPasscode();
	}
	if ($fAction == "Test")
	{
		testPasscode();
	}
	if ($fAction == "Validate")
	{
		echo validateStaff($_POST["password"]);
	}
	displayMessages();
	displayFooter();
?>
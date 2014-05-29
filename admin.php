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
		$fAction =	$_GET["menu"];
	}
	$displayMain = FALSE;
	
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
	
	function loginForm()
	{
		echo "<form action='admin.php' id='submitForm' method='post' autocomplete='off'><table>
		<tr><td class='input'>Username:</td><td class='input'><input type='text' name='username' onfocus='clearThis(this)'></td></tr>
		<tr><td class='input'>Password:</td><td class='input'><input type='password' name='password' onfocus='clearThis(this)'></td></tr>
		<tr><td class='input' colspan='2'><input type='submit' name='action' value='Login'/></td></tr></table></form>";
	}
	
	function login()
	{
		global $connection;
		$match = FALSE;
		if ($stmt = $connection->prepare("SELECT consultantUserName,consultantPasscode,consultantType FROM consultants WHERE consultantUserName = ?"))
		{	
			$stmt->bind_param("s",$_POST["username"]);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($consultantUserName,$consultantPasscode,$consultantType);
			while ($stmt->fetch())
			{
				if (password_verify($_POST["password"],$consultantPasscode))
				{
					$match = TRUE;
					$_SESSION["USER"] = $consultantUserName;
					$_SESSION["TYPE"] = $consultantType;
				}
			}
		}
		return $match;
	}
	
	function logout()
	{
		global $userName;
		global $userType;
		global $messages;
		unset ($_SESSION["USER"]);
		unset ($_SESSION["TYPE"]);
		$userName = "";
		$userType = "";
		$messages .= "RESULT:You have been logged out successfully::";
	}
	
	// PAGE RUN-----------------------------------------------------------------------------
	displayHead();
		//Try to log in or logout
	if (isNullOrEmptyString($userName) && $fAction != "Login"){}
	else if (isNullOrEmptyString($userName) && $fAction == "Login")
	{
		if (login())
		{
			$messages .= "RESULT:You have logged in successfully::";
			$displayMain = TRUE;
		}
		else
		{
			$messages .= "ERROR:Login Invalid. Try Again::";
		}
	}
	else if (!isNullOrEmptyString($userName) && $fAction == "Logout")
	{
		$logout();
	}
	else
	{
		$displayMain = TRUE;
	}
		//Display main admin control panel
	if ($displayMain == TRUE)
	{
		registerPasscode();
		if ($fAction == "Submit")
		{
			setPasscode();
		}
	}
	else
	{
		loginForm();
	}
	displayMessages();
	displayFooter();
?>
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
	
	//Displays legal info above form
	function displayLegal()
	{
		echo"<div class='terms'>
		<p>I agree to the terms and conditions below and grant permission for Computing Services and Systems Development (CSSD) staff to examine my computer and recommend a solution for the problems that I have reported to them.</p> 
		<p>CSSD provides assistance with software problems including drivers, removal of computer viruses and spyware, and related troubleshooting.  The following are our terms and conditions of service: </p>
		<ul>
			<li>All services are performed on a 'best effort' basis.  We do not guarantee that we will be able to resolve your problem or answer all of your questions. We may need to refer you to the hardware manufacturer or software publisher's technical support department for more detailed assistance.</li>
			<li>We do not repair computer hardware, but we can help you identify a local qualified service provider, if necessary.</li>
			<li>In order to install/reinstall software, we require you to have proof of license for any and all software that you would like us to install.  Proof of license may include the original media, a proof of license sticker placed on the computer by the manufacturer, or an original sales receipt.</li>
			<li>We provide networking assistance for connections to PittNet only.</li>
			<li>We can only provide service for a computer that you personally own or that has been assigned by the University for your use.</li>
			<li>The nature of your computer problem may require that we reformat your computer and reinstall Windows (or the operating system you use) along with your application software.  Before we do this, we will ask you to verify that you have backed up your personal files and settings.  We will create a temporary backup, but we cannot guarantee that we can restore all of your files. The University of Pittsburgh and Computing Services and Systems Development are not responsible for the loss of data, programs, or other files installed on your computer.</li>
		</ul>
	</div>";
	}
	
	//Creates a blank check-in form
	function displayForm()
	{
		echo"<form action='index.php' id='submitForm' method='post' autocomplete='off'>
		<div class='section1'>
			<div class='subhead'>Drop Off</div>
			<table><tr><td class='input'>Customer Name: </td><td class='input'><input type='text' name='customerFirstName' value='First'  onfocus='clearThis(this)'></td><td class='input'><input type='text' name='customerLastName' value='Last' onfocus='clearThis(this)'></td><td class='input'>Signature: </td><td class='input'><input type='text' name='customerDropOff'></td></tr>
			<tr><td class='input'>Consultant: </td><td class='input'><input type='password' name='consultantDropOff'></td><td class='input'></td><td class='input'>Staff: </td><td class='input'><input type='password' name='staffDropOff'></td></tr>
			<tr><td colspan='5' class='text'>In the situation I am unable to pick up my computer, I give permission for the following to pick up my computer:</td></tr>
			<tr><td class='input'>Customer Name: </td><td class='input'><input type='text' name='altFirstName' value='First' onfocus='clearThis(this)'></td><td class='input'><input type='text' name='altLastName' value='Last' onfocus='clearThis(this)'></td><td>Username:</td><td class='input'><input type='text' name='altUserName'></td></tr></table></div>
			<div class='section3'>
			<div class='subhead'>Departmental Information</div>
			<table><tr><td class='input'>Username: </td><td><input type='text' name='customerUserName'></td><td class='input'></td><td class='input'>Ticket Number: </td><td class='input'><input type='text' name='ticketNo'></td></tr>
			<tr><td class='input'>Computer: </td><td class='input'><input type='text' name='computerMake' value='Make' onfocus='clearThis(this)'></td><td class='input'><input type='text' name='computerModel' value='Model' onfocus='clearThis(this)'></td><td class='input'>Serial Number: </td><td class='input'><input type='text' name='computerSerialNum'></td></tr></table><br/>
			<table><tr><td class='input'>Cable Quantity: </td><td class='input'><input type='text' name='powerCableQuantity' value='0'  onfocus='clearThis(this)'></td><td class='input'>Description: </td><td class='input'><input type='text' name='powerCableDesc'></td></tr>
			<tr><td class='input'>Drive/Discs Quantity: </td><td class='input'><input type='text' name='mediaQuantity' value='0'  onfocus='clearThis(this)'></td><td class='input'>Description: </td><td class='input'><input type='text' name='mediaDesc'></td></tr>
			<tr><td class='input'>Other Quantity: </td><td class='input'><input type='text' name='otherQuantity' value='0'  onfocus='clearThis(this)'></td><td class='input'>Description: </td><td class='input'><input type='text' name='otherDesc'></td></tr>
			<tr><td colspan='2'>Is the computer under warranty? </td><td colspan='2'><input type='radio' name='warrantyStatus' value='2'>Yes <input type='radio' name='warrantyStatus' value='1'>No <input type='radio' name='warrantyStatus' value='0' checked>Not Sure</td></tr>
			<tr><td colspan='2'>Is the data backed up on CSSD data drive?</td><td colspan='2'><input type='radio' name='cssdDriveOut' value='1'>Yes <input type='radio' name='cssdDriveOut' value='0' checked>No</td></tr></table><br/>
			<table><tr><td>Top Condition</td><td>Bottom Condition</td><td>Screen Condition</td><td>Keyboard Condition</td></tr>
			<tr><td><textarea rows='3' cols='30' name='topCondition'></textarea></td><td><textarea rows='3' cols='30' name='bottomCondition'></textarea></td><td><textarea rows='3' cols='30' name='screenCondition'></textarea></td><td><textarea rows='3' cols='30' name='keyboardCondition'></textarea></td></tr></table>
		</div>
		<div class='action'>
			<input type='submit' name='action' value='Submit Sheet'/>
		</div>
	</form>";
	}
	
	//Creates new entry in check-in database
	function submitFormInfo()
	{
		global $messages;
		global $rightNow;
		global $connection;
		global $break;
		global $instanceID;
			// Verifies all of Drop Off Section is filled out
		if (isNullOrEmptyString($_POST["customerFirstName"]) || $_POST["customerFirstName"] == "First" || isNullOrEmptyString($_POST["customerLastName"]) || $_POST["customerLastName"] == "Last" || isNullOrEmptyString($_POST["customerDropOff"]) || $_POST["consultantDropOff"] == "IGNORE" || isNullOrEmptyString($_POST["staffDropOff"]))
		{
			$messages .= "ERROR:Please have all required info in 'Drop Off'::";
			$break = TRUE;
		}
		else
		{	
			//If section is filled out, validate both signature passcodes
			$consultantDropOff = validateStaff($_POST['consultantDropOff']);
			$staffDropOff = validateStaff($_POST['staffDropOff']);
			if ($consultantDropOff == "No Match" || $staffDropOff == "No Match")
			{
				$messages .= "ERROR:Please enter the correct password in consultant and staff fields::";
				$break = TRUE;
			}
		}
			// Verifies all of required Departmental Information is filled out
		if (isNullOrEmptyString($_POST["customerUserName"]) || isNullOrEmptyString($_POST["ticketNo"]) || isNullOrEmptyString($_POST["computerMake"]) || $_POST["computerMake"] == "Make" || $_POST["computerModel"] == "Model" || isNullOrEmptyString($_POST["computerModel"]) || isNullOrEmptyString($_POST["computerSerialNum"]))
		{
			$messages .= "ERROR:Please have all required info in 'Departmental Information'::";
			$break = TRUE;
		}
		if ($_POST["altFirstName"] == "First")
		{
			$_POST["altFirstName"] = NULL;
		}
		if ($_POST["altLastName"] == "Last")
		{
			$_POST["altLastName"] = NULL;
		}
			// Verifies that either all of or none of Alternative Pick-Up is filled out
		if (!((!(isNullOrEmptyString($_POST["altUserName"]) || isNullOrEmptyString($_POST["altFirstName"]) || isNullOrEmptyString($_POST["altLastName"]))) || (isNullOrEmptyString($_POST["altUserName"]) && isNullOrEmptyString($_POST["altFirstName"]) && isNullOrEmptyString($_POST["altLastName"]))))
		{
			$messages .= "ERROR:For Alternate Pick, make sure it is either empty or completely filled out::";
			$break = TRUE;
		}

		if ($break == FALSE)
		{
			determineInstance();
			//Removes all code, tags, inject characters from input.  If empty, sets to NULL
			foreach($_POST as $key=>$value)
			{
				$_POST[$key] = trim(preg_replace('/ +/', ' ', preg_replace('/[^A-Za-z0-9 ]/', ' ', urldecode(html_entity_decode(strip_tags($value))))));
				if ($_POST[$key] == "" || $_POST[$key] == " ")
				{
					$_POST[$key] = NULL;
				}
			}
			$query = "INSERT INTO sheets (ticketNo,instanceID,customerFirstName,customerLastName,customerDropOff,dateDropOff,consultantDropOff,staffDropOff,customerUserName,computerMake,computerModel,computerSerialNum,powerCableQuantity,powerCableDesc,mediaQuantity,mediaDesc,otherQuantity,otherDesc,warrantyStatus,cssdDriveOut,altUserName,altFirstName,altLastName,topCondition,bottomCondition,screenCondition,keyboardCondition) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
			$bindString = "iissssssssssisisisiisssssss";
			$stmt = $connection->prepare($query);
			$stmt->bind_param($bindString,$_POST['ticketNo'],$instanceID,$_POST['customerFirstName'],$_POST['customerLastName'],$_POST['customerDropOff'],$rightNow,$consultantDropOff,$staffDropOff,$_POST['customerUserName'],$_POST['computerMake'],$_POST['computerModel'],$_POST['computerSerialNum'],$_POST['powerCableQuantity'],$_POST['powerCableDesc'],$_POST['mediaQuantity'],$_POST['mediaDesc'],$_POST['otherQuantity'],$_POST['otherDesc'],$_POST['warrantyStatus'],$_POST['cssdDriveOut'],$_POST['altUserName'],$_POST['altFirstName'],$_POST['altLastName'],$_POST['topCondition'],$_POST['bottomCondition'],$_POST['screenCondition'],$_POST['keyboardCondition']);
			$stmt->execute();
			$stmt->store_result();
		}
	}
	
	//Displays a selected check-in sheet based off ticket number
	function editForm($ticket)
	{
		global $connection;
		global $instanceID;
			//If $instanceID is set from Post or Get variables, then use different query
		if (isNullOrEmptyString($instanceID))
		{
			$query = "SELECT ticketNo,customerFirstName,customerLastName,customerFirstNamePickUp,customerLastNamePickUp,customerDropOff,customerPickUp,consultantDropOff,consultantPickUp,staffDropOff,staffPickUp,customerUserName,computerMake,computerModel,computerSerialNum,powerCableQuantity,powerCableDesc,mediaQuantity,mediaDesc,otherQuantity,otherDesc,warrantyStatus,cssdDriveOut,altUserName,altFirstName,altLastName,topCondition,bottomCondition,screenCondition,keyboardCondition FROM sheets WHERE ticketNo = ? AND instanceID = '0'";
		}
		else
		{
			$query = "SELECT ticketNo,customerFirstName,customerLastName,customerFirstNamePickUp,customerLastNamePickUp,customerDropOff,customerPickUp,consultantDropOff,consultantPickUp,staffDropOff,staffPickUp,customerUserName,computerMake,computerModel,computerSerialNum,powerCableQuantity,powerCableDesc,mediaQuantity,mediaDesc,otherQuantity,otherDesc,warrantyStatus,cssdDriveOut,altUserName,altFirstName,altLastName,topCondition,bottomCondition,screenCondition,keyboardCondition FROM sheets WHERE ticketNo = ? AND instanceID = ?";
		}
		if ($stmt = $connection->prepare($query))
		{
			if (isNullOrEmptyString($instanceID))
			{
				$stmt->bind_param("i",$ticket);
			}
			else
			{
				$stmt->bind_param("ii",$ticket,$instanceID);
			}
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($ticketNo,$customerFirstName,$customerLastName,$customerFirstNamePickUp,$customerLastNamePickUp,$customerDropOff,$customerPickUp,$consultantDropOff,$consultantPickUp,$staffDropOff,$staffPickUp,$customerUserName,$computerMake,$computerModel,$computerSerialNum,$powerCableQuantity,$powerCableDesc,$mediaQuantity,$mediaDesc,$otherQuantity,$otherDesc,$warrantyStatus,$cssdDriveOut,$altUserName,$altFirstName,$altLastName,$topCondition,$bottomCondition,$screenCondition,$keyboardCondition);
			while ($stmt->fetch())
			{
				echo"<form action='index.php' id='submitForm' method='post' autocomplete='off'>
				<div class='section1'>
					<div class='subhead'>Drop Off</div>
					<table><tr><td class='input'>Customer Name: </td><td class='input'><input type='text' value='$customerFirstName' disabled></td><td class='input'><input type='text' value='$customerLastName' disabled></td><td class='input'>Signature: </td><td class='input'><input type='text' value='$customerDropOff' disabled></td></tr>
					<tr><td class='input'>Consultant: </td><td class='input'><input type='text' value='$consultantDropOff' disabled>
				</td><td></td><td class='input'>Staff: </td><td class='input'><input type='text' value='$staffDropOff' disabled></td></tr>
					<tr><td colspan='5' class='text'>In the situation I am unable to pick up my computer, I give permission for the following to pick up my computer:</td></tr>
					<tr><td class='input'>Customer Name: </td><td class='input'><input type='text' value='$altFirstName' disabled></td><td class='input'><input type='text' value='$altLastName' disabled></td><td>Username:</td><td class='input'><input type='text' value='$altUserName' disabled></td></tr></table>
				</div>";
				//Determind if Section 2 is read-only or editable based off staff and consultant signatures
				if (isNullOrEmptyString($consultantPickUp) || isNullOrEmptyString($staffPickUp))
				{
					echo"<div class='section2'><input type='hidden' name='closedForm' value='FALSE'>
						<div class='subhead'>Pick Up</div>
						<table><tr><td colspan='5' class='text'>I agree that I received the items left with CSSD in good condition</td></tr>
						<tr><td class='input'>Customer Name: </td><td class='input'><input type='text' name='customerFirstNamePickUp'></td><td class='input'><input type='text' name='customerLastNamePickUp'></td><td class='input'>Signature: </td><td class='input'><input type='text' name='customerPickUp'></td></tr>
						<tr><td class='input'>Consultant: </td><td class='input'><input type='password' name='consultantPickUp'></td><td></td><td class='input'>Staff: </td><td class='input'><input type='password' name='staffPickUp'></td></tr></table>
					</div>";
				}
				else
				{
					echo"<div class='section2'><input type='hidden' name='closedForm' value='TRUE'>
						<div class='subhead'>Pick Up</div>
						<table><tr><td colspan='5' class='text'>I agree that I received the items left with CSSD in good condition</td></tr>
						<tr><td class='input'>Customer Name: </td><td class='input'><input type='text' value='$customerFirstNamePickUp' disabled></td><td class='input'><input type='text' value='$customerLastNamePickUp' disabled></td><td class='input'>Signature: </td><td class='input'><input type='text' value='$customerPickUp' disabled></td></tr>
						<tr><td class='input'>Consultant: </td><td class='input'><input type='text' value='$consultantPickUp' disabled></td><td></td><td class='input'>Staff: </td><td class='input'><input type='text' value='$staffPickUp' disabled></td></tr></table>
					</div>";
				}
				echo"<div class='section3'>
					<div class='subhead'>Departmental Information</div>
					<table><tr><td class='input'>Username: </td><td><input type='text' value='$customerUserName' disabled></td><td class='input'></td><td class='input'>Ticket Number: </td><td class='input'><input type='text' value='$ticketNo' disabled><input type='hidden' name='ticketNo' value='$ticketNo'><input type='hidden' name='instanceID' value='$instanceID'></td></tr>
					<tr><td class='input'>Computer: </td><td class='input'><input type='text' value='$computerMake' disabled></td><td class='input'><input type='text' value='$computerModel' disabled></td><td class='input'>Serial Number: </td><td class='input'><input type='text' value='$computerSerialNum' disabled></td></tr></table><br/>
					<table><tr><td class='input'>Cable Quantity: </td><td class='input'><input type='text' name='powerCableQuantity' value='$powerCableQuantity'></td><td class='input'>Description: </td><td class='input'><input type='text' name='powerCableDesc' value='$powerCableDesc'></td></tr>
					<tr><td class='input'>Drive/Discs Quantity: </td><td class='input'><input type='text' name='mediaQuantity' value='$mediaQuantity'></td><td class='input'>Description: </td><td class='input'><input type='text' name='mediaDesc' value='$mediaDesc'></td></tr>
					<tr><td class='input'>Other Quantity: </td><td class='input'><input type='text' name='otherQuantity' value='$otherQuantity'></td><td class='input'>Description: </td><td class='input'><input type='text' name='otherDesc'  value='$otherDesc'></td></tr>
					<tr><td colspan='2'>Is the computer under warranty? </td>";
					if ($warrantyStatus == 2)
					{
						echo"<td colspan='2'><input type='radio' name='warrantyStatus' value='2' checked>Yes <input type='radio' name='warrantyStatus' value='1'>No <input type='radio' name='warrantyStatus' value='0'>Not Sure</td></tr>";
					}
					else if ($warrantyStatus == 1)
					{
						echo"<td colspan='2'><input type='radio' name='warrantyStatus' value='2'>Yes <input type='radio' name='warrantyStatus' value='1' checked>No <input type='radio' name='warrantyStatus' value='0'>Not Sure</td></tr>";
					}
					else
					{
						echo"<td colspan='2'><input type='radio' name='warrantyStatus' value='2'>Yes <input type='radio' name='warrantyStatus' value='1'>No <input type='radio' name='warrantyStatus' value='0' checked>Not Sure</td></tr>";
					}
					echo"<tr><td colspan='2'>Is the data backed up on CSSD data drive?</td>";
					if ($cssdDriveOut == 1)
					{
						echo "<td colspan='2'><input type='radio' name='cssdDriveOut' value='1' checked>Yes <input type='radio' name='cssdDriveOut' value='0'>No</td></tr></table><br/>";
					}
					else
					{
						echo "<td colspan='2'><input type='radio' name='cssdDriveOut' value='1'>Yes <input type='radio' name='cssdDriveOut' value='0' checked>No</td></tr></table><br/>";
					}
					echo"<table><tr><td>Top Condition</td><td>Bottom Condition</td><td>Screen Condition</td><td>Keyboard Condition</td></tr>
					<tr><td><textarea rows='3' cols='30' name='topCondition'>$topCondition</textarea></td><td><textarea rows='3' cols='30' name='bottomCondition'>$bottomCondition</textarea></td><td><textarea rows='3' cols='30' name='screenCondition'>$screenCondition</textarea></td><td><textarea rows='3' cols='30' name='keyboardCondition'>$keyboardCondition</textarea></td></tr></table>
				</div>
				<div class='action'>
					<input type='submit' name='action' value='Update Sheet'/><input type='submit' name='action' value='Delete Sheet'/>
				</div>
				</form>";
			}
		}
	}
	
	//Used to update check-in sheet info when edited
	function editFormInfo()
	{
		global $messages;
		global $connection;
		global $break;
		global $rightNow;
		global $instanceID;
		$break = FALSE;

			// If not ready for Pick-Up, does not allow for pick-up date to be filled
		if ($_POST["closedForm"] == FALSE)
		{
			if (isNullOrEmptyString($_POST["customerFirstNamePickUp"]) || isNullOrEmptyString($_POST["customerLastNamePickUp"]) || isNullOrEmptyString($_POST["customerPickUp"]) || $_POST["consultantPickUp"] == "IGNORE" || isNullOrEmptyString($_POST["staffPickUp"]))
			{
				if ($_POST["consultantPickUp"] == "IGNORE")
				{
					$_POST["consultantPickUp"] = NULL;
				}
				$rightNow = NULL;
			}
			else
			{
					//If section is filled out, validate both signature passcodes
				$_POST["consultantPickUp"] = validateStaff($_POST['consultantPickUp']);
				$_POST["staffPickUp"] = validateStaff($_POST['staffPickUp']);
				if ($_POST["consultantPickUp"] == "No Match" || $_POST["staffPickUp"] == "No Match")
				{
					$messages .= "ERROR:Please enter the correct password in consultant and staff fields::";
					$break = TRUE;
					if ($_POST["consultantPickUp"] == "IGNORE")
					{
						$_POST["consultantPickUp"] = NULL;
					}
					$rightNow = NULL;
				}
			}
		}
		if ($break == FALSE)
		{
			//Removes all code/tags/inject characters from input.  If empty, sets to NULL
			foreach($_POST as $key=>$value)
			{
				$_POST[$key] = trim(preg_replace('/ +/', ' ', preg_replace('/[^A-Za-z0-9 ]/', ' ', urldecode(html_entity_decode(strip_tags($value))))));
				if ($_POST[$key] == "" || $_POST[$key] == " ")
				{
					$_POST[$key] = NULL;
				}
			}
			if ($_POST["closedForm"] == FALSE)
			{
				echo "Open Form";
				$query = "UPDATE sheets SET datePickUp = ?, powerCableQuantity = ?, powerCableDesc = ?, mediaQuantity = ?, mediaDesc = ?, otherQuantity = ?, otherDesc = ?, warrantyStatus = ?, cssdDriveOut = ?, altUserName = ?, bottomCondition = ?, screenCondition = ?, keyboardCondition = ?, customerFirstNamePickUp = ?,customerLastNamePickUp = ?, customerPickUp = ?, consultantPickUp = ?, staffPickUp = ? WHERE ticketNo = ? AND instanceID = ?";
				$stmt = $connection->prepare($query);
				$stmt->bind_param("sisisisiisssssssssii",$rightNow,$_POST['powerCableQuantity'],$_POST['powerCableDesc'],$_POST['mediaQuantity'],$_POST['mediaDesc'],$_POST['otherQuantity'],$_POST['otherDesc'],$_POST['warrantyStatus'],$_POST['cssdDriveOut'],$_POST['topCondition'],$_POST['bottomCondition'],$_POST['screenCondition'],$_POST['keyboardCondition'],$_POST['customerFirstNamePickUp'],$_POST['customerLastNamePickUp'],$_POST['customerPickUp'],$_POST['consultantPickUp'],$_POST['staffPickUp'],$_POST['ticketNo'],$instanceID);
			}
			else
			{
				echo "Closed Form";
				$query = "UPDATE sheets SET powerCableQuantity = ?, powerCableDesc = ?, mediaQuantity = ?, mediaDesc = ?, otherQuantity = ?, otherDesc = ?, warrantyStatus = ?, cssdDriveOut = ?, topCondition = ?, bottomCondition = ?, screenCondition = ?, keyboardCondition = ? WHERE ticketNo = ? AND instanceID = ?";
				$stmt = $connection->prepare($query);
				$stmt->bind_param("isisisiissssii",$_POST['powerCableQuantity'],$_POST['powerCableDesc'],$_POST['mediaQuantity'],$_POST['mediaDesc'],$_POST['otherQuantity'],$_POST['otherDesc'],$_POST['warrantyStatus'],$_POST['cssdDriveOut'],$_POST['topCondition'],$_POST['bottomCondition'],$_POST['screenCondition'],$_POST['keyboardCondition'],$_POST['ticketNo'],$instanceID);
			}
			$stmt->execute();
			$stmt->store_result();
		}
	}
	
	//Used to delete check-in sheets
	function deleteFormInfo()
	{
		global $messages;
		global $connection;
		global $instanceID;
			//If $instanceID is set from Post or Get variables, then use different query
		if (isNullOrEmptyString($instanceID))
		{
			if ($stmt = $connection->prepare("DELETE FROM sheets WHERE ticketNo=? AND instanceID='0'"))
			{
				$stmt->bind_param('i',$_POST["ticketNo"]);
				$stmt->execute();
				$messages .= "RESULT:Ticket ".$_POST["ticketNo"]." has been Deleted::";
			}
		}
		else
		{
			if ($stmt = $connection->prepare("DELETE FROM sheets WHERE ticketNo=? AND instanceID=?"))
			{
				$stmt->bind_param('ii',$_POST["ticketNo"],$instanceID);
				$stmt->execute();
				$messages .= "RESULT:Ticket ".$_POST["ticketNo"]."-$instanceID has been Deleted::";
			}
		}
	}
	
	//Displays form for searching check-ins
	function searchForm()
	{
		echo "<div class='subhead'>Search</div>
				<form action='index.php' id='searchForm' method='post'>
				<table><tr><td class='input'>Employee</td><td cclass='input'>";
		echo generateDropDowns("consultant");
		echo "</td></tr>\n
		<tr><td class='input'>Timespan</td><td class='input'><input type='date' name='startDate'></td><td class='input'><input type='date' name='endDate'></td></td></tr>\n
		<tr><td class='input'>Ticket Number</td><td class='input'><input type='text' name='ticketNumber'></td></tr>\n
		<tr><td class='input'>Customer Username</td><td class='input'><input type='text' name='customerUserName'></td></tr>\n
		<tr><td colspan='2' class='input'><input type='submit' name='action' value='Search Sheets'/></td></tr></table></form>\n";
	}

	//Displays all matching check-ins to a search quota
	function searchFormInfo()
	{
		global $connection;
		global $messages;
		$whereTrigger = TRUE;
		$andTrigger = FALSE;
		$tickets = 0;
		$bindString = "";
		$usedVariables = array();
		$query = "SELECT ticketNo,instanceID,customerFirstName,customerLastName,dateDropOff,datePickup,customerUserName,computerMake,computerModel,cssdDriveOut FROM sheets";
		//Sets up SQL Statement based on what variables are filled out in search form
		if ($_POST["endDate"] == "IGNORE" && $_POST["startDate"] != "IGNORE")
		{
			if ($whereTrigger == TRUE)
			{
				$query .= " WHERE";
				$whereTrigger = FALSE;
			}
			$_POST["startDate"] = $_POST["startDate"]." 00:00:00";
			$usedVariables[] = $_POST["startDate"];
			$query .= " dateDropOff>=?";
			$andTrigger = TRUE;
			$bindString .= "s";
		}
		else if ($_POST["endDate"] != "IGNORE" && $_POST["startDate"] != "IGNORE")
		{
			if ($whereTrigger == TRUE)
			{
				$query .= " WHERE";
				$whereTrigger = FALSE;
			}
			$_POST["startDate"] = $_POST["startDate"]." 00:00:00";
			$_POST["endDate"] = $_POST["endDate"]." 23.59.59";
			$usedVariables[] = $_POST["startDate"];
			$usedVariables[] = $_POST["endDate"];
			$query .= " dateDropOff>=? AND datePickup<=?";
			$andTrigger = TRUE;
			$bindString .= "ss";
		}
		else if ($_POST["endDate"] != "IGNORE" && $_POST["startDate"] == "IGNORE")
		{
			if ($whereTrigger == TRUE)
			{
				$query .= " WHERE";
				$whereTrigger = FALSE;
			}
			$_POST["endDate"] = $_POST["endDate"]." 23.59.59";
			$usedVariables[] = $_POST["endDate"];
			$query .= " datePickup<=?";
			$andTrigger = TRUE;
			$bindString .= "s";
		}
		else{}
		if ($_POST["consultant"] != "IGNORE")
		{
			if ($whereTrigger == TRUE)
			{
				$query .= " WHERE";
				$whereTrigger = FALSE;
			}
			if ($andTrigger == TRUE)
			{
				$query .= " AND";
			}
			$usedVariables[] = $_POST["consultant"];
			$usedVariables[] = $_POST["consultant"];
			$query .=" consultantDropOff=? OR consultantPickUp=?";
			$andTrigger = TRUE;
			$bindString .= "ss";
		}
		if (!(isNullOrEmptyString($_POST["ticketNumber"])))
		{
			if ($whereTrigger == TRUE)
			{
				$query .= " WHERE";
				$whereTrigger = FALSE;
			}
			if ($andTrigger == TRUE)
			{
				$query .= " AND";
			}
			$usedVariables[] = $_POST["ticketNumber"];
			$query .=" ticketNo=?";
			$andTrigger = TRUE;
			$bindString .= "i";
		}
		if (!(isNullOrEmptyString($_POST["customerUserName"])))
		{
			if ($whereTrigger == TRUE)
			{
				$query .= " WHERE";
				$whereTrigger = FALSE;
			}
			if ($andTrigger == TRUE)
			{
				$query .= " AND";
			}
			$usedVariables[] = $_POST["customerUserName"];
			$query .=" customerUserName=?";
			$andTrigger = TRUE;
			$bindString .= "s";
		}
		$query .= " ORDER BY ticketNo DESC";
		echo "<div class='subhead'>Results</div>";
		if ($stmt = $connection->prepare($query))
		{
			//Based on how many variables are used in search, bind the parameters correctly
			if (count($usedVariables) == 6)
			{
				$stmt->bind_param($bindString,$usedVariables[0],$usedVariables[1],$usedVariables[2],$usedVariables[3],$usedVariables[4],$usedVariables[5]);
			}
			else if (count($usedVariables) == 5)
			{
				$stmt->bind_param($bindString,$usedVariables[0],$usedVariables[1],$usedVariables[2],$usedVariables[3],$usedVariables[4]);
			}
			else if (count($usedVariables) == 4)
			{
				$stmt->bind_param($bindString,$usedVariables[0],$usedVariables[1],$usedVariables[2],$usedVariables[3]);
			}
			else if (count($usedVariables) == 3)
			{
				$stmt->bind_param($bindString,$usedVariables[0],$usedVariables[1],$usedVariables[2]);
			}
			else if (count($usedVariables) == 2)
			{
				$stmt->bind_param($bindString,$usedVariables[0],$usedVariables[1]);
			}
			else if (count($usedVariables) == 1)
			{
				$stmt->bind_param($bindString,$usedVariables[0]);
			}
			else{}
			$stmt->execute();
			$stmt->store_result();
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
				$tickets++;
			}
			if ($tickets == 0)
			{
				$messages .= "RESULT:No Results Found::";
			}
		}
	}
	
	//Displays 10 most recent tickets
	function displayRecent()
	{
		global $connection;
		global $messages;
		$whereTrigger = TRUE;
		$andTrigger = FALSE;
		$query = "SELECT ticketNo,instanceID,customerFirstName,customerLastName,dateDropOff,datePickup,customerUserName,computerMake,computerModel,cssdDriveOut FROM sheets ORDER BY dateDropOff DESC LIMIT 10";
		echo "<div class='subhead'>Recent Tickets</div>";
		if ($stmt = $connection->prepare($query))
		{
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
	}
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
		displayLegal();
		deleteFormInfo();
		displayForm();
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
	else
	{
		displayRecent();
	}
	displayMessages();
	displayFooter();
?>
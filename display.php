<?php
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
		global $pageName;
		echo"<form action='$pageName' id='submitForm' method='post' autocomplete='off'>
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
	
	
	//Displays a selected check-in sheet based off ticket number
	function editForm($ticket)
	{
		global $connection;
		global $instanceID;
		global $pageName;
		global $userName;
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
				$_POST["consultantDropOff"] = $consultantDropOff;
				$_POST["staffDropOff"] = $staffDropOff;
				if (verifySessions() == 2)
				{
					echo"<form action='$pageName' id='submitForm' method='post' autocomplete='off'>
					<div class='section1'>
						<div class='subhead'>Drop Off</div>
						<table><tr><td class='input'>Customer Name: </td><td class='input'><input type='text' value='$customerFirstName' name='customerFirstName'></td><td class='input'><input type='text' value='$customerLastName' name='customerLastName'></td><td class='input'>Signature: </td><td class='input'><input type='text' value='$customerDropOff' name='customerDropOff'></td></tr>
						<tr><td class='input'>Consultant: </td><td class='input'>".generateDropDowns("consultantDropOff")."
					</td><td></td><td class='input'>Staff: </td><td class='input'>".generateDropDowns("staffDropOff")."</td></tr>
						<tr><td colspan='5' class='text'>In the situation I am unable to pick up my computer, I give permission for the following to pick up my computer:</td></tr>
						<tr><td class='input'>Customer Name: </td><td class='input'><input type='text' value='$altFirstName' name='altFirstName'></td><td class='input'><input type='text' value='$altLastName' name='altLastName'></td><td>Username:</td><td class='input'><input type='text' value='$altUserName' name='altUserName'></td></tr></table>
					</div>";
				}
				else
				{
					echo"<form action='$pageName' id='submitForm' method='post' autocomplete='off'>
					<div class='section1'>
						<div class='subhead'>Drop Off</div>
						<table><tr><td class='input'>Customer Name: </td><td class='input'><input type='text' value='$customerFirstName' disabled></td><td class='input'><input type='text' value='$customerLastName' disabled></td><td class='input'>Signature: </td><td class='input'><input type='text' value='$customerDropOff' disabled></td></tr>
						<tr><td class='input'>Consultant: </td><td class='input'><input type='text' value='$consultantDropOff' disabled>
					</td><td></td><td class='input'>Staff: </td><td class='input'><input type='text' value='$staffDropOff' disabled></td></tr>
						<tr><td colspan='5' class='text'>In the situation I am unable to pick up my computer, I give permission for the following to pick up my computer:</td></tr>
						<tr><td class='input'>Customer Name: </td><td class='input'><input type='text' value='$altFirstName' disabled></td><td class='input'><input type='text' value='$altLastName' disabled></td><td>Username:</td><td class='input'><input type='text' value='$altUserName' disabled></td></tr></table>
					</div>";
				}
				
				//Determind if Section 2 is read-only or editable based off staff and consultant signatures
				if (isNullOrEmptyString($consultantPickUp) || isNullOrEmptyString($staffPickUp))
				{
					echo"<div class='section2'><input type='hidden' name='closedForm' value='FALSE'>
						<div class='subhead'>Pick Up</div>
						<table><tr><td colspan='5' class='text'>I agree that I received the items left with CSSD in good condition</td></tr>
						<tr><td class='input'>Customer Name: </td><td class='input'><input type='text' name='customerFirstNamePickUp'></td><td class='input'><input type='text' name='customerLastNamePickUp'></td><td class='input'>Signature: </td><td class='input'><input type='text' name='customerPickUp'></td></tr><tr>";
						if (verifySessions() == 2)
						{
							$_POST["consultantPickUp"] = $userName;
							$_POST["staffPickUp"] = $userName;
							echo"<td class='input'>Consultant: </td><td class='input'>".generateDropDowns("consultantPickUp")."</td><td></td><td class='input'>Staff: </td><td class='input'>".generateDropDowns("staffPickUp")."</td>";
						}
						else if (verifySessions() == 1)
						{
							echo "<td class='input'>Consultant: </td><td class='input'><input type='text' name='consultantPickUp' value='$userName' disabled><input type='hidden' name='consultantPickUp' value='$userName'></td><td></td><td class='input'>Staff: </td><td class='input'><input type='password' name='staffPickUp'></td>";
						}
						else
						{
							echo "<td class='input'>Consultant: </td><td class='input'><input type='password' name='consultantPickUp'></td><td></td><td class='input'>Staff: </td><td class='input'><input type='password' name='staffPickUp'></td>";
						}
					echo"</tr></table></div>";
				}
				else
				{
					$_POST["consultantPickUp"] = $consultantPickUp;
					$_POST["staffPickUp"] = $staffPickUp;
					if (verifySessions() == 2)
					{
						echo"<div class='section2'><input type='hidden' name='closedForm' value='TRUE'>
							<div class='subhead'>Pick Up</div>
							<table><tr><td colspan='5' class='text'>I agree that I received the items left with CSSD in good condition</td></tr>
							<tr><td class='input'>Customer Name: </td><td class='input'><input type='text' value='$customerFirstNamePickUp' name='customerFirstNamePickUp'></td><td class='input'><input type='text' value='$customerLastNamePickUp' name='customerLastNamePickUp'></td><td class='input'>Signature: </td><td class='input'><input type='text' value='$customerPickUp' name='customerPickUp'></td></tr>
							<tr><td class='input'>Consultant: </td><td class='input'>".generateDropDowns("consultantPickUp")."</td><td></td><td class='input'>Staff: </td><td class='input'>".generateDropDowns("staffPickUp")."</td></tr></table>
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
				}
				if (verifySessions() == 2)
				{
					echo"<div class='section3'>
						<div class='subhead'>Departmental Information</div>
						<table><tr><td class='input'>Username: </td><td><input type='text' value='$customerUserName' name='customerUserName'></td><td class='input'></td><td class='input'>Ticket Number: </td><td class='input'><input type='text' value='$ticketNo' disabled><input type='hidden' name='ticketNo' value='$ticketNo'><input type='hidden' name='instanceID' value='$instanceID'></td></tr>
						<tr><td class='input'>Computer: </td><td class='input'><input type='text' value='$computerMake' name='computerMake'></td><td class='input'><input type='text' value='$computerModel' name='computerModel'></td><td class='input'>Serial Number: </td><td class='input'><input type='text' value='$computerSerialNum' name='computerSerialNum'></td></tr></table><br/>";
				}
				else
				{
					echo"<div class='section3'>
						<div class='subhead'>Departmental Information</div>
						<table><tr><td class='input'>Username: </td><td><input type='text' value='$customerUserName' disabled></td><td class='input'></td><td class='input'>Ticket Number: </td><td class='input'><input type='text' value='$ticketNo' disabled><input type='hidden' name='ticketNo' value='$ticketNo'><input type='hidden' name='instanceID' value='$instanceID'></td></tr>
						<tr><td class='input'>Computer: </td><td class='input'><input type='text' value='$computerMake' disabled></td><td class='input'><input type='text' value='$computerModel' disabled></td><td class='input'>Serial Number: </td><td class='input'><input type='text' value='$computerSerialNum' disabled></td></tr></table><br/>";
				}
				echo"<table><tr><td class='input'>Cable Quantity: </td><td class='input'><input type='text' name='powerCableQuantity' value='$powerCableQuantity'></td><td class='input'>Description: </td><td class='input'><input type='text' name='powerCableDesc' value='$powerCableDesc'></td></tr>
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
					<input type='submit' name='action' value='Update Sheet'/>";
				if (verifySessions() == 2)
				{
					echo"<input type='submit' name='action' value='Delete Sheet'/><input type='submit' name='action' value='Email Customer'/>";
				}
					echo"</div>
				</form>";
			}
		}
	}
	
	//Displays form for searching check-ins
	function searchForm()
	{
		global $pageName;
		echo "<div class='subhead'>Search</div>
				<form action='$pageName' id='searchForm' method='post'>
				<table><tr><td class='input'>Employee</td><td cclass='input'>";
		echo generateDropDowns("consultantSearch");
		echo "</td></tr>\n
		<tr><td class='input'>Timespan</td><td class='input'><input type='date' name='startDate'></td><td class='input'><input type='date' name='endDate'></td></td></tr>\n
		<tr><td class='input'>Ticket Number</td><td class='input'><input type='text' name='ticketNumber'></td></tr>\n
		<tr><td class='input'>Customer Username</td><td class='input'><input type='text' name='customerUserName'></td></tr>\n
		<tr><td colspan='2' class='input'><input type='submit' name='action' value='Search Sheets'/></td></tr></table></form>\n";
	}
	
	//Displays info page that allows for emailer to send emails and see what has been previously sent
	function emailInfoForm()
	{
		global $connection;
		global $pageName;
		global $emailArray;
		$ticketNo = $_POST["ticketNo"];
		$instanceID = $_POST["instanceID"];
		$customerUserName = $_POST["customerUserName"];
		$customerFirstName = $_POST["customerFirstName"];
		echo "<div class='subhead'>Previous Emails</div>";
		$query = "SELECT emailType,staffSent,dateSent FROM checkout.emails WHERE ticketNo=? AND instanceID=?";
		if ($stmt = $connection->prepare($query))
		{
			$stmt->bind_param("ii",$ticketNo,$instanceID);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($emailType,$staffSent,$dateSent);
			echo "<table><tr><td>Type</td><td>Staff</td><td>Date</td></tr>";
			while ($stmt->fetch())
			{
				$emailType = $emailArray[$emailType];
				echo "<tr><td>$emailType</td><td>$staffSent</td><td>$dateSent</td></tr>";
			}
			echo "</table>";
		}
		echo "<div class='subhead'>Send Email</div>
		<form action='$pageName' id='emailForm' method='post'>
				<table><tr><td class='input'>Email Type</td><td cclass='input'>";
		echo generateDropDowns("emailType");
		echo "</td></tr>\n
		<tr><td colspan='2' class='input'><input type='submit' name='action' value='Send Email'/></td></tr></table>
		<input type='hidden' name='ticketNo' value='$ticketNo'>
		<input type='hidden' name='instanceID' value='$instanceID'>
		<input type='hidden' name='customerUserName' value='$customerUserName'>
		<input type='hidden' name='customerFirstName' value='$customerFirstName'></form>\n";
	}
	
	//Displays a copy of what the current email to be sent looks like
	function confirmEmailForm()
	{
		global $rightNow;
		global $userName;
		global $emailArray;
		global $pageName;
		echo "<div class='subhead'>Confirm Email</div>";
		$body = emailBodyFunc($_POST['customerFirstName'],$_POST['emailType'],$_POST['ticketNo'],$_POST['instanceID']);
		$email = $_POST['customerUserName']."@pitt.edu";
		if ($_POST['emailType'] == 0)
		{
			$subject = "Computer Drop-Off Information";
		}
		else if ($_POST['emailType'] == 1)
		{
			$subject = "Computer Needs your Assistance";
		}
		else if ($_POST['emailType'] == 2)
		{
			$subject = "Computer Ready for Pickup";
		}
		else if ($_POST['emailType'] == 3)
		{
			$subject = "Computer Status Update";
		}
		else if ($_POST['emailType'] == 4)
		{
			$subject = "Technology Services USB Data Drive";
		}
		else if ($_POST['emailType'] == 5)
		{
			$subject = "Computer Drop-Off Completion";
		}
		else {}
		$subject .= " [Ticket #".$_POST["ticketNo"]."]";
		echo "<form action='$pageName' id='emailForm' method='post'><table>
		<tr><td class='input'>To:</td><td class='emailDisplay'><input type='text' value='$email' size=100 disabled></td></tr>\n
		<tr><td class='input'>Subject:</td><td class='emailDisplay'><input type='text' value='$subject' size=100 disabled></td></tr>\n
		<tr><td class='input'>Body:</td><td class='emailDisplay'><textarea cols=75 rows=20 disabled>$body</textarea></td></tr>\n
		<tr><td colspan='2' class='input'><input type='submit' name='action' value='Confirm Email'/>
		<input type='hidden' name='ticketNo' value=".$_POST['ticketNo'].">
		<input type='hidden' name='instanceID' value=".$_POST['instanceID'].">
		<input type='hidden' name='customerUserName' value=".$_POST['customerUserName'].">
		<input type='hidden' name='customerFirstName' value=".$_POST['customerFirstName'].">
		<input type='hidden' name='emailType' value=".$_POST['emailType'].">
		</td></tr></table></form>";
	}
?>
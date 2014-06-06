<?php
	//Creates new entry in check-in database
	function submitFormFunc()
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
			$_POST['consultantDropOff'] = validateStaff($_POST['consultantDropOff']);
			$_POST['staffDropOff'] = validateStaff($_POST['staffDropOff']);
			if ($_POST['consultantDropOff'] == "No Match" || $_POST['staffDropOff'] == "No Match")
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
			$stmt = $connection->prepare($query);
			$stmt->bind_param("iissssssssssisisisiisssssss",$_POST['ticketNo'],$instanceID,$_POST['customerFirstName'],$_POST['customerLastName'],$_POST['customerDropOff'],$rightNow,$_POST['consultantDropOff'],$_POST['staffDropOff'],$_POST['customerUserName'],$_POST['computerMake'],$_POST['computerModel'],$_POST['computerSerialNum'],$_POST['powerCableQuantity'],$_POST['powerCableDesc'],$_POST['mediaQuantity'],$_POST['mediaDesc'],$_POST['otherQuantity'],$_POST['otherDesc'],$_POST['warrantyStatus'],$_POST['cssdDriveOut'],$_POST['altUserName'],$_POST['altFirstName'],$_POST['altLastName'],$_POST['topCondition'],$_POST['bottomCondition'],$_POST['screenCondition'],$_POST['keyboardCondition']);
			$stmt->execute();
			$stmt->store_result();
		}
	}

	//Used to update check-in sheet info when edited
	function editFormFunc()
	{
		global $messages;
		global $connection;
		global $break;
		global $rightNow;
		global $instanceID;
		global $userName;
		$break = FALSE;
			// If not ready for Pick-Up, does not allow for pick-up date to be filled
		if ($_POST["closedForm"] == "FALSE")
		{
			if (isNullOrEmptyString($_POST["customerFirstNamePickUp"]) || isNullOrEmptyString($_POST["customerLastNamePickUp"]) || isNullOrEmptyString($_POST["customerPickUp"]) || isNullOrEmptyString($_POST["consultantPickUp"]) || isNullOrEmptyString($_POST["staffPickUp"]))
			{
				$rightNow = NULL;
				$_POST["consultantPickUp"] = NULL;
				$_POST["staffPickUp"] = NULL;
			}
			else
			{
					//If section is filled out, validate both signature passcodes
				if (verifySessions() == 2)
				{}
				else if (verifySessions() == 1)
				{
					$_POST["staffPickUp"] = validateStaff($_POST['staffPickUp']);
				}
				else
				{
					$_POST["consultantPickUp"] = validateStaff($_POST['consultantPickUp']);
					$_POST["staffPickUp"] = validateStaff($_POST['staffPickUp']);
				}
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
			if (verifySessions() == 2)
			{
				if ($_POST["closedForm"] == "FALSE")
				{
					$query = "UPDATE sheets SET customerFirstName = ?, customerLastName = ?, customerDropOff = ?, datePickUp = ?, consultantDropOff = ?, staffDropOff = ?, customerUserName = ?, computerMake = ?, computerModel = ?, computerSerialNum = ?,  powerCableQuantity = ?, powerCableDesc = ?, mediaQuantity = ?, mediaDesc = ?, otherQuantity = ?, otherDesc = ?, warrantyStatus = ?, cssdDriveOut = ?, altUserName = ?, altFirstName = ?, altLastName = ?,  topCondition = ?, bottomCondition = ?, screenCondition = ?, keyboardCondition = ?, customerFirstNamePickUp = ?,customerLastNamePickUp = ?, customerPickUp = ?, consultantPickUp = ?, staffPickUp = ? WHERE ticketNo = ? AND instanceID = ?";
					$stmt = $connection->prepare($query);
					$stmt->bind_param("ssssssssssisisisiissssssssssssii",$_POST['customerFirstName'],$_POST['customerLastName'],$_POST['customerDropOff'],$rightNow,$_POST['consultantDropOff'],$_POST['staffDropOff'],$_POST['customerUserName'],$_POST['computerMake'],$_POST['computerModel'],$_POST['computerSerialNum'],$_POST['powerCableQuantity'],$_POST['powerCableDesc'],$_POST['mediaQuantity'],$_POST['mediaDesc'],$_POST['otherQuantity'],$_POST['otherDesc'],$_POST['warrantyStatus'],$_POST['cssdDriveOut'],$_POST['altUserName'],$_POST['altFirstName'],$_POST['altLastName'],$_POST['topCondition'],$_POST['bottomCondition'],$_POST['screenCondition'],$_POST['keyboardCondition'],$_POST['customerFirstNamePickUp'],$_POST['customerLastNamePickUp'],$_POST['customerPickUp'],$_POST['consultantPickUp'],$_POST['staffPickUp'],$_POST['ticketNo'],$instanceID);
				}
				else
				{
					$query = "UPDATE sheets SET customerFirstName = ?, customerLastName = ?, customerDropOff = ?, datePickUp = ?, consultantDropOff = ?, staffDropOff = ?, customerUserName = ?, computerMake = ?, computerModel = ?, computerSerialNum = ?,  powerCableQuantity = ?, powerCableDesc = ?, mediaQuantity = ?, mediaDesc = ?, otherQuantity = ?, otherDesc = ?, warrantyStatus = ?, cssdDriveOut = ?, altUserName = ?, altFirstName = ?, altLastName = ?,  topCondition = ?, bottomCondition = ?, screenCondition = ?, keyboardCondition = ? WHERE ticketNo = ? AND instanceID = ?";
					$stmt = $connection->prepare($query);
					$stmt->bind_param("ssssssssssisisisiisssssssii",$_POST['customerFirstName'],$_POST['customerLastName'],$_POST['customerDropOff'],$rightNow,$_POST['consultantDropOff'],$_POST['staffDropOff'],$_POST['customerUserName'],$_POST['computerMake'],$_POST['computerModel'],$_POST['computerSerialNum'],$_POST['powerCableQuantity'],$_POST['powerCableDesc'],$_POST['mediaQuantity'],$_POST['mediaDesc'],$_POST['otherQuantity'],$_POST['otherDesc'],$_POST['warrantyStatus'],$_POST['cssdDriveOut'],$_POST['altUserName'],$_POST['altFirstName'],$_POST['altLastName'],$_POST['topCondition'],$_POST['bottomCondition'],$_POST['screenCondition'],$_POST['keyboardCondition'],$_POST['ticketNo'],$instanceID);
				}
			}
			else
			{
				if ($_POST["closedForm"] == "FALSE")
				{
					$query = "UPDATE sheets SET datePickUp = ?, powerCableQuantity = ?, powerCableDesc = ?, mediaQuantity = ?, mediaDesc = ?, otherQuantity = ?, otherDesc = ?, warrantyStatus = ?, cssdDriveOut = ?, topCondition = ?, bottomCondition = ?, screenCondition = ?, keyboardCondition = ?, customerFirstNamePickUp = ?,customerLastNamePickUp = ?, customerPickUp = ?, consultantPickUp = ?, staffPickUp = ? WHERE ticketNo = ? AND instanceID = ?";
					$stmt = $connection->prepare($query);
					$stmt->bind_param("sisisisiisssssssssii",$rightNow,$_POST['powerCableQuantity'],$_POST['powerCableDesc'],$_POST['mediaQuantity'],$_POST['mediaDesc'],$_POST['otherQuantity'],$_POST['otherDesc'],$_POST['warrantyStatus'],$_POST['cssdDriveOut'],$_POST['topCondition'],$_POST['bottomCondition'],$_POST['screenCondition'],$_POST['keyboardCondition'],$_POST['customerFirstNamePickUp'],$_POST['customerLastNamePickUp'],$_POST['customerPickUp'],$_POST['consultantPickUp'],$_POST['staffPickUp'],$_POST['ticketNo'],$instanceID);
				}
				else
				{
					$query = "UPDATE sheets SET powerCableQuantity = ?, powerCableDesc = ?, mediaQuantity = ?, mediaDesc = ?, otherQuantity = ?, otherDesc = ?, warrantyStatus = ?, cssdDriveOut = ?, topCondition = ?, bottomCondition = ?, screenCondition = ?, keyboardCondition = ? WHERE ticketNo = ? AND instanceID = ?";
					$stmt = $connection->prepare($query);
					$stmt->bind_param("isisisiissssii",$_POST['powerCableQuantity'],$_POST['powerCableDesc'],$_POST['mediaQuantity'],$_POST['mediaDesc'],$_POST['otherQuantity'],$_POST['otherDesc'],$_POST['warrantyStatus'],$_POST['cssdDriveOut'],$_POST['topCondition'],$_POST['bottomCondition'],$_POST['screenCondition'],$_POST['keyboardCondition'],$_POST['ticketNo'],$instanceID);
				}
			}
			$stmt->execute();
			$stmt->store_result();
		}
	}
	
	//Used to delete check-in sheets
	function deleteFormFunc()
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
	
	//Displays all matching check-ins to a search quota
	function searchFormFunc()
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
	
	//Sends Email to Customer
	function sendEmailFunc()
	{
		global $messages;
		global $connection;
		global $rightNow;
		global $userName;
		global $emailArray;
		//print_r($_POST);
		//$additonalBody = trim(preg_replace('/ +/', ' ', preg_replace('/[^A-Za-z0-9 ]/', ' ', urldecode(html_entity_decode(strip_tags($_POST['additionalBody']))))));
		$body = emailBodyFunc($_POST['customerFirstName'],$_POST['emailType'],$_POST['ticketNo'],$_POST['instanceID']);
		$email = $_POST['customerUserName']."@pitt.edu";
			$emailArray = array(0=>"Checked In",1=>"Assistance Needed",2=>"Pick Up",3=>"Hardware",4=>"Data Drive",5=>"Appointment Closed");
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
		$headers = "From: Student Computing Services <resnet@pitt.edu>\r\n" . "Reply-To: helpdesk@pitt.edu\r\n";
        $headers .= "Content-Type: text/plain; charset=ISO_8859-1\r\n";
		echo $email."<br><br>";
		echo $subject."<br><br>";
		print $body."<br><br>";
		echo $headers."<br><br>";
		/*if (@mail($email,$subject,$body,$headers))
		{
			$messages .= "RESULT:Email Sent Successfully!::";
			
		}
		else
		{
			$messages .= "ERROR:Sending Email Failed::";
		}*/
	}
	
	//Creates the body of an email for sending to a customer and for archive viewing
	function emailBodyFunc($firstName,$type,$ticketNo,$instanceID)
	{
		global $emailArray;
		$message = "Dear $firstName,\n\n\n";
		$time = "Monday | 8:30am - 6:00pm\nTuesday | 8:30am - 6:00pm\nWednesday | 8:30am - 8:30pm\nThursday | 8:30am - 6:00pm\nFriday | 8:30am - 5:00pm\nSaturday | 10:00am - 5:00pm\nSunday | 12:00pm - 4:00pm\n\n\n";
		$footer = "Sincerely,\n\nTechnology Services\nUniversity of Pittsburgh\nComputing Services and Systems Development (CSSD)\n\ntechnology.pitt.edu\n412-624-HELP [4357]\n";
		if ($type == 0)	//Checked In
		{
			$message .= "";
		}
		else if ($type == 1) //Assistance Needed
		{
			$message .= "We currently working on your device and need your assistance to move forward with the repairs. When you come a Technical Consultant will review with you the work that has been and still needs to be done on your device.\n\nOur standard hours are as follows (please allow 30 minutes for assistance):\n";
			$message .= $time;
			$message .= "Any further questions can be answered by calling the Technology Helpdesk at 412-624-HELP (4357).\n\n";
		}
		else if ($type == 2) //Pick Up
		{
			$message .= "We have finished working on your device and it is ready to be picked up at the Technology Services Desk at the University Store on Fifth. When you come a Technical Consultant will review with you the work that has been done on your device.\n\nOur standard hours are as follows (please allow 30 minutes for check out procedures):\n";
			$message .= $time;
			$message .= "Any further questions can be answered by calling the Technology Helpdesk at 412-624-HELP (4357).\n\n";
		}
		else if ($type == 3) //Hardware
		{
			$message .= "While working on your device, it was determined that there are hardware issues present. Your device is ready to be picked up at the Technology Services Desk at the University Store on Fifth. When you arrive, a Technical Consultant will discuss options to have your computer repaired at the University Store Hardware Repair Center (please allow 30 minutes for this consultation).\n\nThe Technology Services Desk standard hours are as follows:\n";
			$message .= $time;
			$message .= "The University Store Hardware Repair Center hours are as follows:\nMonday | 9:30am - 5:30pm\nTuesday | 9:30am - 5:30pm\nWednesday | 11:30am - 7:30pm\nThursday | 9:30am - 5:30pm\nFriday | 11:00am - 4:30pm\n\nAny further questions can be answered by calling the Technology Helpdesk at 412-624-HELP (4357).\n\n";
		}
		else if ($type == 4) //Data Drive
		{
			$message .= "You are being contacted because our records indicate that the Technology Services Desk has lent you a USB data drive to back up your data.\n\nIf you need assistance transferring your data please feel free to stop by and we would be glad to help you out. If not, please return the USB data drive to the Technology Services Desk at the University Store on Fifth as soon as possible.\n\nOur standard hours are as follows:\n";
			$message .= $time;
			$message .= "If you have any further questions or if you believe this to be a mistake please call the Technology Helpdesk at 412-624-HELP (4357).\n\n";
		}
		else if ($type == 5) //Appointment Closed
		{
			$message .= "";
		}
		else{}
		$message .= $footer;
		return $message;
	}
?>
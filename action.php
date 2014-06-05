<?php
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
	
?>
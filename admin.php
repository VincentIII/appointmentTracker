<?php
	//Form used to set passcodes for the first time
	function passcodeForm()
	{
		global $pageName;
		echo "<form action='$pageName' id='submitForm' method='post' autocomplete='off'>
		<div class='subhead'>Reset Passcode</div><table>
		<tr><td class='input'>Old Passcode:</td><td class='input'><input type='password' name='oldPassword'></td></tr>
		<tr><td class='input'>New Passcode:</td><td class='input'><input type='password' name='newPassword1'></td></tr>
		<tr><td class='input'>Confirm Passcode:</td><td class='input'><input type='password' name='newPassword2'></td></tr>
		</table>
		<div class='action'>
			<input type='submit' name='action' value='Reset'/>
		</div>
		</form>";
	}
	
	//Resets password after running two verifications on identity and correct password selection
	function passcodeFunc()
	{
		global $connection;
		global $messages;
		$match = FALSE;
		if ($_POST["newPassword1"] == $_POST["newPassword2"])
		{
			if ($stmt = $connection->prepare("SELECT consultantPasscode FROM consultants WHERE consultantUserName = ?"))
			{	
				$stmt->bind_param("s",$_SESSION["USER"]);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($consultantPasscode);
				while ($stmt->fetch())
				{
					if (password_verify($_POST["oldPassword"],$consultantPasscode))
					{
						$match = TRUE;
					}
				}
			}
			if ($match == TRUE)
			{
				if ($stmt2 = $connection->prepare("UPDATE consultants SET consultantPasscode = ? WHERE consultantUserName = ?"))
				{
					$hash = password_hash($_POST["newPassword1"],PASSWORD_DEFAULT);
					$stmt2->bind_param("ss",$hash,$_SESSION["USER"]);
					$stmt2->execute();
					$stmt2->store_result();
					$messages .= "RESULT:Passcode Resetted Successfully::";
				}
				else
				{
					$messages .= "ERROR:Database Error. Please contact Full-Time Staff::";
				}
			}
			else
			{
				$messages .= "ERROR:Old passcode did not match on in database.  Try Again::";
			}
		}
		else
		{
			$messages .= "ERROR:New Passcode did not match. Try again::";
		}
	}
	
	//Form displayed for logging in to panel
	function loginForm()
	{
		global $pageName;
		echo "<form action='$pageName' id='submitForm' method='post' autocomplete='off'>
		<div class='subhead'>Log In</div><table>
		<tr><td class='input'>Username:</td><td class='input'><input type='text' name='username' onfocus='clearThis(this)'></td></tr>
		<tr><td class='input'>Passcode:</td><td class='input'><input type='password' name='password' onfocus='clearThis(this)'></td></tr></table>
		<div class='action'>
			<input type='submit' name='action' value='Log in'/>
		</div>
		</form>";
	}
	
	//Function for logging user into admin section and establishing staff type
	function loginFunc()
	{
		global $userName;
		global $userType;
		global $connection;
		global $rightNow;
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
					$userName = $consultantUserName;
					$userType = $consultantType;
				}
			}
		}
		if ($match == TRUE)
		{
			$query = "UPDATE consultants SET lastSignedIn = ?, lastIPAddress = ? WHERE consultantUserName = ?";
			$stmt = $connection->prepare($query);
			$stmt->bind_param("sss",$rightNow,$_SERVER['REMOTE_ADDR'],$userName);
			$stmt->execute();
			$stmt->store_result();
		}
		return $match;
	}
	
	//Logs user out of admin panel
	function logoutFunc()
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
?>
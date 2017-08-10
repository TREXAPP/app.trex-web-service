<?php
/***
 the app sends the login credentials as POST variables
 the app receives from server an array in this format:
 array['islogin']='1' for successful login or '0' for unsuccessful login
 array['loginerror']='<error message>' if unsuccessful or empty string if successful
 rows found with all the control points found for that user
 ***/
 
 
 //testing - query link: http://app.trex.mk/login.php?username=treskavec3&password=treskavec3123&operator=igor

//Igor J. - 28.10.2016 21:47

require 'conn.php';

$username = urldecode($_POST['username']);
$password = urldecode($_POST['password']);
$operator = urldecode($_POST['operator']);
$deviceID = urldecode($_POST['deviceid']);
$loginComment = urldecode($_POST['logincomment']);

$myArray = array();
$myArray['islogin'] = '0';
$myArray['loginerror'] = '';
	
if (!$username) {
	$myArray['islogin'] = "0";
	$myArray['loginerror'] .= "The username cannot be empty;";
}

if (!$password) {
	$myArray['islogin'] = "0";
	$myArray['loginerror'] .= "The password cannot be empty;";
}

if ($myArray['loginerror'] == '') {
	
	$query = "SELECT `ControlPoints`.`CPID`,`ControlPoints`.`Comment` AS CPComment, `ControlPoints`.`CPName`,`RacesControlPoints`.`CPNo`,`Races`.`RaceID`,`Races`.`RaceName`,`Races`.`Description` AS RaceDescription,`Users`.`Comment` AS UsersComment
			FROM `Users`
			JOIN `ControlPoints` ON `Users`.`CPID`=`ControlPoints`.`CPID`
			JOIN `RacesControlPoints` ON `RacesControlPoints`.`CPID`=`ControlPoints`.`CPID`
			JOIN `Races` ON `RacesControlPoints`.`RaceID`=`Races`.`RaceID` WHERE ";
	$query .= "`Users`.Username = '" . $username . "' AND `Users`.Hash = '" . $password . "';";
	
	$result = mysqli_query($conn,$query);
	
	if ($result) {
		//check if user is found with that username/password combination
		if ($result->num_rows == 0) {
			//user not found with that username/password combination
			$myArray['loginerror'] .= "Wrong username or password;";
		} else {
			//add the rows found into the output array, row by row
			while($row = $result->fetch_array(MYSQL_ASSOC)) {
				$myArray[] = $row;
			}
		}
	} else {
		//error with the connection
		$myArray['loginerror'] .= mysqli_error($conn);
	}
	//if no errors are found so far
	if ($myArray['loginerror'] == '') {


		
		//check first if there is a leftover entry in LoggedUsers for the same user&DeviceID combination, if so delete it
		$checkQuery = "SELECT * FROM `LoggedUsers` WHERE Username='" . $username . "' AND DeviceID='" . $deviceID . "'";
		$checkResult = mysqli_query($conn,$query);
		$deleteNeeded = false;
		if ($checkResult){
			if ($checkResult->num_rows != 0) {
				$deleteNeeded = true;
			}
		} else {
			$myArray['loginerror'] .= mysqli_error($conn);
		}

		if ($myArray['loginerror'] == '') {
			
			//use transaction
			mysqli_autocommit($conn,FALSE);
			if ($deleteNeeded) {
				$deleteQuery = "DELETE FROM `LoggedUsers` WHERE Username='" . $username . "' AND DeviceID='" . $deviceID . "'";
				if (!mysqli_query($conn,$deleteQuery))
				{
					$myArray['loginerror'] .= mysqli_errno($conn);
				}
			}
			
			if ($myArray['loginerror'] == '') {
				//insert in LoggedUsers
				$insertQuery1 = "INSERT INTO LoggedUsers (Username";
				if ($operator) {
					$insertQuery1 .= ",Operator";
				}
				if ($deviceID) {
					$insertQuery1 .= ",DeviceID";
				}
				if ($loginComment) {
					$insertQuery1 .= ",Comment";
				}
				
				$insertQuery1 .= ") VALUES ('" . $username . "'";
				if ($operator) {
					$insertQuery1 .= ",'" . $operator . "'";
				}
				if ($deviceID) {
					$insertQuery1 .= ",'" . $deviceID . "'";
				}
				if ($loginComment) {
					$insertQuery1 .= ",'" . $loginComment . "'";
				}
				$insertQuery1 .= ");";
				//echo $insertQuery1;
				if (!mysqli_query($conn,$insertQuery1))
				{
					$myArray['loginerror'] .= mysqli_errno($conn);
				}
				
				if ($myArray['loginerror'] == '') {
					//insert in LoginLog
					$insertQuery2 = "INSERT INTO LoginLog (Username,Action";
					if ($operator) {
						$insertQuery2 .= ",Operator";
					}
					if ($deviceID) {
						$insertQuery2 .= ",DeviceID";
					}
					if ($loginComment) {
						$insertQuery2 .= ",Comment";
					}
					
					$insertQuery2 .= ") VALUES ('" . $username . "','login'";
					if ($operator) {
						$insertQuery2 .= ",'" . $operator . "'";
					}
					if ($deviceID) {
						$insertQuery2 .= ",'" . $deviceID . "'";
					}
					if ($loginComment) {
						$insertQuery2 .= ",'" . $loginComment . "'";
					}
					$insertQuery2 .= ");";		
					//echo $insertQuery2;
					if (!mysqli_query($conn,$insertQuery2)) {
						$myArray['loginerror'] .= mysqli_errno($conn);
					}
				}
			}
			if ($myArray['loginerror'] == '') {
				mysqli_commit($conn);
				//mark login as successful
				$myArray['islogin'] = '1';
			} else {
				mysqli_rollback($conn);
			}
		}
	}	
}

	echo json_encode($myArray);
	mysql_close($conn);

?>
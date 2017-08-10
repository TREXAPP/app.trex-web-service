<?php
/***
 the app sends Username, Operator, DeviceID, LogoutComment
 the service deletes the row in LoggedUsers with that user
 the service adds a line in LoginLog with Action='logout'
 the app receives json array from the service with these fields:
 array['islogout']="1" for successful or array['islogout']="0" for unsuccessful logout
 array['logouterror']="" for successful or array['logouterror']="<error message>" for unsuccessful logout
 array['logouterror'] - (optional) comment, not scripted
 
 ***/
 
 
 //testing - query link: http://app.trex.mk/login.php?username=treskavec3&password=treskavec3123&operator=igor

//Igor J. - 03.11.2016 11:32

require 'conn.php';

$username = urldecode($_POST['username']);
$operator = urldecode($_POST['operator']);
$deviceID = urldecode($_POST['deviceid']);
$logoutComment = urldecode($_POST['logoutcomment']);

$myArray = array();
$myArray['islogout'] = '0';
$myArray['logouterror'] = '';
$myArray['logoutcomment'] = '';

	$query = "SELECT * FROM `LoggedUsers` WHERE Username='" . $username . "' AND DeviceID='" . $deviceID . "'";
	
	$result = mysqli_query($conn,$query);
	
	if ($result) {
		//echo $result->num_rows;
		//check if the user is logged from this device
		if ($result->num_rows == 0) {
			//user not found with that username/password combination
			$myArray['loginerror'] .= "This user wasn't logged in from this device.";
		} else {
			//all OK
		}
	} else {
		//error with the connection
		$myArray['logouterror'] .= mysqli_error($conn);
	}
	//if no errors are found so far
	if ($myArray['loginerror'] == '') {

		//use transaction
		mysqli_autocommit($conn,FALSE);
		//delete from LoggedUsers

		$deleteQuery = "DELETE FROM `LoggedUsers` WHERE Username='" . $username . "' AND DeviceID='" . $deviceID . "'";

		//echo $deleteQuery;
		if (!mysqli_query($conn,$deleteQuery))
		  {
			$myArray['loginerror'] .= mysqli_errno($conn);
		  }
		
		if ($myArray['loginerror'] == '') {
		//insert in LoginLog
		$insertQuery = "INSERT INTO LoginLog (Username,Action";
		if ($operator) {
			$insertQuery .= ",Operator";
		}
		if ($deviceID) {
			$insertQuery .= ",DeviceID";
		}
		if ($logoutComment) {
			$insertQuery .= ",Comment";
		}
		
		$insertQuery .= ") VALUES ('" . $username . "','logout'";
		if ($operator) {
			$insertQuery .= ",'" . $operator . "'";
		}
		if ($deviceID) {
			$insertQuery .= ",'" . $deviceID . "'";
		}
		if ($logoutComment) {
			$insertQuery .= ",'" . $logoutComment . "'";
		}
		$insertQuery .= ");";
		
		//echo $insertQuery;
			if (!mysqli_query($conn,$insertQuery))
			  {
				$myArray['loginerror'] .= mysqli_errno($conn);
			  }
		}

		if ($myArray['loginerror'] == '') {
			mysqli_commit($conn);
			//mark login as successful
			$myArray['islogout'] = '1';
		} else {
			mysqli_rollback($conn);
		}
	}


	echo json_encode($myArray);
	mysql_close($conn);

?>
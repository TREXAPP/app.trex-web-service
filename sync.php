<?php
/***
 the app sends username, operator, deviceid, comment, query as POST variables to the web server
 the web server executes a query and fetches all registered racers
 the web server sends to the app:
 array['issync']='1' for successful sync or '0' for unsuccessful sync
 array['syncerror']='<error message>' if unsuccessful or empty string if successful
 $myArray['rowsno'] = '<rows of racers fetched>'
 array[i]=<rows of active racers>
 ***/
 
 
 //testing - query link: http://app.trex.mk/................
 
 //test query
 //SELECT * FROM ActiveRacers
//JOIN Racers
//ON ActiveRacers.RacerID = Racers.RacerID
//WHERE RaceID=1

//Igor J. - 04.11.2016 16:56

require 'conn.php';

$username = urldecode($_POST['username']);
$operator = urldecode($_POST['operator']);
$deviceID = urldecode($_POST['deviceid']);
$comment = urldecode($_POST['comment']);
$query = urldecode($_POST['query']);

$myArray = array();
$myArray['issync'] = '0';
$myArray['syncerror'] = '';
$myArray['rowsno'] = '-1';
	
$result = mysqli_query($conn,$query);

if ($result) {
	//check if racers are found
	$myArray['rowsno'] = $result->num_rows;
	
	if ($result->num_rows > 0) {
		//add the rows found into the output array, row by row
		while($row = $result->fetch_array(MYSQL_ASSOC)) {
			$myArray[] = $row;
			
		}
	} else {
		//no racers are found
		}
} else {
//error with the connection
$myArray['syncerror'] .= mysqli_error($conn);
}

//if no errors are found so far
if ($myArray['syncerror'] == '') {

	//use transaction
	mysqli_autocommit($conn,FALSE);
	if ($myArray['syncerror'] == '') {
		
		//insert in LoginLog
		$insertQuery = "INSERT INTO LoginLog (Username,Action";
		if ($operator) {
			$insertQuery .= ",Operator";
		}
		if ($deviceID) {
			$insertQuery .= ",DeviceID";
		}
		if ($comment) {
			$insertQuery .= ",Comment";
		}
		
		$insertQuery .= ") VALUES ('" . $username . "','sync_logininfo'";
		if ($operator) {
			$insertQuery .= ",'" . $operator . "'";
		}
		if ($deviceID) {
			$insertQuery .= ",'" . $deviceID . "'";
		}
		if ($comment) {
			$insertQuery .= $myArray['rowsno'] . " racers fetched;" . $comment . "'";
		}
		$insertQuery .= ");";		
		//echo $insertQuery;
		if (!mysqli_query($conn,$insertQuery)) {
			$myArray['syncerror'] .= mysqli_errno($conn);
		}
	}

	if ($myArray['syncerror'] == '') {
		mysqli_commit($conn);
		//mark sync as successful
		$myArray['issync'] = '1';
	} else {
		mysqli_rollback($conn);
	}

}

	echo json_encode($myArray);
	mysqli_close($conn);
	


?>
<?php
/***
the app sends these parameters:
	type=sync_pull
	username - my user, don't pull the entries from this user
	last_pull_timestamp=<long> - when was the last pull, in long (0=never)

the server returns:
	success=1/0 (1 for successful, 0 for failed)
	error="" (if success=1), "<the error>" (if success=0)
	rowsNo (TODO if needed)
	payload=rows of entries

 ***/
 

 //testing http://app.trex.mk/syncEntries_pull.php?type=sync_pull&username=treskavec1&last_pull_timestamp=123
 
 
 //Igor J. - 19.05.2017 01:02

require 'conn.php';

$type = urldecode($_POST['type']);
$last_pull_timestamp = urldecode($_POST['last_pull_timestamp']);
$username = urldecode($_POST['username']);

$payload = "";
$error = "";
$success = true;


//initialize result array
$resultArray = array();
$resultArray["success"] = "";
$resultArray["error"] = "";

if ($type == "sync_pull") {

		
		$data = json_decode($payload, true);
		$rowCount = 0;
		$LocalEntryIDArray = array();
		//EntryID, CPEntries.CPID, CPName, Username AS UserID, ActiveRacerID, Barcode, Time, EntryTypeID, CPEntries.Comment, BIB, Operator, CPEntries.CPNo, Timestamp_long AS Timestamp
		$query = "SELECT EntryID, CPEntries.CPID, CPName, Username AS UserID, ActiveRacerID, Barcode, Time, EntryTypeID, CPEntries.Comment AS CPComment, BIB, Operator, CPEntries.CPNo, Timestamp_long AS Timestamp ";
		$query .= "FROM CPEntries JOIN ControlPoints ON CPEntries.CPID = ControlPoints.CPID ";
		//$query .= "WHERE Valid=1 AND Username != '" . $username . "' AND UNIX_TIMESTAMP(Timestamp) > " . $last_pull_timestamp;
		$query .= "WHERE Valid=1 AND Username != '" . $username . "' AND Timestamp_long > " . $last_pull_timestamp;
		//$result = true;
		$result = mysqli_query($conn,$query);

		if ($result) {

				if (($result->num_rows + 0) > 0) {
					$resultArray["rowsNo"] = $result->num_rows;
					$resultRowsCount = 0;
					//add the rows found into the output array, row by row
					while($row = $result->fetch_array(MYSQL_ASSOC)) {
						$resultArray[] = $row;
						$resultRowsCount++;
					}			
				}
				//$resultArray["actualRowsNo"] = $resultRowsCount;
				if ($resultRowsCount != $result->num_rows) {
					//this is the only place we will allow success=1 even though we have an error. (its more like a warning, rows may be inserted, but some rows may have errors) Admin should investigate it!
					$success = true;
					$error = "Warning: Error with fetching EntryIDs for the inserted entries.";
				}

		} else {
				$error .= "Error inserting into CPEntries, mySql database";
				$success = false;
			}
				
	} else {
		$success = false;
		$error = "Unknown call type";
}



if ($success) {
	$resultArray["success"] = "1";
} else {
	$resultArray["success"] = "0";
}
$resultArray["error"] = $error;

echo json_encode($resultArray);
mysql_close($conn);


?>
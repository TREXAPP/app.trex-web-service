<?php
/***
the app sends these parameters:
	type=sync_push_insert or sync_push_update (depending on whether it is inserting brand new entries, or it is updating existing rows)
	rowsNo	- integer that says how many rows are being sent into the payload
	payload	- json string (url encoded) with the rows
	
the server decodes the payload, inserts the entries into the mysql database and returns to the app:
	success=1/0 (1 for successful, 0 for failed)
	error= (if success=0, a string description of the error. if success=1 error="")
	ids= json string (url encoded) with 2 columns, LocalEntryID and EntryID. Basically, it returns the EntryIDs of the inserted rows, to be updated localy. 

 ***/
 
 
 //testing - insert link: http://app.trex.mk/syncEntries_push.php?type=sync_push_insert&rowsNo=3&payload=%7B%220%22%3A%7B%22LocalEntryID%22%3A%229%22%2C%22CPID%22%3A%225%22%2C%22UserID%22%3A%22prisad2%22%2C%22Time%22%3A%222017-04-30+12%3A55%3A51%22%2C%22EntryTypeID%22%3A%221%22%2C%22Comment%22%3A%22%22%2C%22BIB%22%3A%22412%22%2C%22Valid%22%3A%221%22%2C%22Operator%22%3A%22dd%22%2C%22TimeStamp%22%3A%221493556951184%22%7D%2C%221%22%3A%7B%22LocalEntryID%22%3A%2210%22%2C%22CPID%22%3A%225%22%2C%22UserID%22%3A%22prisad2%22%2C%22Time%22%3A%222017-04-30+12%3A55%3A58%22%2C%22EntryTypeID%22%3A%221%22%2C%22Comment%22%3A%22%22%2C%22BIB%22%3A%22053%22%2C%22Valid%22%3A%221%22%2C%22Operator%22%3A%22dd%22%2C%22TimeStamp%22%3A%221493556958649%22%7D%2C%222%22%3A%7B%22LocalEntryID%22%3A%2211%22%2C%22CPID%22%3A%225%22%2C%22UserID%22%3A%22prisad2%22%2C%22Time%22%3A%222017-04-30+12%3A56%3A05%22%2C%22EntryTypeID%22%3A%221%22%2C%22Comment%22%3A%22%22%2C%22BIB%22%3A%22293%22%2C%22Valid%22%3A%221%22%2C%22Operator%22%3A%22dd%22%2C%22TimeStamp%22%3A%221493556965232%22%7D%7D
 //test2 = http://app.trex.mk/syncEntries_push.php?type=sync_push_insert&rowsNo=1&payload=%7B%220%22%3A%7B%22LocalEntryID%22%3A%2223%22%2C%22CPID%22%3A%225%22%2C%22UserID%22%3A%22prisad2%22%2C%22Time%22%3A%222017-05-02+13%3A48%3A18%22%2C%22EntryTypeID%22%3A%221%22%2C%22Comment%22%3A%22%22%2C%22BIB%22%3A%22321%22%2C%22Valid%22%3A%220%22%2C%22Operator%22%3A%22dd%22%2C%22ReasonInvalid%22%3A%22Code+01%3A+There+was+a+valid+entry+for+this+runner+in+the+last+1+minutes%22%2C%22TimeStamp%22%3A%221493732898357%22%7D%7D
 //test3 = http://app.trex.mk/syncEntries_push.php?type=sync_push_insert&rowsNo=5&payload=%7B%220%22%3A%7B%22LocalEntryID%22%3A%221%22%2C%22Time%22%3A%222017-05-03+09%3A57%3A51%22%2C%22EntryTypeID%22%3A%221%22%2C%22Comment%22%3A%22%22%2C%22BIB%22%3A%22114%22%2C%22Valid%22%3A%221%22%2C%22TimeStamp%22%3A%221493805471802%22%7D%2C%221%22%3A%7B%22LocalEntryID%22%3A%222%22%2C%22CPID%22%3A%221%22%2C%22UserID%22%3A%22treskavec1%22%2C%22ActiveRacerID%22%3A%222%22%2C%22Time%22%3A%222017-05-03+09%3A58%3A49%22%2C%22EntryTypeID%22%3A%221%22%2C%22Comment%22%3A%22%22%2C%22BIB%22%3A%22228%22%2C%22Valid%22%3A%221%22%2C%22Operator%22%3A%22qwe%22%2C%22TimeStamp%22%3A%221493805529167%22%7D%2C%222%22%3A%7B%22LocalEntryID%22%3A%223%22%2C%22CPID%22%3A%221%22%2C%22UserID%22%3A%22treskavec1%22%2C%22Time%22%3A%222017-05-03+09%3A59%3A17%22%2C%22EntryTypeID%22%3A%221%22%2C%22Comment%22%3A%22%22%2C%22BIB%22%3A%22789%22%2C%22Valid%22%3A%221%22%2C%22Operator%22%3A%22qwe%22%2C%22TimeStamp%22%3A%221493805557416%22%7D%2C%223%22%3A%7B%22LocalEntryID%22%3A%224%22%2C%22CPID%22%3A%221%22%2C%22UserID%22%3A%22treskavec1%22%2C%22Time%22%3A%222017-05-03+10%3A00%3A02%22%2C%22EntryTypeID%22%3A%221%22%2C%22Comment%22%3A%22%22%2C%22BIB%22%3A%22089%22%2C%22Valid%22%3A%221%22%2C%22Operator%22%3A%22qwe%22%2C%22TimeStamp%22%3A%221493805602467%22%7D%2C%224%22%3A%7B%22LocalEntryID%22%3A%225%22%2C%22CPID%22%3A%221%22%2C%22UserID%22%3A%22treskavec1%22%2C%22Time%22%3A%222017-05-03+10%3A07%3A05%22%2C%22EntryTypeID%22%3A%221%22%2C%22Comment%22%3A%22%22%2C%22BIB%22%3A%22336%22%2C%22Valid%22%3A%221%22%2C%22Operator%22%3A%22qwe%22%2C%22TimeStamp%22%3A%221493806025595%22%7D%7D
//Igor J. - 30.04.2017 13:35

require 'conn.php';

$type = urldecode($_POST['type']);
$rowsNo = urldecode($_POST['rowsNo']);
$payload = urldecode($_POST['payload']);
$error = "";
$success = true;



/*
INSERT INTO table (column1, column2, ... ) VALUES (expression1, expression2, ... ), (expression1, expression2, ... ), ...;
UPDATE table_name
SET column1 = value1, column2 = value2, ...
WHERE condition;
*/

//initialize result array
$resultArray = array();
$resultArray["success"] = "";
$resultArray["error"] = "";

if ($type == "sync_push_insert" || $type == "sync_push_update") {
	if (!empty($rowsNo) && $rowsNo > 0) {
		
		$data = json_decode($payload, true);
		$rowCount = 0;
		$LocalEntryIDArray = array();
		
		if ($type == "sync_push_insert") {
		$query = "INSERT INTO CPEntries (LocalEntryID, CPID, CPNo, Username, Operator, BIB, Barcode, ActiveRacerID, Time, EntryTypeID, Valid, ReasonInvalid, Timestamp_long, Comment) VALUES ";

		while ($rowCount < $rowsNo) {
	
			$currentRow = $data[$rowCount];
			//check if exists 
			
			if (empty($currentRow["LocalEntryID"])) {
				$qLocalEntryID = "IS NULL";
			} else {
				$qLocalEntryID = "= " . $currentRow["LocalEntryID"];
			}
			
			if (empty($currentRow["UserID"])) {
				$qUsername = "IS NULL";
			} else {
				$qUsername = "= '" . $currentRow["UserID"] . "'";
			}
			
			if (empty($currentRow["Time"])) {
				$qTime = "IS NULL";
			} else {
				$qTime = "= '" . $currentRow["Time"] . "'";
			}
			
			$rowExistsQuery = "SELECT LocalEntryID FROM CPEntries WHERE LocalEntryID " . $qLocalEntryID . " AND Username " . $qUsername . " AND Time " . $qTime . ";";
			$rowExistsResult = mysqli_query($conn,$rowExistsQuery);

			
			if ($rowExistsResult->num_rows > 0) {
				$rowExists = true;
			} else {
				$rowExists = false;
			}
			
			if (!$rowExists) {
				if ($rowCount != 0) {
					$query .= ", ";
				}
				$query .= "(";
				

				if (!empty($currentRow["LocalEntryID"])) {
					$query .= $currentRow["LocalEntryID"] . ", ";
				} else {
					$query .= "NULL, ";
				}
				
				if (!empty($currentRow["CPID"])) {
					$query .= $currentRow["CPID"] . ", ";
				} else {
					$query .= "NULL, ";
				}		
				
				if (!empty($currentRow["CPNo"])) {
					$query .= $currentRow["CPNo"] . ", ";
				} else {
					$query .= "NULL, ";
				}

				if (!empty($currentRow["UserID"])) {
					$query .= "'" . $currentRow["UserID"] . "', ";
				} else {
					$query .= "NULL, ";
				}
				
				if (!empty($currentRow["Operator"])) {
					$query .= "'" . $currentRow["Operator"] . "', ";
				} else {
					$query .= "NULL, ";
				}
				
				if (!empty($currentRow["BIB"])) {
					$query .= "'" . $currentRow["BIB"] . "', ";
				} else {
					$query .= "NULL, ";
				}		

				if (!empty($currentRow["Barcode"])) {
					$query .= "'" . $currentRow["Barcode"] . "', ";
				} else {
					$query .= "NULL, ";
				}
				
				if (!empty($currentRow["ActiveRacerID"])) {
					$query .= $currentRow["ActiveRacerID"] . ", ";
				} else {
					$query .= "0, ";
				}
				
				if (!empty($currentRow["Time"])) {
					$query .= "'" . $currentRow["Time"] . "', ";
				} else {
					$query .= "NULL, ";
				}		

				if (!empty($currentRow["EntryTypeID"])) {
					$query .= $currentRow["EntryTypeID"] . ", ";
				} else {
					$query .= "NULL, ";
				}
				
				if (!empty($currentRow["Valid"])) {
					$query .= $currentRow["Valid"] . ", ";
				} else {
					$query .= "0, ";
				}
				
				if (!empty($currentRow["ReasonInvalid"])) {
					$query .= "'" .  $currentRow["ReasonInvalid"] . "', ";
				} else {
					$query .= "NULL, ";
				}
				
				if (!empty($currentRow["TimeStamp"])) {
					$query .= $currentRow["TimeStamp"] . ", ";
				} else {
					$query .= "NULL, ";
				}
				
				if (!empty($currentRow["Comment"])) {
					$query .= "'" . $currentRow["Comment"] . "') ";
				} else {
					$query .= "NULL) ";
				}		


				$LocalEntryIDArray[$rowCount] = $currentRow["LocalEntryID"];

				$rowCount++;
			}
		}
		
		$query .= ";";

		//$result = true;
		$result = mysqli_query($conn,$query);
		
		
		if ($result) {
			
			//now get the response		
			//contruct select query:
			$returnQuery = "SELECT EntryID, LocalEntryID FROM `CPEntries` WHERE Username = '" . $currentRow["UserID"] . "' AND LocalEntryID IN (";
			$count = 0;
			while ($count < $rowsNo) {
				if ($count != 0) {
					$returnQuery .= ",";
				}
				$returnQuery .= $LocalEntryIDArray[$count];
				$count++;
			}
			$returnQuery .= ");";

			$result1 = mysqli_query($conn,$returnQuery);

				if (($result1->num_rows + 0) > 0) {
					$resultRowsCount = 0;
					//add the rows found into the output array, row by row
					while($row = $result1->fetch_array(MYSQL_ASSOC)) {
						$resultArray[] = $row;
						$resultRowsCount++;
					}			
				}

				if ($resultRowsCount != $rowsNo) {
					//this is the only place we will allow success=1 even though we have an error. (its more like a warning, rows may be inserted, but some rows may have errors) Admin should investigate it!
					$success = true;
					$error = "Warning: Error with fetching EntryIDs for the inserted entries.";
				}
		/*	*/
		} else {
				$error .= "Error inserting into CPEntries, mySql database";
				$success = false;
			}
				
		}	
		
		if ($type == "sync_push_update") {
		
			while ($rowCount < $rowsNo) {
				$query = "UPDATE CPEntries SET ";
				
				$currentRow = $data[$rowCount];

				//LocalEntryID, CPID, CPNo, Username, Operator, BIB, Barcode, ActiveRacerID, Time, EntryTypeID, Valid, ReasonInvalid, Timestamp_long, Comment
				$query .= "LocalEntryID=";
				if (!empty($currentRow["LocalEntryID"])) {
					$query .= "'" . $currentRow["LocalEntryID"] . "', ";
				} else {
					$query .= "NULL, ";
				}
				
				$query .= "CPID=";
				if (!empty($currentRow["CPID"])) {
					$query .= "'" . $currentRow["CPID"] . "', ";
				} else {
					$query .= "NULL, ";
				}	

				$query .= "CPNo=";
				if (!empty($currentRow["CPNo"])) {
					$query .= "'" . $currentRow["CPNo"] . "', ";
				} else {
					$query .= "NULL, ";
				}
				
				$query .= "Username=";
				if (!empty($currentRow["UserID"])) {
					$query .= "'" . $currentRow["UserID"] . "', ";
				} else {
					$query .= "NULL, ";
				}	
				
				$query .= "Operator=";
				if (!empty($currentRow["Operator"])) {
					$query .= "'" . $currentRow["Operator"] . "', ";
				} else {
					$query .= "NULL, ";
				}
				
				$query .= "BIB=";
				if (!empty($currentRow["BIB"])) {
					$query .= "'" . $currentRow["BIB"] . "', ";
				} else {
					$query .= "NULL, ";
				}		
				
				$query .= "Barcode=";
				if (!empty($currentRow["Barcode"])) {
					$query .= "'" . $currentRow["Barcode"] . "', ";
				} else {
					$query .= "NULL, ";
				}
				
				$query .= "ActiveRacerID=";
				if (!empty($currentRow["ActiveRacerID"])) {
					$query .= "'" . $currentRow["ActiveRacerID"] . "', ";
				} else {
					$query .= "0, ";
				}
				
				$query .= "Time=";
				if (!empty($currentRow["Time"])) {
					$query .= "'" . $currentRow["Time"] . "', ";
				} else {
					$query .= "NULL, ";
				}		

				$query .= "EntryTypeID=";
				if (!empty($currentRow["EntryTypeID"])) {
					$query .= "'" . $currentRow["EntryTypeID"] . "', ";
				} else {
					$query .= "NULL, ";
				}
				
				$query .= "Valid=";
				if (!empty($currentRow["Valid"])) {
					$query .= "'" . $currentRow["Valid"] . "', ";
				} else {
					$query .= "0, ";
				}
				
				$query .= "ReasonInvalid=";
				if (!empty($currentRow["ReasonInvalid"])) {
					$query .= "'" . $currentRow["ReasonInvalid"] . "', ";
				} else {
					$query .= "NULL, ";
				}
				
				$query .= "Timestamp_long=";
				if (!empty($currentRow["TimeStamp"])) {
					$query .= "'" . $currentRow["TimeStamp"] . "', ";
				} else {
					$query .= "NULL, ";
				}
				
				$query .= "Comment=";
				if (!empty($currentRow["Comment"])) {
					$query .= "'" . $currentRow["Comment"] . "' ";
				} else {
					$query .= "NULL ";
				}	

				$query .= "WHERE EntryID=" . $currentRow["EntryID"] . ";";
				//$LocalEntryIDArray[$rowCount] = $currentRow["LocalEntryID"];


				$result = mysqli_query($conn,$query);
				
				if (!$result) {
					$success = false;
					$error .= "Error while updating in mySQL database the entry with EntryID=" . $currentRow["EntryID"] . ". ";
				}
				
				$LocalEntryIDArray[$rowCount] = $currentRow["LocalEntryID"];
				$rowCount++;

			}
			
			
			//now get the response		
			//contruct select query:
			$returnQuery = "SELECT EntryID, LocalEntryID FROM `CPEntries` WHERE Username = '" . $currentRow["UserID"] . "' AND LocalEntryID IN (";
			$count = 0;
			while ($count < $rowsNo) {
				if ($count != 0) {
					$returnQuery .= ",";
				}
				$returnQuery .= $LocalEntryIDArray[$count];
				$count++;
			}
			$returnQuery .= ");";

			$result1 = mysqli_query($conn,$returnQuery);

				if (($result1->num_rows + 0) > 0) {
					$resultRowsCount = 0;
					//add the rows found into the output array, row by row
					while($row = $result1->fetch_array(MYSQL_ASSOC)) {
						$resultArray[] = $row;
						$resultRowsCount++;
					}			
				}

				if ($resultRowsCount != $rowsNo) {
					//this is the only place we will allow success=1 even though we have an error. (its more like a warning, rows may be inserted, but some rows may have errors) Admin should investigate it!
					$success = true;
					$error = "Warning: Error with fetching EntryIDs for the inserted entries.";
				}
				
				
				
		
		}
	}

	 else {
		$error = "No rows to be inserted";
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
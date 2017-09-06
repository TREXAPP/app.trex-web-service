<?php

/********************
1. (app.trex) delete all from ActiveRacers
2. (app.trex) delete all from Racers
3. (app.trex) delete all from Teams
4. (kmt)select distinct Team from rr_Racers
5. (app.trex) if not exists in Teams, insert new Team
6. (kmt) the BIG query: select STUFF from rr_Racers JOIN rr_RacersPayment

7.for() {
	- (app.trex) insert row into Racers
	- (app.trex) insert row into ActiveRacers
	}

********************/
require 'conn.php';
echo show_button();
echo "<br>";
if (isset($_POST["password"])) {
	
	if (md5($_POST["password"]) === "82f01e440ac47f174823f0e6e3bb7f4c") {
		echo "Password ok. Executing query ...<br><br>";
		
		$url = 'http://kmt.mk/dbservice/query.php';
		mysqli_begin_transaction($conn, MYSQLI_TRANS_START_READ_WRITE);
		$error = false;
		
		if (!$error) {
			//1. (app.trex) delete all from ActiveRacers
			echo "1. Deleting rows from from ActiveRacers ...<br>";
			$queryTrex = "DELETE FROM ActiveRacers;";
			$response = trexQuery($conn, $queryTrex);
			if (!mysqli_error($conn)) {
				echo "Delete successful! Deleted " . mysqli_affected_rows($conn) . " rows.<br>";
			} else {
				echo "Error! " . mysqli_error($conn) . "<br>";
				$error = true;
			}	
			echo "<br>";
		}
		
		if (!$error) {
			//2. (app.trex) delete all from Racers
			echo "2. Deleting rows from Racers ...<br>";
			$queryTrex = "DELETE FROM Racers;";
			$response = trexQuery($conn, $queryTrex);
			if (!mysqli_error($conn)) {
				echo "Delete successful! Deleted " . mysqli_affected_rows($conn) . " rows.<br>";
			} else {
				echo "Error! " . mysqli_error($conn) . "<br>";
				$error = true;
			}
			echo "<br>";
		}
		
		if (!$error) {
			//3. (app.trex) delete all from Teams
			echo "3. Deleting rows from Teams ...<br>";
			$queryTrex = "DELETE FROM Teams WHERE TeamID != 0;";
			$response = trexQuery($conn, $queryTrex);
			if (!mysqli_error($conn)) {
				echo "Delete successful! Deleted " . mysqli_affected_rows($conn) . " rows.<br>";
			} else {
				echo "Error! " . mysqli_error($conn) . "<br>";
				$error = true;
			}
			echo "<br>";
		}
		
		if (!$error) {
			//4. (kmt)select distinct Team from rr_Racers
			echo "4. Fetching Teams from kmt database ...<br>";
			$queryKMT = 'SELECT DISTINCT Team FROM `rr_Racers`';
			$response = getResponse($url, urlencode($queryKMT));
			$teams = json_decode($response);
			echo "Found " . count($teams) . " teams.<br><br>";
			if (count($teams) > 0) {
				echo "5. Inserting " . count($teams) . " rows in the `Teams` table ...<br>";
				$teamsQuery = '';
				$teamsQuery .= 'INSERT INTO `Teams` (`TeamID`, `TeamName`, `TeamDescription`) VALUES ';
				$firstRow = true;
				//INSERT INTO `Teams` (`TeamID`, `TeamName`, `TeamDescription`) VALUES (NULL, 'testTeam1', NULL), (NULL, 'testTeam2', NULL);
				foreach ($teams as $teamRow) {
					if (!$firstRow) {
						$teamsQuery .= ", ";
					} else {
						$firstRow = false;
					}
					$teamsQuery .= "(NULL, '" . $teamRow->Team . "', NULL)";
				}
				//echo $teamsQuery;
				//echo "<br><br>";
				$response = trexQuery($conn, $teamsQuery);
				if (!mysqli_error($conn)) {
					echo "Insert successful! Inserted " . mysqli_affected_rows($conn) . " rows in the `Teams` table.<br>";
				} else {
					echo "Error! " . mysqli_error($conn) . "<br>";
				}		
			}
			echo "<br>";	
		}
		
		if (!$error) {
			echo "6. Fetching Runners from kmt database ...<br>";
			//6. (kmt) the BIG query: select STUFF from rr_Racers JOIN rr_RacersPayment
			$queryKMT = '	SELECT rr_Racers.RacerID, FirstName, LastName, Gender, DateOfBirth, Country, TShirtSize, Email, Tel, Team, Comment, RaceID, BIB FROM rr_Racers
						JOIN rr_Racers_Payment ON rr_Racers.RacerID = rr_Racers_Payment.RacerID
						WHERE NOT BIB is null AND BIB != \'\' ';
			$response = getResponse($url, urlencode($queryKMT));
			$runners = json_decode($response);
			echo "Found " . count($runners) . " runners.<br><br>";
			if (count($runners) > 0) {
				echo "7. Inserting " . count($runners) . " runners in table `Racers` ...<br>";
				$runnersQuery = '';
				
				$runnersQuery .= 'INSERT INTO `Racers` (`RacerID`, `FirstName`, `LastName`, `Gender`, `DateOfBirth`, `YearBirth`, `Nationality`, `Country`, `TeamID`, `CityOfResidence`, `TShirtSize`, `Email`, `Tel`, `Food`, `Timestamp`, `Comment`) VALUES ';
				$firstRow = true;

				foreach ($runners as $runnerRow) {
					if (!$firstRow) {
						$runnersQuery .= ", ";
					} else {
						$firstRow = false;
					}
					$runnersQuery .= "(NULL, '" . addslashes($runnerRow->FirstName) . "', '" . addslashes($runnerRow->LastName) . "', '" . addslashes($runnerRow->Gender) . "', '" . addslashes($runnerRow->DateOfBirth) . "', NULL, NULL, '" . addslashes($runnerRow->Country) . "', '" . addslashes(getTeamID($runnerRow->Team, $conn)) . "', NULL, '" . addslashes($runnerRow->TShirtSize) . "', '" . addslashes($runnerRow->Email) . "', '" . addslashes($runnerRow->Tel) . "', NULL, CURRENT_TIMESTAMP, 'oldID=" . addslashes($runnerRow->RacerID) . ";" . addslashes($runnerRow->Comment) . "')";
				}
				
				//echo $runnersQuery;
				//echo "<br><br>";
				$response = trexQuery($conn, $runnersQuery);
				if (!mysqli_error($conn)) {
					echo "Insert successful! Inserted " . mysqli_affected_rows($conn) . " rows in the table `Racers`.<br><br>";
				} else {
					echo "Error! " . mysqli_error($conn) . "<br>";
					$error = true;
				}
				
				
				echo "8. Inserting " .  count($runners) . " rows into ActiveRacers ...<br>";
				
				$activeRQuery = '';
				
				
				$activeRQuery .= 'INSERT INTO `ActiveRacers` (`ActiveRacerID`, `RacerID`, `RaceID`, `Age`, `BIB`, `ChipCode`, `Started`, `Registered`, `Timestamp`, `Comment`) VALUES ';
				$firstRow = true;

				foreach ($runners as $runnerRow) {
					if (!$firstRow) {
						$activeRQuery .= ", ";
					} else {
						$firstRow = false;
					}
					$activeRQuery .= "(NULL, '" . addslashes(getRacerID($runnerRow->RacerID, $conn)) . "', '" . addslashes(getRaceID($runnerRow->RaceID)) . "', NULL, '" . addslashes($runnerRow->BIB) . "', NULL, '0', '0', CURRENT_TIMESTAMP, NULL)";
				}
				
				//echo $activeRQuery;
				//echo "<br><br>";
				$response = trexQuery($conn, $activeRQuery);
				if (!mysqli_error($conn)) {
					echo "Insert successful! Inserted " . mysqli_affected_rows($conn) . " rows in the table `ActiveRacers`.<br>";
				} else {
					echo "Error! " . mysqli_error($conn) . "<br>";
					$error = true;
				}
				
			}	
			//INSERT INTO `ActiveRacers` (`ActiveRacerID`, `RacerID`, `RaceID`, `Age`, `BIB`, `ChipCode`, `Started`, `Registered`, `Timestamp`, `Comment`) VALUES (NULL, '14', '4', NULL, '014', NULL, '0', '0', CURRENT_TIMESTAMP, NULL);
			

		}
		
		if ($error) {
			mysqli_rollback($conn);
			echo "<br>Operation Failed :(<br>";
		} else {
			mysqli_commit($conn);
			echo "<br>Operation Successful! :)<br>";
		}
		
		mysqli_close($conn);
		
	} else {
		echo "<br><span style='color: red;'><b>Wrong password!</b></span>";
	}
}

function getRacerID($OldRacerID, $conn) {

	$query = "SELECT RacerID FROM Racers WHERE Comment LIKE 'oldID=" . $OldRacerID . "%'";
	$response = trexQuery($conn,$query);
	if (($response->num_rows + 0) > 0) {
		$row = $response->fetch_array(MYSQL_ASSOC);
		if (isset($row['RacerID'])) {
			//$comment = explode(";", $row['Comment']);
			//$extractedRacerID = substr($comment[0], -3);
			//return $extractedRacerID;
			return $row['RacerID'];
		} else {
			return 0;
		}	
	} else {
		return 0;
	}
}

function getRaceID($oldRaceID) {
	$newRaceID = 8 - intval($oldRaceID);
	return $newRaceID;
}

function getTeamID($Team, $conn) {
	if (($Team == null) || ($Team == '')) {
		return 0;
	} else {
		$query = "SELECT TeamID from Teams WHERE TeamName = '" . $Team . "'";
		$response = trexQuery($conn,$query);
		if (($response->num_rows + 0) > 0) {
			$row = $response->fetch_array(MYSQL_ASSOC);
			if (isset($row['TeamID'])) {
				return $row['TeamID'];
			} else {
				return 0;
			}	
		} else {
			return 0;
		}
	}
}

function getResponse($url, $query) {
	
	$data = array();
	$data['query'] = $query;
	//$data['query'] = 'SELECT+RaceName+FROM+rr_Races';
	//$data = array('query' => 'SELECT+RaceName+FROM+rr_Races');
	$options = array(
		'http' => array(
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query($data),
		),
	);
	$context  = stream_context_create($options);
	$html = file_get_contents($url, false, $context);
	return $html;
}

function show_button() {
	$returnStr = '';
	
	$returnStr .= '
		<form id="form1" action="" method="POST">
		Password: <input type="password" name="password"><br>
		<input type="hidden" name="query" value="SELECT+RaceName+FROM+rr_Races" />
		</form>

		<button type="submit" form="form1" value="submit">Transfer runners</button>
	
	
	';
	
	
	return $returnStr;
}

function trexQuery ($conn, $query) {
	$result = mysqli_query($conn,$query);
	return $result;
}
?>
<?php
/*
http://app.trex.mk/results.php 
Bez GET parametri: Dava pocetna strana so izlistani trki (gi cita od RaceGroups tabela)
So klik na edna trka, otvara strana so GET parametar race=xxx:
Strana kaj sto gi lista site podtrki (od tabela Races), so linkovi za LIVE ili OFFICIAL results (Official krij go se dodeka ne se oficijaliziraat)

Parametri:
RaceGroupID - glavna trka (Krali Marko Trails 2017)
RaceID - podtrka (Treskavec Trail)
display - [live,final] - dali da prikaze live rezultati i officijalni
*/
?>
<html>
<head>
<title>Results - Krali Marko Trails 2017</title>
<meta name="Description" content="Live and Official results from the Krali Marko Trails 2017 race, Macedonia">
<meta name="Keywords" content="Krali Marko, Krali Marko Trails, KMT, KMT2017, Running, Trail Running, Ultra Marathon, Macedonia, Ultra Trail Running">
<link href="https://fonts.googleapis.com/css?family=Cuprum|Fira+Sans+Extra+Condensed|Open+Sans+Condensed:300|Roboto+Condensed|Ubuntu+Condensed" rel="stylesheet">
<link rel="stylesheet" href="/style_results.css">
</head>
<body>


<?php

require 'conn.php';

if (isset($_GET['RaceGroupID'])) {
	//race is selected
	/*
	Prikazi lista od site podtrki, so linkovi za rezultati vo zivo i Oficijalni rezultati
	Pod nea prikazi tabela spored toa sto e selektirano (koja trka i koj tip na rezultati)
	*/

	
	$StartTime = NULL;
	$RaceGroupID = $_GET['RaceGroupID'];
	$raceName = GetRaceName($RaceGroupID);
	echo DrawHeader($raceName);

	
	$query = "SELECT * FROM Races WHERE RaceGroupID = " . $RaceGroupID;
	$races_result = mysqli_query($conn,$query);
	$races = getRaces($races_result);
	echo DrawSubRacesList($races, $RaceGroupID);
	
	$drawTable = false;
	if ($races_result->num_rows  == 1) {
		$row = $races_result->fetch_array(MYSQL_ASSOC);
		//mysql_data_seek($races_result, 0);
		//mysql_data_seek($racegroups_result, 0);
		$RaceID = $row['RaceID'];
		$drawTable = true;
	}
	
	if (isset($_GET['RaceID'])) {
		$drawTable = true;
	}
	
	if ($drawTable) {
		$display = "live";
		if (isset($_GET['display'])) {
			$display = $_GET['display'];
		}
		

		//draw table here
		$RaceID = $_GET['RaceID'];
		$StartTime = getStartTime($races, $RaceID, $RaceGroupID);
		$headerIDsArray = array();
		$headerArray = getHeaderArray($RaceGroupID, $RaceID, $display, $headerIDsArray);
		$tableArray = getTableArray($headerIDsArray, $RaceGroupID, $RaceID, $display, $StartTime);
		$sortedTableArray = sortTableArray($tableArray, $headerArray, $StartTime);
		echo DrawTheTable($headerArray, $sortedTableArray, $display, $StartTime);
	}
	
} else {
	
	$query = "SELECT * FROM `RaceGroups` WHERE 1";
	$racegroups_result = mysqli_query($conn,$query);
	
	if (($racegroups_result->num_rows + 0) > 0) {
		echo "<ul>";
		while($row = $racegroups_result->fetch_array(MYSQL_ASSOC)) {
			$actual_link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
			$race_link = $actual_link . "?RaceGroupID=" . $row['RaceGroupID'];
			echo "<li>";
			echo "<h4><a href='" . $race_link . "'>" . $row['RaceGroupName'] . "</a></h4>";
			echo "<p>" .$row['Description'] . "</p>";
			echo "</li>";
		}	
		echo "</ul>";
	}
	/*
	*/
}


?>
</body>
</html>

<?php
function getRaces($races_result) {
	$races = array();
	$racesCount = 0;
	if ($races_result->num_rows > 0) {
		while($row = $races_result->fetch_array(MYSQL_ASSOC)) {
			$races[$racesCount]['RaceID'] = $row['RaceID'];
			$races[$racesCount]['RaceGroupID'] = $row['RaceGroupID'];
			$races[$racesCount]['RaceName'] = $row['RaceName'];
			$races[$racesCount]['Description'] = $row['Description'];
			$races[$racesCount]['StartTime'] = $row['StartTime'];
			$racesCount++;
		}
		return $races;
	} else {
		return null;
	}
}

function GetRaceName($RaceGroupID) {
	require 'conn.php';
	$racegroups_query = "SELECT * FROM `RaceGroups` WHERE `RaceGroups`.RaceGroupID='" . $RaceGroupID . "';";
	$racegroups_result = mysqli_query($conn,$racegroups_query);
	if ($racegroups_result->num_rows > 0) {
		$row = $racegroups_result->fetch_array(MYSQL_ASSOC);
		return $row['RaceGroupName'];
	} else {
		return "";
	}
}

function DrawHeader($raceName) {
	
	$returnStr = "";
	$returnStr .= "<div class='header'>";
	$returnStr .= "	<h2>" . $raceName . "</h2>";
	$returnStr .= "	<h2>Results</h2>";
	$returnStr .= "</div>";
	
	return $returnStr;
}

function getTableArray($headerIDsArray, $RaceGroupID, $RaceID, $display, $StartTime) {
	
	require 'conn.php';

	//retrieve entries
	$q = "SELECT CPNo, ActiveRacers.BIB, Time, ActiveRacers.ActiveRacerID FROM `CPEntries`
		JOIN ActiveRacers ON ActiveRacers.ActiveRacerID = CPEntries.ActiveRacerID
		WHERE Valid = 1 AND RaceID = " . $RaceID;

	$entries_result = mysqli_query($conn,$q);	
	
	while($row = $entries_result->fetch_array(MYSQL_ASSOC)) {
		$entriesArray[] = $row;
	}	

	//retrieve racers
	$q = "SELECT ActiveRacerID, CONCAT(FirstName, ' ', LastName) AS Name, Gender, Country, BIB FROM `ActiveRacers`
			JOIN Racers ON ActiveRacers.RacerID = Racers.RacerID
			WHERE RaceID = " . $RaceID;
	$racers_result = mysqli_query($conn,$q);
	if ($racers_result->num_rows > 0) {
		$racersCounter = 0;
		while($row = $racers_result->fetch_array(MYSQL_ASSOC)) {
			$racersArray[] = $row;
			/*
				$resultArray[0] = "Position\n(Total)";
				$resultArray[1] = "Position (Gender)";
				$resultArray[2] = "Starting number";
				$resultArray[3] = "Name";
				$resultArray[4] = "Gender";
				$resultArray[5] = "Country";
			*/
			
			$returnArray[$racersCounter][0] = $racersCounter+1;
			$returnArray[$racersCounter][1] = $racersCounter+1;
			$returnArray[$racersCounter][2] = $row['BIB'];
			$returnArray[$racersCounter][3] = $row['Name'];
			$returnArray[$racersCounter][4] = $row['Gender'];
			$returnArray[$racersCounter][5] = $row['Country'];
			
			$CPCounter = 0;
			while (isset($headerIDsArray[$CPCounter])) {			
				$returnArray[$racersCounter][$CPCounter+6] = findTheEntryTime($row['ActiveRacerID'],$headerIDsArray[$CPCounter],$entriesArray);
				$CPCounter++;
			}
			
			//avg speed
			$returnArray[$racersCounter][$CPCounter+6] = "--";
			$racersCounter++;
		}	

		//return $row['RaceGroupName'];
	} else {
		//nesto...
	}

	return $returnArray;
}

function sortTableArray(&$tableArray, $headerArray, $StartTime) {
	
	
	
	//1 inicijalizirame nova niza ($sortTotal) sto sodrzi samo mesto i bib
	//2 ja vrtime tableArray na sledniov nacin:
	//3 sekoja kolona, pocnuvajki od poslednata (finish) pa se do 1 kontrolna
	//4 zemame predvid samo ako postoi vreme (ne e prazno)
	//5 stavi gi vo privremena niza ($sortColumn) samo tie sto imaat vreminja
	//6 sortiraj ja taa niza od najmal do najgolem
	//7 proveri go sekoj clen od 0 nagore dali postoi vo $sortTotal. Ako ne, dodadi go. Ako postoi, ignore
	//8 Otkako $sortTotal e formirana, nov for ciklus koj sto ke ja sortira $tableArray spored redosledot vo $sortTotal.
	//9 i na kraj uste eden for ciklus sto ke proveri ako nekoj go nema vo $sortTotal, a go ima vo $tableArray (ne pominal niz ni edna kontrolna do sega) da go dodade na kraj, po redosled na BIB
	
	//1 kreirame nova niza ($sortTotal) sto sodrzi samo mesto i bib
	$sortTotal = array ();
	$stTotalCount = 0;
	//2 ja vrtime tableArray na sledniov nacin:
	//3 sekoja kolona, pocnuvajki od poslednata (finish) pa se do 1 kontrolna
	for ($i=count($tableArray[0])-1;$i>5;$i--) {
		//4 zemame predvid samo ako postoi vreme (ne e prazno)
		//5 stavi gi vo privremena niza ($sortColumn) samo tie sto imaat vreminja
		$sortColumn = array ();
		$stColumnCount = 0;
		for ($j=0;$j<count($tableArray)-1;$j++) {
			if (($tableArray[$j][$i] != "--") && ($tableArray[$j][$i] != "") && (!is_null($tableArray[$j][$i]))) {
				$sortColumn[$stColumnCount]['BIB'] = $tableArray[$j][2];
				$sortColumn[$stColumnCount]['Time'] = $tableArray[$j][$i];
				$sortColumn[$stColumnCount]['Timestamp'] = strtotime($tableArray[$j][$i]);
				$distance = $headerArray[$i]['Distance'];
				if ($distance != null) {
					$sortColumn[$stColumnCount]['AvgSpeed'] = getAvgSpeed($sortColumn[$stColumnCount]['Timestamp'], $StartTime, $distance);
				} else {
					$sortColumn[$stColumnCount]['AvgSpeed'] = '--';
				}
				$stColumnCount++;
			} else {
			}
		}

		//6 sortiraj ja $sortColumn od najmal do najgolem spored 'Time'
		usort($sortColumn, function($a, $b) {
			return strcmp($a['Timestamp'], $b['Timestamp']);
		});

		
		//7 proveri go sekoj clen od 0 nagore dali postoi vo $sortTotal. Ako ne, dodadi go. Ako postoi, ignore
		if ($stColumnCount > 0) {
			foreach ($sortColumn as &$row) {
				$sortTotal[$stColumnCount]['BIB'] = $row['BIB'];
				$sortTotal[$stColumnCount]['Time'] = $row['Time'];
				$sortTotal[$stColumnCount]['AvgSpeed'] = $row['AvgSpeed'];
				$stColumnCount++;
			}
		}
	}
	
	$sortedTableArray = array ();
	$sortedIndex = 0;
	$positionMale = 1;
	$positionFemale = 1;
	
	//8 Otkako $sortTotal e formirana, nov for ciklus koj sto ke ja sortira $tableArray spored redosledot vo $sortTotal.
	foreach ($sortTotal as &$sortTotalRow) {
		$found = false;
		foreach ($tableArray as &$tableArrayRow) {
			
			if (!$found && $sortTotalRow['BIB'] == $tableArrayRow[2]) {
				$sortedTableArray[$sortedIndex] = $tableArrayRow;
				//Position (total)
				$sortedTableArray[$sortedIndex][0] = $sortedIndex+1;
				//Position (gender)
				if ($sortedTableArray[$sortedIndex][4] == 'F') {
					$sortedTableArray[$sortedIndex][1] = 'F' . $positionFemale;
					$positionFemale++;
				} else {
					$sortedTableArray[$sortedIndex][1] = 'M' . $positionMale;
					$positionMale++;
				}
				//Avg Speed
				$sortedTableArray[$sortedIndex][count($tableArrayRow)-1] = $sortTotalRow['AvgSpeed'];
				$sortedIndex++;
				$found = true;
			}
		}
	}
	
	//9 i na kraj uste eden for ciklus sto ke proveri ako nekoj go nema vo $sortTotal, 
	//a go ima vo $tableArray (ne pominal niz ni edna kontrolna do sega) da go dodade na kraj, po redosled na BIB
	
	foreach ($tableArray as &$tableArrayRow) {
	$found = false;
		foreach ($sortTotal as &$sortTotalRow) {
			if (!$found && $sortTotalRow['BIB'] == $tableArrayRow[2]) {
				$found = true;
			}
		}
		
		if (!$found) {
				$sortedTableArray[$sortedIndex] = $tableArrayRow;				
				$sortedTableArray[$sortedIndex][0] = $sortedIndex+1;
				
			if ($sortedTableArray[$sortedIndex][4] == 'F') {
				$sortedTableArray[$sortedIndex][1] = 'F' . $positionFemale;
				$positionFemale++;
			} else {
				$sortedTableArray[$sortedIndex][1] = 'M' . $positionMale;
				$positionMale++;
			}
		
				$sortedIndex++;
		}
		


	}
	
	
	return $sortedTableArray;
}

function findTheEntryTime($ActiveRacerID, $CPNo, $entriesArray) {
	$i=0;
	$found = false;
	while(isset($entriesArray[$i])) {
		if (!$found) {
			if (($entriesArray[$i]['ActiveRacerID'] == $ActiveRacerID) && ($entriesArray[$i]['CPNo'] == $CPNo)) {
				$found == true;
				//return substr($entriesArray[$i]['Time'],11);
				return $entriesArray[$i]['Time'];
			}
		}
		$i++;
	}
	
	if (!$found) {
		return "--";
	}
	
}

function getHeaderArray($RaceGroupID, $RaceID, $display, &$headerIDsArray) {
	require 'conn.php';
	
	//retrieve header fields
	$q = "SELECT CONCAT(CPNo, '<br>', CPName) AS CPNoDisplay, CPNo, Distance FROM `RacesControlPoints` 
		JOIN ControlPoints ON RacesControlPoints.CPID = ControlPoints.CPID
		WHERE RaceID = " . $RaceID;
	$header_result = mysqli_query($conn,$q);
	$i = 0;
	if ($header_result->num_rows > 0) {
	
			$find = array("START", "FINISH");
			$replace   = array("00", "99");
		//$header_row = $racegroups_result->fetch_array(MYSQL_ASSOC);
		while($row = $header_result->fetch_array(MYSQL_ASSOC)) {
			//in order to sort it better, will replace START with 00, FINISH with 99, then sort it, and after that replace them back to START and FINISH

			$find = array("START", "FINISH");
			$replace   = array("00", "99");
			$cpnoDisplayArray[$i] = str_replace($find, $replace, $row["CPNoDisplay"]);
			$cpnoArray[$i] = str_replace($find, $replace, $row["CPNo"]);
			$distanceArray[$i] = $row['Distance'];
			$i++;
		}

		
	} else {
		//return null;
	}

	sort($distanceArray);
	sort($cpnoDisplayArray);
	sort($cpnoArray);

	$i=0;
		while (isset($cpnoArray[$i])) {
			$headerIDsArray[$i] = str_replace($replace, $find, $cpnoArray[$i]);
			$i++;
		}
	//add other fields in front
	$resultArray[0]['Value'] = "Position<br>(Total)";
	$resultArray[1]['Value'] = "Position<br>(Gender)";
	$resultArray[2]['Value'] = "Starting<br>BIB number";
	$resultArray[3]['Value'] = "Name";
	$resultArray[4]['Value'] = "Gender";
	$resultArray[5]['Value'] = "Country";
	$i=0;
		while (isset($cpnoDisplayArray[$i])) {
			$resultArray[$i+6]['Value'] = str_replace($replace, $find, $cpnoDisplayArray[$i]);
			$resultArray[$i+6]['Distance'] = $distanceArray[$i];
			$i++;
		}
		
		//Avg speed
		$resultArray[$i+6]['Value'] = "Average<br>Speed";
		$i++;
	
	return $resultArray;
}

function DrawTheTable($headerArray, $tableArray, $display, $StartTime) {
	$returnStr = "";
	if ($display == 'live') {
		$returnStr .= "
		<table class='live_results_table'>
			<tbody>
				<tr>";
		$headerCounter = 0;
		while (isset($headerArray[$headerCounter]['Value'])) {
			$returnStr .= "	<th>";
			$returnStr .= $headerArray[$headerCounter]['Value'];
			$returnStr .= "	</th>";
			
			$headerCounter++;
		}
			$returnStr .= "	</tr>";
			
		$rowCounter = 0;
		while (isset($tableArray[$rowCounter][0])) {
			$dataCounter = 0;
			$returnStr .= "	<tr>";
			while (isset($tableArray[$rowCounter][$dataCounter])) {
				$returnStr .= "	<td>";
				if (($dataCounter > 5) && ($dataCounter < count($tableArray[0])-1)) {				
					$returnStr .= formatTime($tableArray[$rowCounter][$dataCounter], $StartTime);
				} else {
					$returnStr .= $tableArray[$rowCounter][$dataCounter];
				}
				$returnStr .= "	</td>";
				$dataCounter++;
			}
			$returnStr .= "	</tr>";
			$rowCounter++;
		}

			$returnStr .= "	</tbody>
		</table>";
	}

	return $returnStr;
}

function DrawSubRacesList($races, $RaceGroupID) {
	$returnStr = "";
	$returnStr .= "<div class='subraces_list'>";
	
	$returnStr .=  "<ul>";
	/*
		while($row = $races_result->fetch_array(MYSQL_ASSOC)) {
			$actual_link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
			$subrace_link = $actual_link . "?RaceGroupID=" . $RaceGroupID . "&RaceID=" . $row['RaceID'];
			$returnStr .=  "<li>";
			$returnStr .= $row['Description'];
			$returnStr .= "<br/>";
			$returnStr .=  "<a href='" . $subrace_link .  "&display=live'>LIVE RESULTS</a>";
			$returnStr .= " | ";
			$returnStr .=  "<a href='" . $subrace_link .  "&display=final'>OFFICIAL RESULTS</a>";
			$returnStr .=  "</li>";
		}	
	*/
	
	foreach ($races as &$row) {
		$actual_link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
		$subrace_link = $actual_link . "?RaceGroupID=" . $RaceGroupID . "&RaceID=" . $row['RaceID'];
		$returnStr .=  "<li>";
		$returnStr .= $row['Description'];
		$returnStr .= "<br/>";
		$returnStr .=  "<a href='" . $subrace_link .  "&display=live'>LIVE RESULTS</a>";
		$returnStr .= " | ";
		$returnStr .=  "<a href='" . $subrace_link .  "&display=final'>OFFICIAL RESULTS</a>";
		$returnStr .=  "</li>";
	}
		$returnStr .=  "</ul>";
	
	
	$returnStr .= "</div>";

	return $returnStr;
}

function getStartTime($races, $RaceID, $RaceGroupID) {
	//mysql_data_seek($races, 0);
	
	foreach ($races as $row) {
		if ($row['RaceID'] == $RaceID && $row['RaceGroupID'] == $RaceGroupID) {
			return $row['StartTime'];
		}
	}
	return null;
	
}

function formatTime($rawValue, $StartTime) {
	if ($rawValue != '--') {
		$ValueInt = strtotime($rawValue);
		$StartTimeInt = strtotime($StartTime);
		$totalSec = $ValueInt - $StartTimeInt;
		$sec = 0;
		$min = 0;
		$hr = 0;
		if ($totalSec > 59) {
			$min = floor($totalSec/60);
			$sec = $totalSec % 60;
			
			if ($min > 59) {
				$hr = floor($min/60);
				$min = $min % 60;
		
			}
		} else {
			$sec = $totalSec;
		}
		
		if ($sec < 10) {
			$sec = '0' . $sec;
		}
		if ($min < 10) {
			$min = '0' . $min;
		}
		/*
		if ($hr < 10) {
			$sec = '0' . $sec;
		}
		*/
		
		$returnValue = $hr . ":" . $min . ":" . $sec;
		return $returnValue;
	} else {
		return $rawValue;
	}
}

function getAvgSpeed($lastTimestamp, $StartTime, $distance) {
	
	$seconds = (int)$lastTimestamp - (int)strtotime($StartTime);
	$kmh = $distance*3600/$seconds;
	
	$minkm_num = $seconds/($distance*60);
	$min = floor($minkm_num);
	$sec = floor(($minkm_num - floor($minkm_num))*60);
	if ($sec < 10) {
		$sec = '0' . $sec;
	}
	$minkm_str = $min . ":" . $sec;
	return number_format($kmh,2) . " km/h (" . $minkm_str . " min/km)";
}
?>
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
<meta http-equiv="refresh" content="30"/>
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
	$q = "SELECT ActiveRacerID, CONCAT(FirstName, ' ', LastName) AS Name, Gender, Country, BIB, DateOfBirth FROM `ActiveRacers`
			JOIN Racers ON ActiveRacers.RacerID = Racers.RacerID
			WHERE RaceID = " . $RaceID;
	$racers_result = mysqli_query($conn,$q);
	if ($racers_result->num_rows > 0) {
		$racersCounter = 0;
		while($row = $racers_result->fetch_array(MYSQL_ASSOC)) {
			$racersArray[] = $row;
			/*
				$resultArray[0] = "General rank";
				$resultArray[1] = "by Gender";
				$resultArray[2] = "by Category<br>(cat | pos)";
				$resultArray[3] = "Starting number";
				$resultArray[4] = "Name";
				$resultArray[5] = "Gender";
				$resultArray[6] = "Age";
				$resultArray[7] = "Country";
			*/
			
			$returnArray[$racersCounter][0] = $racersCounter+1;
			$returnArray[$racersCounter][1] = $racersCounter+1;
			$returnArray[$racersCounter][2] = $racersCounter+1;
			$returnArray[$racersCounter][3] = $row['BIB'];
			$returnArray[$racersCounter][4] = $row['Name'];
			$returnArray[$racersCounter][5] = $row['Gender'];
			$returnArray[$racersCounter][6] = getAge($row['DateOfBirth'], $StartTime);
			//$returnArray[$racersCounter][7] = $row['Country'];
			$returnArray[$racersCounter][7] = "<img src='/flags-mini/" . strtolower($row['Country']) . ".png'/> " . getFullCountryName($row['Country'],'en');
			
			$CPCounter = 0;
			while (isset($headerIDsArray[$CPCounter])) {			
				$returnArray[$racersCounter][$CPCounter+8] = findTheEntryTime($row['ActiveRacerID'],$headerIDsArray[$CPCounter],$entriesArray);
				$CPCounter++;
			}
			
			//avg speed
			$returnArray[$racersCounter][$CPCounter+8] = "--";
			$racersCounter++;
		}	

		//return $row['RaceGroupName'];
	} else {
		//nesto...
	}

	return $returnArray;
}

function getAge($dateOfBirth, $StartTime) {
	
	$diff = date_diff(date_create($dateOfBirth), date_create(substr($StartTime,0,10)));
	return $diff->format('%y');
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
	for ($i=count($tableArray[0])-1;$i>7;$i--) {
		//4 zemame predvid samo ako postoi vreme (ne e prazno)
		//5 stavi gi vo privremena niza ($sortColumn) samo tie sto imaat vreminja
		$sortColumn = array ();
		$stColumnCount = 0;
		for ($j=0;$j<count($tableArray)-1;$j++) {
			if (($tableArray[$j][$i] != "--") && ($tableArray[$j][$i] != "") && (!is_null($tableArray[$j][$i]))) {
				$sortColumn[$stColumnCount]['BIB'] = $tableArray[$j][3];
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
	
	$positionM1 = 1;
	$positionM2 = 1;
	
	$positionF1 = 1;
	$positionF2 = 1;
	
	//8 Otkako $sortTotal e formirana, nov for ciklus koj sto ke ja sortira $tableArray spored redosledot vo $sortTotal.
	foreach ($sortTotal as &$sortTotalRow) {
		$found = false;
		foreach ($tableArray as &$tableArrayRow) {
			
			if (!$found && $sortTotalRow['BIB'] == $tableArrayRow[3]) {
				$sortedTableArray[$sortedIndex] = $tableArrayRow;
				//Position (total)
				$sortedTableArray[$sortedIndex][0] = $sortedIndex+1;
				//Position (gender)
				if ($sortedTableArray[$sortedIndex][5] == 'F') {
					$sortedTableArray[$sortedIndex][1] = 'F | ' . $positionFemale;
					$positionFemale++;
				} else {
					$sortedTableArray[$sortedIndex][1] = 'M | ' . $positionMale;
					$positionMale++;
				}
				
				//Position (Category)
				if (((int)$sortedTableArray[$sortedIndex][6] < 50) && ($sortedTableArray[$sortedIndex][5] == 'F')) {
					//F1
					$sortedTableArray[$sortedIndex][2] = 'F(18-50) | ' . $positionF1;
					$positionF1++;
				}
				if (((int)$sortedTableArray[$sortedIndex][6] >= 50) && ($sortedTableArray[$sortedIndex][5] == 'F')) {
					//F2
					$sortedTableArray[$sortedIndex][2] = 'F(50+) | ' . $positionF2;
					$positionF2++;
				}
				if (((int)$sortedTableArray[$sortedIndex][6] < 50) && ($sortedTableArray[$sortedIndex][5] == 'M')) {
					//M1
					$sortedTableArray[$sortedIndex][2] = 'M(18-50) | ' . $positionM1;
					$positionM1++;
				}
				if (((int)$sortedTableArray[$sortedIndex][6] >= 50) && ($sortedTableArray[$sortedIndex][5] == 'M')) {
					//M2
					$sortedTableArray[$sortedIndex][2] = 'M(50+) | ' . $positionM2;
					$positionM2++;
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
			if (!$found && $sortTotalRow['BIB'] == $tableArrayRow[3]) {
				$found = true;
			}
		}
		
		if (!$found) {
				$sortedTableArray[$sortedIndex] = $tableArrayRow;				
				$sortedTableArray[$sortedIndex][0] = $sortedIndex+1;
				
			if ($sortedTableArray[$sortedIndex][5] == 'F') {
				$sortedTableArray[$sortedIndex][1] = 'F | ' . $positionFemale;
				$positionFemale++;
			} else {
				$sortedTableArray[$sortedIndex][1] = 'M | ' . $positionMale;
				$positionMale++;
			}
			

			//Position (Category)
			if (((int)$sortedTableArray[$sortedIndex][6] < 50) && ($sortedTableArray[$sortedIndex][5] == 'F')) {
				//F1
				$sortedTableArray[$sortedIndex][2] = 'F(18-50) | ' . $positionF1;
				$positionF1++;
			}
			if (((int)$sortedTableArray[$sortedIndex][6] >= 50) && ($sortedTableArray[$sortedIndex][5] == 'F')) {
				//F2
				$sortedTableArray[$sortedIndex][2] = 'F(50+) | ' . $positionF2;
				$positionF2++;
			}
			if (((int)$sortedTableArray[$sortedIndex][6] < 50) && ($sortedTableArray[$sortedIndex][5] == 'M')) {
				//M1
				$sortedTableArray[$sortedIndex][2] = 'M(18-50) | ' . $positionM1;
				$positionM1++;
			}
			if (((int)$sortedTableArray[$sortedIndex][6] >= 50) && ($sortedTableArray[$sortedIndex][5] == 'M')) {
				//M2
				$sortedTableArray[$sortedIndex][2] = 'M(50+) | ' . $positionM2;
				$positionM2++;
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
	$resultArray[0]['Value'] = "General<br>Rank";
	$resultArray[1]['Value'] = "by Gender<br>(gen | pos)";
	$resultArray[2]['Value'] = "by Category<br>(cat | pos)";
	$resultArray[3]['Value'] = "Starting<br>BIB number";
	$resultArray[4]['Value'] = "Name";
	$resultArray[5]['Value'] = "Gender";
	$resultArray[6]['Value'] = "Age";
	$resultArray[7]['Value'] = "Country";
	$i=0;
		while (isset($cpnoDisplayArray[$i])) {
			$resultArray[$i+8]['Value'] = str_replace($replace, $find, $cpnoDisplayArray[$i]);
			$resultArray[$i+8]['Distance'] = $distanceArray[$i];
			$i++;
		}
		
		//Avg speed
		$resultArray[$i+8]['Value'] = "Average<br>Speed";
		$i++;
	
	return $resultArray;
}

function DrawTheTable($headerArray, $tableArray, $display, $StartTime) {
	$returnStr = "";
	if ($display == 'live') {
		$returnStr .= "
		<div class='live_results_table_wrapper'>
			<table class='live_results_table'>
				<tbody>
					<tr class='table_header_row'>";
			$headerCounter = 0;
			while (isset($headerArray[$headerCounter]['Value'])) {
				
				$returnStr .= "	<th class='table_header_cell table_header_cell_col" . $headerCounter . "'>";
				$returnStr .= $headerArray[$headerCounter]['Value'];
				$returnStr .= "	</th>";
				
				$headerCounter++;
			}
				$returnStr .= "	</tr>";
				
			$rowCounter = 0;
			while (isset($tableArray[$rowCounter][0])) {
				$dataCounter = 0;
				$returnStr .= "	<tr class='table_row table_row_" . $rowCounter . " table_row_gender_" . $tableArray[$rowCounter][5] . "'>";
				while (isset($tableArray[$rowCounter][$dataCounter])) {
					$returnStr .= "	<td class='table_cell table_cell_col" . $dataCounter . "'>";
					if (($dataCounter > 7) && ($dataCounter < count($tableArray[0])-1)) {				
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
			</table>
		</div>";
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
		$returnStr .=  "<li><div class='subrace'>";
		$returnStr .= $row['Description'];
		$returnStr .= "<br/>";
		$returnStr .=  "<a class='live_results_link' href='" . $subrace_link .  "&display=live'>LIVE RESULTS</a>";
		$returnStr .= "<span class='separator_results_link'> | </span>";
		$returnStr .=  "<a class='official_results_link' href='" . $subrace_link .  "&display=final'>OFFICIAL RESULTS</a>";
		$returnStr .=  "</div></li>";
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

function display_country($lang) {

	
	$country_array_en = array(
		"00" => "[Please select one ...]",
		"AF" => "Afghanistan",
		"AL" => "Albania",
		"DZ" => "Algeria",
		"AS" => "American Samoa",
		"AD" => "Andorra",
		"AO" => "Angola",
		"AI" => "Anguilla",
		"AQ" => "Antarctica",
		"AG" => "Antigua and Barbuda",
		"AR" => "Argentina",
		"AM" => "Armenia",
		"AW" => "Aruba",
		"AU" => "Australia",
		"AT" => "Austria",
		"AZ" => "Azerbaijan",
		"BS" => "Bahamas",
		"BH" => "Bahrain",
		"BD" => "Bangladesh",
		"BB" => "Barbados",
		"BY" => "Belarus",
		"BE" => "Belgium",
		"BZ" => "Belize",
		"BJ" => "Benin",
		"BM" => "Bermuda",
		"BT" => "Bhutan",
		"BO" => "Bolivia",
		"BA" => "Bosnia and Herzegovina",
		"BW" => "Botswana",
		"BV" => "Bouvet Island",
		"BR" => "Brazil",
		"BQ" => "British Antarctic Territory",
		"IO" => "British Indian Ocean Territory",
		"VG" => "British Virgin Islands",
		"BN" => "Brunei",
		"BG" => "Bulgaria",
		"BF" => "Burkina Faso",
		"BI" => "Burundi",
		"KH" => "Cambodia",
		"CM" => "Cameroon",
		"CA" => "Canada",
		"CT" => "Canton and Enderbury Islands",
		"CV" => "Cape Verde",
		"KY" => "Cayman Islands",
		"CF" => "Central African Republic",
		"TD" => "Chad",
		"CL" => "Chile",
		"CN" => "China",
		"CX" => "Christmas Island",
		"CC" => "Cocos [Keeling] Islands",
		"CO" => "Colombia",
		"KM" => "Comoros",
		"CG" => "Congo - Brazzaville",
		"CD" => "Congo - Kinshasa",
		"CK" => "Cook Islands",
		"CR" => "Costa Rica",
		"HR" => "Croatia",
		"CU" => "Cuba",
		"CY" => "Cyprus",
		"CZ" => "Czech Republic",
		"CI" => "Côte d’Ivoire",
		"DK" => "Denmark",
		"DJ" => "Djibouti",
		"DM" => "Dominica",
		"DO" => "Dominican Republic",
		"NQ" => "Dronning Maud Land",
		"DD" => "East Germany",
		"EC" => "Ecuador",
		"EG" => "Egypt",
		"SV" => "El Salvador",
		"GQ" => "Equatorial Guinea",
		"ER" => "Eritrea",
		"EE" => "Estonia",
		"ET" => "Ethiopia",
		"FK" => "Falkland Islands",
		"FO" => "Faroe Islands",
		"FJ" => "Fiji",
		"FI" => "Finland",
		"FR" => "France",
		"GF" => "French Guiana",
		"PF" => "French Polynesia",
		"TF" => "French Southern Territories",
		"FQ" => "French Southern and Antarctic Territories",
		"GA" => "Gabon",
		"GM" => "Gambia",
		"GE" => "Georgia",
		"DE" => "Germany",
		"GH" => "Ghana",
		"GI" => "Gibraltar",
		"GR" => "Greece",
		"GL" => "Greenland",
		"GD" => "Grenada",
		"GP" => "Guadeloupe",
		"GU" => "Guam",
		"GT" => "Guatemala",
		"GG" => "Guernsey",
		"GN" => "Guinea",
		"GW" => "Guinea-Bissau",
		"GY" => "Guyana",
		"HT" => "Haiti",
		"HM" => "Heard Island and McDonald Islands",
		"HN" => "Honduras",
		"HK" => "Hong Kong SAR China",
		"HU" => "Hungary",
		"IS" => "Iceland",
		"IN" => "India",
		"ID" => "Indonesia",
		"IR" => "Iran",
		"IQ" => "Iraq",
		"IE" => "Ireland",
		"IM" => "Isle of Man",
		"IL" => "Israel",
		"IT" => "Italy",
		"JM" => "Jamaica",
		"JP" => "Japan",
		"JE" => "Jersey",
		"JT" => "Johnston Island",
		"JO" => "Jordan",
		"KZ" => "Kazakhstan",
		"KE" => "Kenya",
		"KI" => "Kiribati",
		"XK" => "Kosovo",
		"KW" => "Kuwait",
		"KG" => "Kyrgyzstan",
		"LA" => "Laos",
		"LV" => "Latvia",
		"LB" => "Lebanon",
		"LS" => "Lesotho",
		"LR" => "Liberia",
		"LY" => "Libya",
		"LI" => "Liechtenstein",
		"LT" => "Lithuania",
		"LU" => "Luxembourg",
		"MO" => "Macau SAR China",
		"MK" => "Macedonia",
		"MG" => "Madagascar",
		"MW" => "Malawi",
		"MY" => "Malaysia",
		"MV" => "Maldives",
		"ML" => "Mali",
		"MT" => "Malta",
		"MH" => "Marshall Islands",
		"MQ" => "Martinique",
		"MR" => "Mauritania",
		"MU" => "Mauritius",
		"YT" => "Mayotte",
		"FX" => "Metropolitan France",
		"MX" => "Mexico",
		"FM" => "Micronesia",
		"MI" => "Midway Islands",
		"MD" => "Moldova",
		"MC" => "Monaco",
		"MN" => "Mongolia",
		"ME" => "Montenegro",
		"MS" => "Montserrat",
		"MA" => "Morocco",
		"MZ" => "Mozambique",
		"MM" => "Myanmar [Burma]",
		"NA" => "Namibia",
		"NR" => "Nauru",
		"NP" => "Nepal",
		"NL" => "Netherlands",
		"AN" => "Netherlands Antilles",
		"NT" => "Neutral Zone",
		"NC" => "New Caledonia",
		"NZ" => "New Zealand",
		"NI" => "Nicaragua",
		"NE" => "Niger",
		"NG" => "Nigeria",
		"NU" => "Niue",
		"NF" => "Norfolk Island",
		"KP" => "North Korea",
		"VD" => "North Vietnam",
		"MP" => "Northern Mariana Islands",
		"NO" => "Norway",
		"OM" => "Oman",
		"PC" => "Pacific Islands Trust Territory",
		"PK" => "Pakistan",
		"PW" => "Palau",
		"PS" => "Palestinian Territories",
		"PA" => "Panama",
		"PZ" => "Panama Canal Zone",
		"PG" => "Papua New Guinea",
		"PY" => "Paraguay",
		"YD" => "People's Democratic Republic of Yemen",
		"PE" => "Peru",
		"PH" => "Philippines",
		"PN" => "Pitcairn Islands",
		"PL" => "Poland",
		"PT" => "Portugal",
		"PR" => "Puerto Rico",
		"QA" => "Qatar",
		"RO" => "Romania",
		"RU" => "Russia",
		"RW" => "Rwanda",
		"RE" => "Réunion",
		"BL" => "Saint Barthélemy",
		"SH" => "Saint Helena",
		"KN" => "Saint Kitts and Nevis",
		"LC" => "Saint Lucia",
		"MF" => "Saint Martin",
		"PM" => "Saint Pierre and Miquelon",
		"VC" => "Saint Vincent and the Grenadines",
		"WS" => "Samoa",
		"SM" => "San Marino",
		"SA" => "Saudi Arabia",
		"SN" => "Senegal",
		"RS" => "Serbia",
		"CS" => "Serbia and Montenegro",
		"SC" => "Seychelles",
		"SL" => "Sierra Leone",
		"SG" => "Singapore",
		"SK" => "Slovakia",
		"SI" => "Slovenia",
		"SB" => "Solomon Islands",
		"SO" => "Somalia",
		"ZA" => "South Africa",
		"GS" => "South Georgia and the South Sandwich Islands",
		"KR" => "South Korea",
		"ES" => "Spain",
		"LK" => "Sri Lanka",
		"SD" => "Sudan",
		"SR" => "Suriname",
		"SJ" => "Svalbard and Jan Mayen",
		"SZ" => "Swaziland",
		"SE" => "Sweden",
		"CH" => "Switzerland",
		"SY" => "Syria",
		"ST" => "São Tomé and Príncipe",
		"TW" => "Taiwan",
		"TJ" => "Tajikistan",
		"TZ" => "Tanzania",
		"TH" => "Thailand",
		"TL" => "Timor-Leste",
		"TG" => "Togo",
		"TK" => "Tokelau",
		"TO" => "Tonga",
		"TT" => "Trinidad and Tobago",
		"TN" => "Tunisia",
		"TR" => "Turkey",
		"TM" => "Turkmenistan",
		"TC" => "Turks and Caicos Islands",
		"TV" => "Tuvalu",
		"UM" => "U.S. Minor Outlying Islands",
		"PU" => "U.S. Miscellaneous Pacific Islands",
		"VI" => "U.S. Virgin Islands",
		"UG" => "Uganda",
		"UA" => "Ukraine",
		"SU" => "Union of Soviet Socialist Republics",
		"AE" => "United Arab Emirates",
		"GB" => "United Kingdom",
		"US" => "United States",
		"ZZ" => "Unknown or Invalid Region",
		"UY" => "Uruguay",
		"UZ" => "Uzbekistan",
		"VU" => "Vanuatu",
		"VA" => "Vatican City",
		"VE" => "Venezuela",
		"VN" => "Vietnam",
		"WK" => "Wake Island",
		"WF" => "Wallis and Futuna",
		"EH" => "Western Sahara",
		"YE" => "Yemen",
		"ZM" => "Zambia",
		"ZW" => "Zimbabwe",
		"AX" => "Åland Islands"
	);
	
	$country_array_mk = array(
		"00" => "[Изберете држава ...]",
		"AF" => "Авганистан",
		"AZ" => "Азербејџан",
		"AL" => "Албанија",
		"DZ" => "Алжир",
		"AS" => "Американска Самоа",
		"AD" => "Андора",
		"AO" => "Ангола",
		"AI" => "Ангвила",
		"AQ" => "Антартик",
		"AG" => "Антигва и Барбуда",
		"AR" => "Аргентина",
		"AM" => "Ерменија",
		"AW" => "Аруба",
		"AU" => "Австралија",
		"AT" => "Австрија",
		"BS" => "Бахами",
		"BH" => "Бахреин",
		"BD" => "Бангладеш",
		"BB" => "Барбадос",
		"BY" => "Белорусија",
		"BE" => "Белгија",
		"BZ" => "Белизе",
		"BJ" => "Бенин",
		"BM" => "Бермуда",
		"BT" => "Бутан",
		"BO" => "Боливија",
		"BA" => "Босна и Херцеговина",
		"BW" => "Боцвана",
		"BR" => "Бразил",
		"VG" => "Девствени Острови",
		"BN" => "Брунеи",
		"BG" => "Бугарија",
		"BF" => "Буркина Фасо",
		"BI" => "Бурунди",
		"KH" => "Камбоџа",
		"CM" => "Камерун",
		"CA" => "Канада",
		"CV" => "Кејп Верде",
		"KY" => "Кајмански Острови",
		"CF" => "Централна Афричка Република",
		"TD" => "Чад",
		"CL" => "Чиле",
		"CN" => "Кина",
		"CX" => "Божиќни Острови",
		"CO" => "Колумбија",
		"KM" => "Коморос",
		"CG" => "Конго",
		"CR" => "Коста Рика",
		"HR" => "Хрватска",
		"CU" => "Куба",
		"CY" => "Кипар",
		"CZ" => "Чешка",
		"CI" => "Брегот на Слоновата Коска",
		"DK" => "Данска",
		"DJ" => "Џибути",
		"DO" => "Доминиканска Република",
		"EC" => "Еквадор",
		"EG" => "Египет",
		"SV" => "Ел Салвадор",
		"GQ" => "Екваторијална Гвинеја",
		"ER" => "Еритреа",
		"EE" => "Естонија",
		"ET" => "Етиопија",
		"FK" => "Фалкландски Острови",
		"FO" => "Фарски Острови",
		"FJ" => "Фиџи",
		"FI" => "Финска",
		"FR" => "Франција",
		"GF" => "Француска Гвајана",
		"PF" => "Полинезија",
		"GA" => "Габон",
		"GM" => "Гамбија",
		"GE" => "Грузија",
		"DE" => "Германија",
		"GH" => "Гана",
		"GI" => "Гибралтар",
		"GR" => "Грција",
		"GL" => "Гренланд",
		"GD" => "Гренада",
		"GP" => "Гвадалупе",
		"GU" => "Гуам",
		"GT" => "Гватемала",
		"GN" => "Гвинеја",
		"GY" => "Гијана",
		"HT" => "Хаити",
		"HN" => "Хондурас",
		"HK" => "Хонг Конг",
		"HU" => "Унгарија",
		"IS" => "Исланд",
		"IN" => "Индија",
		"ID" => "Индонезија",
		"IR" => "Иран",
		"IQ" => "Ирак",
		"IE" => "Ирска",
		"IM" => "Ман (остров)",
		"IL" => "Израел",
		"IT" => "Италија",
		"JM" => "Јамајка",
		"JP" => "Јапонија",
		"JE" => "Џерзи",
		"JO" => "Јордан",
		"KZ" => "Казакхсан",
		"KE" => "Кенија",
		"KI" => "Кирибати",
		"XK" => "Косово",
		"KW" => "Кувајт",
		"KG" => "Киргистан",
		"LA" => "Лаос",
		"LV" => "Летонија",
		"LB" => "Либан",
		"LS" => "Лесото",
		"LR" => "Либерија",
		"LY" => "Либија",
		"LI" => "Лихтенштајн",
		"LT" => "Литванија",
		"LU" => "Луксембург",
		"MO" => "Макау",
		"MK" => "Македонија",
		"MG" => "Мадагаскар",
		"MW" => "Малави",
		"MY" => "Малезија",
		"MV" => "Малдиви",
		"ML" => "Мали",
		"MT" => "Малта",
		"MH" => "Маршалски Острови",
		"MQ" => "Мартиник",
		"MR" => "Мавританија",
		"MU" => "Маврициус",
		"MX" => "Мексико",
		"FM" => "Микронезија",
		"MD" => "Молдавија",
		"MC" => "Монако",
		"MN" => "Монголија",
		"ME" => "Црна Гора",
		"MS" => "Монсерат",
		"MA" => "Мароко",
		"MZ" => "Мозамбик",
		"MM" => "Мијанмар [Бурма]",
		"NA" => "Намибија",
		"NR" => "Науру",
		"NP" => "Непал",
		"NL" => "Холандија",
		"NC" => "Нова Каледонија",
		"NZ" => "Нов Зеланд",
		"NI" => "Никарагва",
		"NE" => "Нигер",
		"NG" => "Нигерија",
		"NF" => "Норфолк Острови",
		"KP" => "Северна Кореа",
		"NO" => "Норвешка",
		"OM" => "Оман",
		"PK" => "Пакистан",
		"PW" => "Палау",
		"PS" => "Палестински Територии",
		"PA" => "Панама",
		"PG" => "Папуа Нова Гвинеја",
		"PY" => "Парагвај",
		"PE" => "Перу",
		"PH" => "Филипини",
		"PL" => "Полска",
		"PT" => "Португалија",
		"PR" => "Порто Рико",
		"QA" => "Катар",
		"RO" => "Романија",
		"RU" => "Русија",
		"RW" => "Руанда",
		"WS" => "Самоа",
		"SM" => "Сан Марино",
		"SA" => "Саудиска Арабија",
		"SN" => "Сенегал",
		"RS" => "Србија",
		"SC" => "Сејшели",
		"SL" => "Сиера Леоне",
		"SG" => "Сингапур",
		"SK" => "Словачка",
		"SI" => "Словенија",
		"SO" => "Сомалија",
		"ZA" => "Јужна Африка",
		"KR" => "Јужна Кореа",
		"ES" => "Шпанија",
		"LK" => "Шри Ланка",
		"SD" => "Судан",
		"SR" => "Суринам",
		"SZ" => "Свазиленд",
		"SE" => "Шведска",
		"CH" => "Швајцарија",
		"SY" => "Сирија",
		"TW" => "Тајван",
		"TJ" => "Таџикистан",
		"TZ" => "Танзанија",
		"TH" => "Тајланд",
		"TG" => "Того",
		"TO" => "Тонга",
		"TT" => "Тринидад и Тобаго",
		"TN" => "Тунис",
		"TR" => "Турција",
		"TM" => "Туркменистан",
		"UG" => "Уганда",
		"UA" => "Украина",
		"AE" => "Обединети Арапски Емирати",
		"GB" => "Велика Британија",
		"US" => "САД",
		"UY" => "Уругвај",
		"UZ" => "Узбекистан",
		"VA" => "Ватикан",
		"VE" => "Венецуела",
		"VN" => "Виетнам",
		"EH" => "Западна Сахара",
		"YE" => "Јемен",
		"ZM" => "Замбија",
		"ZW" => "Зимбабве"
	);
	
	$collator = new Collator('en_US');
	$collator->asort($country_array_mk);
	$collator->asort($country_array_en);

	
	//asort($country_array_mk);
	//asort($country_array_en);
	if ($lang=='mk') {
		return $country_array_mk;
	} else {
		return $country_array_en;
	}

}

function getFullCountryName($shortCountry, $lang) {
	$countriesArray = display_country($lang);
	//$country_long = array_search($shortCountry,$countriesArray);
	//if ($country_long) {
	//	return $country_long;
	//} else {
	//	return $country_short;
	//}
	if (empty($countriesArray[$shortCountry])) {
		return $shortCountry;
	} else {
		return $countriesArray[$shortCountry];
	}
}

?>
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

Update: dodadi status:
- citaj go od baza
- prikazi uste edna kolona za nego, pred Start
- podesi gi brojacite sekade
- logika za prikaz:
	- ako ne pocnala trkata:
		Registered - nema zapis za start 
		Checked in - ima zapis za start
	- ako pocnala trkata:
		ako Status - OK:
			ako nema zapis za Finish:
			 Running
			ako ima zapis za Finish:
			 Finished.
		ako Status - DNS
		 DNS
		ako Status - DNF
		 DNF



*/





$TEST_MODE = true;


require 'conn.php';
$outhtml = "";
$drawSubRacesList = false;

	if (isset($_GET['RaceID'])) {
		$drawTable = true;
		$RaceID = $_GET['RaceID'];
	} else {
		$RaceID = 0;
		$drawTable = false;
	}
		

	

	$StartTime = NULL;
	$RaceGroupID = 2; //HARDCODED!!!
	$raceName = GetRaceName($RaceGroupID);
	$outhtml .= DrawHeader($raceName);
	
	$outhtml .= DrawTopnav($RaceID);	
	
		
	$query = "SELECT * FROM Races WHERE RaceGroupID = " . $RaceGroupID;
	$races_result = mysqli_query($conn,$query);
	$races = getRaces($races_result);
	
	if ($races_result->num_rows  == 1) {
		$row = $races_result->fetch_array(MYSQL_ASSOC);
		//mysql_data_seek($races_result, 0);
		//mysql_data_seek($racegroups_result, 0);
		$RaceID = $row['RaceID'];
		$drawTable = true;
	}
	
	if (!$drawTable) {
		$outhtml .= DrawSubRaces($races, $RaceGroupID);
	} else {
		

		
		//draw race info
		$RaceID = $_GET['RaceID'];
		$StartTime = getStartTime($races, $RaceID, $RaceGroupID);
		
		//var_dump($races);
		$outhtml .= DrawRaceInfo($RaceID, $races, $StartTime);
		
		//draw table here

		$headerIDsArray = array();
		$headerArray = getHeaderArray($RaceGroupID, $RaceID, $headerIDsArray);
		$tableArray = getTableArray($headerIDsArray, $RaceGroupID, $RaceID, $StartTime);
		$sortedTableArray = sortTableArray($tableArray, $headerArray, $StartTime);

		$outhtml .= DrawTheTable($headerArray, $sortedTableArray, $display, $StartTime, $RaceID);
		
	}	
		/*
		echo "<br><br>";
		echo "tableArray<br>";
		var_dump($tableArray);
		echo "<br><br>";
		echo "sortedtableArray<br>";
		var_dump($sortedTableArray);
		echo "<br><br>";
		*/

	


$outhtml .= "	<div class='footer'>
					<a href='https://www.catphones.com/?type=smartphones' target='_blank'>
						The 'Krali Marko Trails 2017' Live Tracking is brought to you by <img src='/assets/cat-logo-black.png' /> RaceTracker © 2017 All Rights Reserved
					</a>
				</div>";
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" data-reactid="4"/>
<title>Results - Krali Marko Trails 2017</title>
<meta name="Description" content="Live and Official results from the Krali Marko Trails 2017 race, Macedonia">
<meta name="Keywords" content="Krali Marko, Krali Marko Trails, KMT, KMT2017, Running, Trail Running, Ultra Marathon, Macedonia, Ultra Trail Running">
<?php if (!$TEST_MODE) { ?>
	<meta http-equiv="refresh" content="30"/>
<?php } ?>
<link href="https://fonts.googleapis.com/css?family=Cuprum|Fira+Sans+Extra+Condensed|Open+Sans+Condensed:300|Roboto+Condensed|Ubuntu+Condensed" rel="stylesheet">
<link rel="stylesheet" href="/style_results.css">
<?php echo loadCSS($races, $drawSubRacesList); ?>
<script src="results.js"></script>
</head>
<body>
<?php echo $outhtml; ?>
</body>
</html>

<?php

function DrawRaceInfo($RaceID, $races, $StartTime) {
	//var_dump($races);
	/*
	$time = '';
	$raceDescription = '';
	foreach ($races as $racesRow) {
		if ($racesRow['RaceID'] == $RaceID) {
			$time = $racesRow['StartTime'];
			$raceDescription = $racesRow['Description'];
		}
	}
	*/

	$raceInfoHtml = '';
	$raceInfoHtml .= '<div class="race-info race-info-' . $RaceID . '">';
		$raceInfoHtml .= '<img src="/assets/kmt-logo-' . $RaceID . '.png" />';
		$raceInfoHtml .= '<div class="race-info-text">';
			$raceInfoHtml .= '<span class="race-info-text1">' . $raceDescription . '</span><br>';
		//$raceInfoHtml .= 'Starting time: 22.09.2017 23:00:00 CET';
		$raceInfoHtml .= "</div>";

		$raceInfoHtml .= "<div class='race-info-text2'>";
		$raceInfoHtml .= DrawRaceInfoTable($RaceID, $StartTime, ', ');
		$raceInfoHtml .= '</div>';
	$raceInfoHtml .= '</div>';
	
	return $raceInfoHtml;
}

function DrawRaceInfoTable($RaceID, $StartTime, $dateSeparator) {
	
	$StartPassed = IsStartPassed($StartTime);

	require 'conn.php';
	/*
	$registeredRunners = 150;
	$started = 128;
	$running = 108;
	$finished = 12;
	$dnf = 8;
	$dns = 22;
	
	
	Logic:
	- registeredRunners - kolku trkaci imame vo bazata vkupno registrirani
	- started/checked in - kolku trkaci imame zapis deka si podignale starten paket (imaat entry vo CPEntries za check in na Start)
	- running - formula: started - finished - dnf
	- finished - kolku trkaci imaat entry za Finish(posledna kontrolna)
	- dnf - kolku imaat vo ActiveRacers, status DNF
	- dns - 2 nacini: 	Registered - Started
						status DNS vo ActiveRacers
	*/
	
	//Get values:
	
	//Registered Runners:
	$query = "SELECT Count(ActiveRacerID) AS Count FROM `ActiveRacers` WHERE RaceID='" . $RaceID . "';";
	$result = mysqli_query($conn,$query);
	if ($result->num_rows > 0) {
		$row = $result->fetch_array(MYSQL_ASSOC);
		$registeredRunners = $row['Count'];
	} else {
		$registeredRunners = 0;
	}
	
	//Started:
	$query = "	SELECT Count(Distinct EntryID) AS Count FROM `CPEntries`
				JOIN ActiveRacers ON CPEntries.ActiveRacerID = ActiveRacers.ActiveRacerID
				WHERE Valid = 1 AND CPID = 8 AND RaceID = '" . $RaceID . "';";
	$result = mysqli_query($conn,$query);
	if ($result->num_rows > 0) {
		$row = $result->fetch_array(MYSQL_ASSOC);
		$started = $row['Count'];
	} else {
		$started = 0;
	}
	
	//Finished:
	if ($StartPassed) {
		$query = "	SELECT Count(Distinct EntryID) AS Count FROM `CPEntries`
					JOIN ActiveRacers ON CPEntries.ActiveRacerID = ActiveRacers.ActiveRacerID
					WHERE Valid = 1 AND CPID = 9 AND RaceID = '" . $RaceID . "';";
		$result = mysqli_query($conn,$query);
		if ($result->num_rows > 0) {
			$row = $result->fetch_array(MYSQL_ASSOC);
			$finished = $row['Count'];
		} else {
			$finished = 0;
		}
	} else {
		$finished = 'N/A';
	}

	//dnf
	if ($StartPassed) {
		$query = "	SELECT Count(ActiveRacerID) as Count FROM `ActiveRacers`
					WHERE Status = 'DNF' AND RaceID = '" . $RaceID . "';";
		$result = mysqli_query($conn,$query);
		if ($result->num_rows > 0) {
			$row = $result->fetch_array(MYSQL_ASSOC);
			$dnf = $row['Count'];
		} else {
			$dnf = 0;
		}
	} else {
		$dnf = 'N/A';
	}
	
	//dns
	if ($StartPassed) {
		$dns = $registeredRunners - $started;
		if ($dns < 0) {
			$dns = 'N/A';
		}
	} else {
		$dns = 'N/A';
	}
	
	//running
	if ($StartPassed) {
		$running = $started - $finished - $dnf;
		if ($running < 0) {
			$running = 'N/A';
		}
	} else {
		$running = 'N/A';
	}
	
	if ($StartPassed) {
		$startedLabel = "Started";
		$startsAtLabel = "Started At";
		$startedClass = "started";
	} else {
		$startedLabel = "Checked In";
		$startsAtLabel = "Starts At";
		$startedClass = "not-started";
	}

	$raceInfoHtml = '';
	
	/*
	//timer
	if ($StartPassed) {
		$raceInfoHtml .=	'<div class="timer_wrapper"><div>Race starts in</div><span id="time_before_' . $RaceID . '">1:00:00</span></div>';
	} else {
		if ($running > 0) {
			$raceInfoHtml .=	'<div class="timer_wrapper"><div>Time elapsed</div><span id="time_after_' . $RaceID . '">1:00:00</span></div>';
		}
	}
	
	
	$raceInfoHtml .=	'<div>Race starts in <span id="time">05:00</span> minutes!</div>';
	$raceInfoHtml .= "<table class='race-info-table'><tbody>";
	*/		

		//$raceInfoHtml .= "<td class='race-info-table-cell-right' colspan=3>" . date_format(date_create($StartTime), 'l, d M Y, H:i:s\h') . " CET</td>";
			$raceInfoHtml .= "<tr>";
			
			if (!$StartPassed) {
				$raceInfoHtml .= "<td class='race-info-table-cell-left' colspan=1>" . $startsAtLabel . ":</td>";
				$raceInfoHtml .= "<td class='race-info-table-cell-right' colspan=3><span id='time_before_" . $RaceID . "'>0:00:00</span></td>";
			} else {
				$raceInfoHtml .= "<td class='race-info-table-cell-left' colspan=1>" . $startsAtLabel . ":</td>";
				$raceInfoHtml .= "<td class='race-info-table-cell-right' colspan=3><span id='time_after_" . $RaceID . "'>0:00:00</span></td>";

				if ($running > 0) {
					"<td class='race-info-table-cell-left' colspan=1>Started At:</td>";
					$raceInfoHtml .= "<td class='race-info-table-cell-right' colspan=3>" . date_format(date_create($StartTime), 'l' . $dateSeparator . 'd M Y' . $dateSeparator . 'H:i:s\h') . " CET</td>";
				}
			}
			
			$raceInfoHtml .= "</tr>";
		
		//$raceInfoHtml .= "<td class='race-info-table-cell-right' colspan=3>" . date_format(date_create($StartTime), 'l' . $dateSeparator . 'd M Y' . $dateSeparator . 'H:i:s\h') . " CET</td>";

		
		$raceInfoHtml .= "<tr>";
		$raceInfoHtml .= "<td class='race-info-table-cell-left'>Registered:</td>";
		$raceInfoHtml .= "<td class='race-info-table-cell-right'>" . $registeredRunners . "</td>";
		$raceInfoHtml .= "<td class='race-info-table-cell-left'>" . $startedLabel . ":</td>";
		$raceInfoHtml .= "<td class='race-info-table-cell-right'>" . $started . "</td>";
		$raceInfoHtml .= "</tr>";
		
		$raceInfoHtml .= "<tr class='race-info-row-" . $startedClass . "'>";
		$raceInfoHtml .= "<td class='race-info-table-cell-left'>Running:</td>";
		$raceInfoHtml .= "<td class='race-info-table-cell-right'>" . $running . "</td>";
		$raceInfoHtml .= "<td class='race-info-table-cell-left'>Finished:</td>";
		$raceInfoHtml .= "<td class='race-info-table-cell-right'>" . $finished . "</td>";
		$raceInfoHtml .= "</tr>";
		
		$raceInfoHtml .= "<tr class='race-info-row-" . $startedClass . "'>";
		$raceInfoHtml .= "<td class='race-info-table-cell-left'>DNF:</td>";
		$raceInfoHtml .= "<td class='race-info-table-cell-right'>" . $dnf . "</td>";
		$raceInfoHtml .= "<td class='race-info-table-cell-left'>DNS:</td>";
		$raceInfoHtml .= "<td class='race-info-table-cell-right'>" . $dns . "</td>";
		$raceInfoHtml .= "</tr>";
			
	$raceInfoHtml .= "</tbody></table>";
	
	return $raceInfoHtml;

}

function IsStartPassed($StartTime) {
	if (time() > strtotime($StartTime)) {
		return true;
	} else {
		return false;
	}
}

function loadCSS($races, $drawSubRacesList) {
	$styleStr = "";
	if ($drawSubRacesList) {
		if (count($races) > 0) {
			$widthPercentage = 100/count($races) - 1;
			$styleStr .= "<style type='text/css'>";
			$styleStr .= ".subraces_list ul li {width: " . $widthPercentage . "%;}";
			$styleStr .= "</style>";
		}
	}
	return $styleStr;
}

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

function DrawTopnav($RaceID) {
	
	$topnavHtml = '';
	$topnavHtml .= '		<div class="topnav" id="myTopnav">';
	//$topnavHtml .= '			<a class="topnav-chose-race" href="http://app.trex.mk/results.php">Choose a race                 </a>';
	$topnavHtml .= '			<a class="topnav-chose-race" href="javascript:void(0);" class="icon" onclick="myFunction()">Choose a race</a>';
	$classSelected = '';
	if ($RaceID == 0) $classSelected = ' topnav-selected';
	$topnavHtml .= '					  <a class="topnav-overview' . $classSelected . '" href="?#">Overview</a>';
	$classSelected = '';
	if ($RaceID == 4) $classSelected = ' topnav-selected';
	$topnavHtml .= '					  <a class="topnav-110k' . $classSelected . '" href="?RaceGroupID=2&RaceID=4">Krali Marko Ultra Trail 110K</a>';
	$classSelected = '';
	if ($RaceID == 5) $classSelected = ' topnav-selected';
	$topnavHtml .= '					  <a  class="topnav-65k' . $classSelected . '" href="?RaceGroupID=2&RaceID=5">Kozjak Trail 65K</a>';
	$classSelected = '';
	if ($RaceID == 6) $classSelected = ' topnav-selected';
	$topnavHtml .= '					  <a  class="topnav-31k' . $classSelected . '" href="?RaceGroupID=2&RaceID=6">Kamena Baba Trail 31K</a>';
	$classSelected = '';
	if ($RaceID == 7) $classSelected = ' topnav-selected';
	$topnavHtml .= '					  <a  class="topnav-16k' . $classSelected . '" href="?RaceGroupID=2&RaceID=7">Treskavec Trail 16K</a>';
	$topnavHtml .= '					  <a href="javascript:void(0);" class="icon" onclick="myFunction()">&#9776;</a>';
	$topnavHtml .= '				</div>';
	return $topnavHtml;
}

function DrawHeader($raceName) {
	
	$returnStr = "";
	$returnStr .= "<div class='header'>";
	$returnStr .= "<div class='header-race-logo-wrapper'><a href='?#'><img class='header-race-logo' src='/assets/kmt-logo.png' /></div>";
	$returnStr .= "	<div class='header-headline-wrapper'><h2 class='headline'>" . $raceName . "<br>LIVE Results</h2></a></div>";
	$returnStr .= "<div class='header-sponsor-logo-wrapper'><a href='https://www.catphones.com/?type=smartphones' target='_blank'><span>Powered by</span><br><img class='header-sponsor-logo' src='/assets/cat-logo.png' /></a></div>";
	$returnStr .= "</div>";
	
	return $returnStr;
}

function getTableArray($headerIDsArray, $RaceGroupID, $RaceID, $StartTime) {
	
	require 'conn.php';

	//retrieve entries
	$q = "SELECT CPNo, ActiveRacers.BIB, Time, ActiveRacers.ActiveRacerID FROM `CPEntries`
		JOIN ActiveRacers ON ActiveRacers.BIB = CPEntries.BIB
		WHERE Valid = 1 AND RaceID = " . $RaceID;

	$entries_result = mysqli_query($conn,$q);	
	
	while($row = $entries_result->fetch_array(MYSQL_ASSOC)) {
		$entriesArray[] = $row;
	}	

	//retrieve racers
	$q = "SELECT ActiveRacerID, CONCAT(FirstName, ' ', LastName) AS Name, Gender, Country, BIB, DateOfBirth, Status, TeamName FROM `ActiveRacers`
			JOIN Racers ON ActiveRacers.RacerID = Racers.RacerID
			JOIN Teams ON Racers.TeamID = Teams.TeamID
			WHERE RaceID = " . $RaceID . " ORDER BY BIB";
	$racers_result = mysqli_query($conn,$q);
	if ($racers_result->num_rows > 0) {
		$racersCounter = 0;
		while($row = $racers_result->fetch_array(MYSQL_ASSOC)) {
			$racersArray[] = $row;

			$returnArray[$racersCounter][0] = $racersCounter+1;
			$returnArray[$racersCounter][1] = $racersCounter+1;
			$returnArray[$racersCounter][2] = $racersCounter+1;
			$returnArray[$racersCounter][3] = $row['BIB'];
			$returnArray[$racersCounter][4] = $row['Name'];
			$returnArray[$racersCounter][5] = $row['Gender'];
			$returnArray[$racersCounter][6] = getAge($row['DateOfBirth'], $StartTime);
			$returnArray[$racersCounter][7] = $row['TeamName'];	
			$returnArray[$racersCounter][8] = "<img src='/flags-mini/" . strtolower($row['Country']) . ".png'/> " . getFullCountryName($row['Country'],'en');
			$returnArray[$racersCounter][9] = $row['Status'];
			
			$CPCounter = 0;
			while (isset($headerIDsArray[$CPCounter])) {
				$entryTime = findTheEntryTimeByBIB($row['BIB'],$headerIDsArray[$CPCounter],$entriesArray);
				$returnArray[$racersCounter][$CPCounter+10] = $entryTime;
				$CPCounter++;
			}
			
			//avg speed
			$returnArray[$racersCounter][$CPCounter+10] = "--";
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
	
	/*
	echo "<br><br>tablearray<br><br>";
	var_dump($tableArray);
	echo "<br><br>";
	*/
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
	for ($i=count($tableArray[0])-1;$i>9;$i--) {
		//4 zemame predvid samo ako postoi vreme (ne e prazno)
		//5 stavi gi vo privremena niza ($sortColumn) samo tie sto imaat vreminja
		$sortColumn = array ();
		$stColumnCount = 0;
		for ($j=0;$j<count($tableArray);$j++) {

			if (($tableArray[$j][$i] != "--") && ($tableArray[$j][$i] != "") && (!is_null($tableArray[$j][$i]))) {
				
				$sortColumn[$stColumnCount]['BIB'] = $tableArray[$j][3];
				$sortColumn[$stColumnCount]['Time'] = $tableArray[$j][$i];
				$sortColumn[$stColumnCount]['Timestamp'] = strtotime($tableArray[$j][$i]);
				$distance = $headerArray[$i]['Distance'];
				if (($distance != null) && ($distance != 0)) {
					$sortColumn[$stColumnCount]['AvgSpeed'] = getAvgSpeed($sortColumn[$stColumnCount]['Timestamp'], $StartTime, $distance);
				} else {
					$sortColumn[$stColumnCount]['AvgSpeed'] = '--';
				}
				$stColumnCount++;
				
			}
		}

		//6 sortiraj ja $sortColumn od najmal do najgolem spored 'Time'
		usort($sortColumn, function($a, $b) {
			return strcmp($a['Timestamp'], $b['Timestamp']);
		});

		//7 proveri go sekoj clen od 0 nagore dali postoi vo $sortTotal. Ako ne, dodadi go. Ako postoi, ignore
		if ($stColumnCount > 0) {
			foreach ($sortColumn as &$row) {
				if (!BIBExistsInArray($row['BIB'],$sortTotal)) {
					$sortTotal[$stTotalCount]['BIB'] = $row['BIB'];
					$sortTotal[$stTotalCount]['Time'] = $row['Time'];
					$sortTotal[$stTotalCount]['AvgSpeed'] = $row['AvgSpeed'];
					$stTotalCount++;
				}
			}
		}
	}
	/*
	echo "<br><br>sortTotal<br><br>";
	var_dump($sortTotal);
	echo "<br><br>";
	*/
	//8 Otkako $sortTotal e formirana, nov for ciklus koj sto ke ja sortira $tableArray spored redosledot vo $sortTotal.
	
	$sortedTableArray = array ();
	$sortedIndex = 0;
	
	$positionMale = 1;
	$positionFemale = 1;
	
	$positionM1 = 1;
	$positionM2 = 1;
	
	$positionF1 = 1;
	$positionF2 = 1;

	foreach ($sortTotal as &$sortTotalRow) {
		$found = false;
		foreach ($tableArray as &$tableArrayRow) {
			
			if (!$found && $sortTotalRow['BIB'] == $tableArrayRow[3]) {
				$sortedTableArray[$sortedIndex] = $tableArrayRow;
				//Position (total)
				$totalPos = $sortedIndex+1;
				$sortedTableArray[$sortedIndex][0] = '<span class="position-table-text">' . $totalPos . '</span>';
				//Position (gender)
				if ($sortedTableArray[$sortedIndex][5] == 'F') {
					$sortedTableArray[$sortedIndex][1] = 'F | <span class="position-table-text">' . $positionFemale . '</span>';
					$positionFemale++;
				} else {
					$sortedTableArray[$sortedIndex][1] = 'M | <span class="position-table-text">' . $positionMale . '</span>';
					$positionMale++;
				}
				
				//Position (Category)
				if (((int)$sortedTableArray[$sortedIndex][6] < 50) && ($sortedTableArray[$sortedIndex][5] == 'F')) {
					//F1
					$sortedTableArray[$sortedIndex][2] = 'F(18-50) | <span class="position-table-text">' . $positionF1 . '</span>';
					$positionF1++;
				}
				if (((int)$sortedTableArray[$sortedIndex][6] >= 50) && ($sortedTableArray[$sortedIndex][5] == 'F')) {
					//F2
					$sortedTableArray[$sortedIndex][2] = 'F(50+) | <span class="position-table-text">' . $positionF2 . '</span>';
					$positionF2++;
				}
				if (((int)$sortedTableArray[$sortedIndex][6] < 50) && ($sortedTableArray[$sortedIndex][5] == 'M')) {
					//M1
					$sortedTableArray[$sortedIndex][2] = 'M(18-50) | <span class="position-table-text">' . $positionM1 . '</span>';
					$positionM1++;
				}
				if (((int)$sortedTableArray[$sortedIndex][6] >= 50) && ($sortedTableArray[$sortedIndex][5] == 'M')) {
					//M2
					$sortedTableArray[$sortedIndex][2] = 'M(50+) | <span class="position-table-text">' . $positionM2 . '</span>';
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
				
				//Position (total)
				$totalPos = $sortedIndex+1;
				$sortedTableArray[$sortedIndex][0] = '<span class="position-table-text">' . $totalPos . '</span>';
				//Position (gender)
				if ($sortedTableArray[$sortedIndex][5] == 'F') {
					$sortedTableArray[$sortedIndex][1] = 'F | <span class="position-table-text">' . $positionFemale . '</span>';
					$positionFemale++;
				} else {
					$sortedTableArray[$sortedIndex][1] = 'M | <span class="position-table-text">' . $positionMale . '</span>';
					$positionMale++;
				}
				
				//Position (Category)
				if (((int)$sortedTableArray[$sortedIndex][6] < 50) && ($sortedTableArray[$sortedIndex][5] == 'F')) {
					//F1
					$sortedTableArray[$sortedIndex][2] = 'F(18-50) | <span class="position-table-text">' . $positionF1 . '</span>';
					$positionF1++;
				}
				if (((int)$sortedTableArray[$sortedIndex][6] >= 50) && ($sortedTableArray[$sortedIndex][5] == 'F')) {
					//F2
					$sortedTableArray[$sortedIndex][2] = 'F(50+) | <span class="position-table-text">' . $positionF2 . '</span>';
					$positionF2++;
				}
				if (((int)$sortedTableArray[$sortedIndex][6] < 50) && ($sortedTableArray[$sortedIndex][5] == 'M')) {
					//M1
					$sortedTableArray[$sortedIndex][2] = 'M(18-50) | <span class="position-table-text">' . $positionM1 . '</span>';
					$positionM1++;
				}
				if (((int)$sortedTableArray[$sortedIndex][6] >= 50) && ($sortedTableArray[$sortedIndex][5] == 'M')) {
					//M2
					$sortedTableArray[$sortedIndex][2] = 'M(50+) | <span class="position-table-text">' . $positionM2 . '</span>';
					$positionM2++;
				}
		
				$sortedIndex++;
		}
		


	}
	//fix status and start column display
	foreach ($sortedTableArray as &$row) {
		//start column
		if ($row[10] != '--') {
			$row[10] = "<img src='/assets/success_20x20.png' />";
		}
		
		//status column
		//$newStatusValue = "<div class='status-wrapper'>";
		if (IsStartPassed($StartTime)) {
			if ($row[9] == 'OK') {
				//check if Finished
				if ($row[count($row)-2] == '--') {
					$row[9] = "Running";
				} else {
					$row[9] = "Finished";
				}
			}
		} else {
			if ($row[10] == '--') {
				$row[9] = "Not Checked In";
			} else {
				$row[9] = "Checked In";
			}
		}
		//$newStatusValue .= "</div>";
	}
	return $sortedTableArray;
}

function BIBExistsInArray($BIB, $Array) {

	$found = false;
	foreach ($Array as &$row) {
		if (!$found) {
			if ($row['BIB'] == $BIB) {
				$found = true;
			}
		}
	}
	return $found;
}


function findTheEntryTimeByBIB($BIB, $CPNo, $entriesArray) {
	$i=0;
	$found = false;
	while(isset($entriesArray[$i])) {
		if (!$found) {
			if (($entriesArray[$i]['BIB'] == $BIB) && ($entriesArray[$i]['CPNo'] == $CPNo)) {
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

function findTheEntryTimeByActRacerID($ActiveRacerID, $CPNo, $entriesArray) {
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

function getHeaderArray($RaceGroupID, $RaceID, &$headerIDsArray) {
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
			//$cpnoArray[$i] = str_replace($find, $replace, $row["CPNo"]);
			$headerIDsArray[$i] = str_replace($find, $replace, $row["CPNo"]);
			$distanceArray[$i] = $row['Distance'];
			$i++;
		}

		
	} else {
		//return null;
	}

	sort($distanceArray);
	sort($cpnoDisplayArray);
	sort($headerIDsArray);
	$i=0;

	//add other fields in front
	$resultArray[0]['Value'] = "General<br>Rank";
	$resultArray[1]['Value'] = "by Gender<br>(gen | pos)";
	$resultArray[2]['Value'] = "by Category<br>(cat | pos)";
	$resultArray[3]['Value'] = "Starting<br>BIB<br>number";
	$resultArray[4]['Value'] = "Name";
	$resultArray[5]['Value'] = "Gender";
	$resultArray[6]['Value'] = "Age";
	$resultArray[7]['Value'] = "Team";
	$resultArray[8]['Value'] = "Country";
	$resultArray[9]['Value'] = "Status";
	$i=0;
		while (isset($cpnoDisplayArray[$i])) {
			if (substr($cpnoDisplayArray[$i], 0, 2) == '00' || substr($cpnoDisplayArray[$i], 0, 2) == '99') {
				$resultArray[$i+10]['Value'] = str_replace($replace, $find, $cpnoDisplayArray[$i]);
			} else {
				$resultArray[$i+10]['Value'] = "CP " . $cpnoDisplayArray[$i];
			}
			$resultArray[$i+10]['Distance'] = $distanceArray[$i];
			$resultArray[$i+10]['CPNo'] = $headerIDsArray[$i];
			$i++;
		}
		
		//Avg speed
		$resultArray[$i+10]['Value'] = "Average Speed";
		$i++;
	
	return $resultArray;
}

function DrawTheTable($headerArray, $tableArray, $display, $StartTime, $RaceID) {

	$returnStr = "";
	$returnStr .= "
	<div class='live_results_table_wrapper'>
		<table class='live_results_table'>
			<tbody>
				<tr class='table_header_row table_header_row_race" . $RaceID . "'>";
		$headerCounter = 0;
		while (isset($headerArray[$headerCounter]['Value'])) {
			
			$returnStr .= "	<th class='table_header_cell table_header_cell_col" . $headerCounter . "'>";
			$returnStr .= $headerArray[$headerCounter]['Value'];
			if (isset($headerArray[$headerCounter]['Distance']) && $headerArray[$headerCounter]['Distance'] != 0) {
				$returnStr .= "<br>(" . $headerArray[$headerCounter]['Distance'] . " km)";
			}	
			$returnStr .= "	</th>";
			
			$headerCounter++;
		}
			$returnStr .= "	</tr>";
			
		$rowCounter = 0;
		while (isset($tableArray[$rowCounter][0])) {
			$dataCounter = 0;
			$returnStr .= "	<tr class='table_row table_row_race_" . $RaceID . " table_row_" . $rowCounter . " table_row_gender_" . $tableArray[$rowCounter][5] . "'>";
			while (isset($tableArray[$rowCounter][$dataCounter])) {
				$returnStr .= "	<td class='table_cell table_cell_col" . $dataCounter;
				if (isset($headerArray[$dataCounter]['CPNo'])) {
					$returnStr .= " table_cell_cpno" . $headerArray[$dataCounter]['CPNo'];
				}
				if ($dataCounter == 9) {
					$statusClass = ' cell_status';
					switch ($tableArray[$rowCounter][$dataCounter]) {
					case 'Checked In':
						$statusClass .= ' cell_status_checkedin';
						break;
					case 'Not Checked In':
						$statusClass .= ' cell_status_notcheckedin';
						break;
					case 'Running':
						$statusClass .= ' cell_status_running';
						break;
					case 'Finished':
						$statusClass .= ' cell_status_finished';
						break;
					case 'DNF':
						$statusClass .= ' cell_status_dnf';
						break;
					case 'DNS':
						$statusClass .= ' cell_status_dns';
						break;						
					default:
						$statusClass .= ' cell_status_other';
					}
					$returnStr .= $statusClass;
				}
				
				
				$returnStr .= "'>";
				if (($dataCounter > 10) && ($dataCounter < count($tableArray[0])-1)) {				
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

	return $returnStr;
}

function DrawSubRaces($races, $RaceGroupID) {
	
	$returnStr = "";
	
	$returnStr .= "<div class='subraces_list'>";
	$returnStr .=  "<ul>";

	
	foreach ($races as &$row) {
		$actual_link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
		$subrace_link = $actual_link . "?RaceGroupID=" . $RaceGroupID . "&RaceID=" . $row['RaceID'];
		$returnStr .=  "<li><div class='subrace subrace_" . $row['RaceID'] . "'>";
		//$returnStr .=  "<a class='live_results_link' href='" . $subrace_link .  "'>";
		$returnStr .= "<img class='subrace_logo' src='/assets/kmt-logo-" . $row['RaceID'] . "a.png' />";
		$returnStr .= DrawRaceInfoTable($row['RaceID'], $row['StartTime'], '\<\b\r\>');
		$returnStr .= "<div class='subrace_text subrace_text_" . $row['RaceID'] . "'>";
		$returnStr .=  "<a class='live_results_link' href='" . $subrace_link .  "'>";
		//$returnStr .= $row['Description'];
		//$returnStr .= "<br/>";
		$returnStr .=  "LIVE RESULTS</a>";
		$returnStr .=  "</div>";
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
	return "<span class='avg-speed-kmh'>" . number_format($kmh,2) . " km/h</span><span class='avg-speed-minkm'> (" . $minkm_str . " min/km)</span>";
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
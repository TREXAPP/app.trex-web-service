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

	
	
	$RaceGroupID = $_GET['RaceGroupID'];
	$raceName = GetRaceName($RaceGroupID);
	echo DrawHeader($raceName);

	
	$query = "SELECT * FROM Races WHERE RaceGroupID = " . $RaceGroupID;
	$races_result = mysqli_query($conn,$query);
	 
	echo DrawSubRacesList($races_result, $RaceGroupID);
	
		
	$drawTable = false;
	if ($races_result->num_rows  == 1) {
		$row = $racegroups_result->fetch_array(MYSQL_ASSOC);
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
		$headerIDsArray = array();
		$headerArray = getHeaderArray($RaceGroupID, $RaceID, $display, $headerIDsArray);
		$tableArray = getTableArray($headerIDsArray, $RaceGroupID, $RaceID, $display);
		sortTableArray($tableArray);
		echo DrawTheTable($headerArray, $tableArray, $display);
	}
	/*
	*/
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

function getTableArray($headerIDsArray, $RaceGroupID, $RaceID, $display) {
	
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
				$resultArray[0] = "Position";
				$resultArray[1] = "Starting number";
				$resultArray[2] = "Name";
				$resultArray[3] = "Gender";
				$resultArray[4] = "Country";
			*/
			
			$returnArray[$racersCounter][0] = $racersCounter+1;
			$returnArray[$racersCounter][1] = $row['BIB'];
			$returnArray[$racersCounter][2] = $row['Name'];
			$returnArray[$racersCounter][3] = $row['Gender'];
			$returnArray[$racersCounter][4] = $row['Country'];
			
			$CPCounter = 0;
			while (isset($headerIDsArray[$CPCounter])) {
				
				
				
				$returnArray[$racersCounter][$CPCounter+5] = findTheEntryTime($row['ActiveRacerID'],$headerIDsArray[$CPCounter],$entriesArray);
	
				$CPCounter++;
			}
			
			$racersCounter++;
		}	

		//return $row['RaceGroupName'];
	} else {
		//nesto...
	}

	return $returnArray;
}

function sortTableArray(&$tableArray) {
	var_dump($tableArray);
	
	
	
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
	for ($i=count($tableArray[0])-1;$i>4;$i--) {
		echo "<br>kolona: " . $tableArray[0][$i] . "<br>";		
		//4 zemame predvid samo ako postoi vreme (ne e prazno)
		//5 stavi gi vo privremena niza ($sortColumn) samo tie sto imaat vreminja
		$sortColumn = array ();
		$stColumnCount = 0;
		for ($j=0;$j<count($tableArray);$j++) {
			echo "<br>red: " . $j . "<br>";
			if ($tableArray[j][i] != "--" || $tableArray[j][i] != "" || !is_null($tableArray[j][i])) {
				$sortTotal[$stColumnCount]['BIB'] = $tableArray[j][1];
				$sortTotal[$stColumnCount]['Time'] = $tableArray[j][i];
				$stColumnCount++;
			}
			
			//6 sortiraj ja taa niza od najmal do najgolem
			//TODO
		}
	}
	/*
	*/
}

function findTheEntryTime($ActiveRacerID, $CPNo, $entriesArray) {
	$i=0;
	$found = false;
	while(isset($entriesArray[$i])) {
		if (!$found) {
			if (($entriesArray[$i]['ActiveRacerID'] == $ActiveRacerID) && ($entriesArray[$i]['CPNo'] == $CPNo)) {
				$found == true;
				return substr($entriesArray[$i]['Time'],11);
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
	$q = "SELECT CONCAT(CPNo, ' ', CPName) AS CPNoDisplay, CPNo FROM `RacesControlPoints` 
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
			$i++;
		}

		
	} else {
		//return null;
	}
	sort($cpnoDisplayArray);
	sort($cpnoArray);
		$i=0;
		while (isset($cpnoArray[$i])) {
			$headerIDsArray[$i] = str_replace($replace, $find, $cpnoArray[$i]);
			$i++;
		}
	//add other fields in front
	$resultArray[0] = "Position";
	$resultArray[1] = "Starting number";
	$resultArray[2] = "Name";
	$resultArray[3] = "Gender";
	$resultArray[4] = "Country";
	$i=0;
		while (isset($cpnoDisplayArray[$i])) {
			$resultArray[$i+5] = str_replace($replace, $find, $cpnoDisplayArray[$i]);
			$i++;
		}
	
	return $resultArray;
}

function DrawTheTable($headerArray, $tableArray, $display) {
	$returnStr = "";
	if ($display == 'live') {
		$returnStr .= "
		<table class='live_results_table'>
			<tbody>
				<tr>";
		$headerCounter = 0;
		while (isset($headerArray[$headerCounter])) {
			$returnStr .= "	<th>";
			$returnStr .= $headerArray[$headerCounter];
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
				$returnStr .= $tableArray[$rowCounter][$dataCounter];
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

function DrawSubRacesList($races_result, $RaceGroupID) {
	$returnStr = "";
	$returnStr .= "<div class='subraces_list'>";
	
	$returnStr .=  "<ul>";
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
		$returnStr .=  "</ul>";
	
	
	$returnStr .= "</div>";
	
	return $returnStr;
}

?>
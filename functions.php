<?php

/****************** USER CONTROL FUNCTIONS *******************/

function getAuthLevel() {
	if (!empty($_SESSION["AuthLevel"])) {
		return $_SESSION["AuthLevel"];
	} else {
		return 0;
	}
}

function isLoggedIn() {
	if (!empty($_SESSION["Logged"])) {
		if ($_SESSION["Logged"] == "true") {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function LogIn($user, $pass) {
	$authLevel = checkUser($user, $pass);
	if ($authLevel) {

		session_start();
		$_SESSION["AuthLevel"] = $authLevel;
		$_SESSION["Logged"] = "true";
		$_SESSION["UserLogged"] = $user;
		return true;
	} else {
		return false;
	}
}

function LogOut () {
	$_SESSION["Logged"] = "false";
	$_SESSION["AuthLevel"] = 0;
	$_SESSION = array ();
	session_unset();
	session_destroy();
}

function checkUser($user, $pass) {
	//HARDCODED!!!
	if ($user == 'admin' && $pass == 'maraton') {
		return 1;
	} else {
		return 0;
	}
}

function redirectToPage () {
	//$_SESSION['msg'] = $msg;
	switch (getAuthLevel()) {
			case 1:
				header('Location: http://app.trex.mk/AdminPanel.php');
				exit;
				break;
			default:
				header('Location: http://app.trex.mk/LoginPanel.php');
				exit;
		}
	

}

/****************** Draw User Panel **********************/

function DrawHead($page) {
	$returnStr = "";
	
	$returnStr .= '
	<!DOCTYPE html>
	<html>
	<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" data-reactid="4"/>
	<title>' . $page . ' - Krali Marko Trails 2017</title>
	<link href="https://fonts.googleapis.com/css?family=Cuprum|Fira+Sans+Extra+Condensed|Open+Sans+Condensed:300|Roboto+Condensed|Ubuntu+Condensed" rel="stylesheet">
	<link rel="stylesheet" href="/style_admin.css">
	<script src="admin.js"></script>
	</head>
	<body> ';
	
	return $returnStr;
}

function DrawHeader($title) {

	$returnStr = "";
	
	$returnStr .= "<div class='userHeaderWrapper'>";
	
	$returnStr .= "<div class='Headline'>";
	$returnStr .= $title;
	//vamu nekoj naslov, header, logo, bilo sto
	$returnStr .= "</div>"; //headline
	
	
	$returnStr .= "<div class='logedInInfo'>";
	if (isLoggedIn()) {
		$returnStr .= "Логиран корисник: <b>" . $_SESSION["UserLogged"] . "</b> ";
		/*
		switch (getAuthLevel()) {
			case 1:
				$returnStr .= "(Администратор)";
				break;
			case 2:
				$returnStr .= "(Едитор)";
				break;
			case 3:
				$returnStr .= "(Корисник)";
				break;
			default:
				$returnStr .= "(Корисник)";
		}
		*/
		$returnStr .= DrawLogOutBtn();
	} else {
		$returnStr .= "Нема логирано корисник";
	}
	$returnStr .= "</div>"; //logedInInfo
	
	$returnStr .= "</div>"; //userHeaderWrapper

	return $returnStr;
}

function DrawLogOutBtn() {
	$returnStr = "";

	//$returnStr .= "<form action=".get_permalink()." method='post'>";
	
	$returnStr .= "<form action='http://app.trex.mk/AdminPanel.php' method='post'>";

	$returnStr .= "<input type='hidden' name='LogOut' value='true'>";
	$returnStr .= "<input type='submit' value='Одлогирај се'>";
	$returnStr .= "</form>";
	return $returnStr;
}

function DrawContent() {

	$returnStr = "";
	//ако нема логирано корисник, цртај форма за логирање
	//ако има логирано цртај различен панел во зависност од тоа каков корисник е логиран
	
	
	//ako ima poraka od post, prikazi ja
	/*
	if (!empty($msg)) {
		$returnStr .= '<div class="msgPost">'.$msg.'</div>';
	}
	*/
	if (isLoggedIn()) {
		//proveri authLevel
		//TODO
		$returnStr = "";
		
		if (!empty($_SESSION["AuthLevel"])) {
			switch (getAuthLevel()) {
			case 1:
				$returnStr .= drawAdminPage();
				break;
			case 2:
				$returnStr .= drawEditorPage();
				break;
			case 3:
				$returnStr .= drawUserPage();
				break;
			default:
				$returnStr .= drawUserPage();
			}
		}
	} else {
		$returnStr .= DrawMsg();
		//crtaj forma za logiranje
	$returnStr .= "<form action='#' method='post'>";

  $returnStr .= '<div class="loginFormContainer">';
  $returnStr .= '<div class="loginFormEntry loginFormUser">';
  $returnStr .= '<label><b>Корисник</b></label>';
  $returnStr .= '<input type="text" placeholder="Внеси корисник" name="user" required>';
  $returnStr .= '</div>';
  $returnStr .= '<div class="loginFormEntry loginFormPass">';
  $returnStr .= '<label><b>Лозинка</b></label>';
  $returnStr .= '<input type="password" placeholder="Внеси лозинка" name="pass" required>';
  $returnStr .= '</div>';
  $returnStr .= "<input type='hidden' name='LogIn' value='true'>";

  $returnStr .= '<input type="submit" value="Внеси">';
  $returnStr .= '</div>';
  $returnStr .= '</form>';
	}
	return $returnStr;
}

function DrawFooter() {
	$returnStr = "";
	
	
	//zatvori tagovi
	$returnStr .= "</body></html>";
	return $returnStr;

}

function drawAdminPage() {
	$returnStr = "";
	$returnStr .= "<div class='AdminPageWrapper'>";
	$returnStr .= drawAdminMenu();
	$returnStr .= drawAdminContent();
	$returnStr .= "</div>";
	
	return $returnStr;
}

function drawAdminMenu() {
	$returnStr = "";
	
	$returnStr .= "<div class='AdminMenuWrapper'>";
	
	$returnStr .= DrawMenuItem("Проблематични","urgent");
	$returnStr .= DrawMenuItem("Внесови од контролни","inputs");
	$returnStr .= DrawMenuItem("Означи старт","start");
	$returnStr .= DrawMenuItem("Внеси нов тркач","newrunner");
	$returnStr .= DrawMenuItem("Тркачи на 110K","runners110");
	$returnStr .= DrawMenuItem("Тркачи на 65K","runners65");
	$returnStr .= DrawMenuItem("Тркачи на 31K","runners31");
	$returnStr .= DrawMenuItem("Тркачи на 16K","runners16");

	$returnStr .= DrawMenuItem("Останато","other");

	$returnStr .= "</div>";
	
	return $returnStr;
}

function DrawMenuItem($menutxt,$sufix) {
	$returnStr = "";
	

	
	$menulink = "http://app.trex.mk/AdminPanel.php?adminpage=".$sufix;
	
	$currentlink = getCurrentUrl();

	$returnStr .= "<a href='".$menulink."'>";
	$returnStr .= "<div class='AdminMenuItem AdminMenu".$sufix;

	if (isset($_GET['adminpage'])) {
		if ($_GET['adminpage'] == $sufix) {
			$returnStr .= " ActiveMenuItem";
		} else {
			if (('#' == $currentlink) && ($sufix == '0')) {
				$returnStr .= " ActiveMenuItem";
			}
		}
	}
	if ($menulink == $currentlink) {
		$returnStr .= " ActiveMenuItem";
	} else {
		if (('#' == $currentlink) && ($sufix == '0')) {
			$returnStr .= " ActiveMenuItem";
		}
	}
	$returnStr .= "'>";
	$returnStr .= $menutxt;
	$returnStr .= "</div>";
	$returnStr .= "</a>";
	
	return $returnStr;
}

function drawAdminContent() {
	$returnStr = "";
	
	$returnStr .= "<div class='AdminContentWrapper'>";
	$returnStr .= DrawMsg();
	if (empty($_GET["adminpage"])) {
		//$returnStr .= "<h2 class='AdminTableTitle'>" . "Здравоо" . "</h2>";
		$returnStr .= DrawAdminDashboard();
	} else {
			switch ($_GET["adminpage"]) {
			case 'urgent':
				//Проблематични
				$returnStr .= "<h2 class='AdminTableTitle'>" . "Проблематични" . "</h2>";
				$returnStr .= Urgent();
				break;
			case 'inputs':
				//Внесови од контролни
				$returnStr .= "<h2 class='AdminTableTitle'>" . "Внесови од контролни" . "</h2>";
				$returnStr .= EntriesTable();
				break;
			case 'start':
				//Означи старт
				$returnStr .= "<h2 class='AdminTableTitle'>" . "Означи старт" . "</h2>";
				$returnStr .= ChangeStart();
				//$returnStr .= DrawAdminTable("KMT65");
				break;
			case 'newrunner':
				//Внеси нов тркач
				$returnStr .= "<h2 class='AdminTableTitle'>" . "Внеси нов тркач" . "</h2>";
				$returnStr .= NewRunner();
				break;
			case 'runners110':
				//Тркачи 110К
				$returnStr .= "<h2 class='AdminTableTitle'>" . "Тркачи на 110К" . "</h2>";
				$RaceID = 4;
				$returnStr .= RunnersTable($RaceID);
				break;
			case 'runners65':
				//Тркачи 65К
				$returnStr .= "<h2 class='AdminTableTitle'>" . "Тркачи на 65К" . "</h2>";
				$RaceID = 5;
				$returnStr .= RunnersTable($RaceID);
				break;
			case 'runners31':
				//Тркачи 31К
				$returnStr .= "<h2 class='AdminTableTitle'>" . "Тркачи на 31К" . "</h2>";
				$RaceID = 6;
				$returnStr .= RunnersTable($RaceID);
				break;
			case 'runners16':
				//Тркачи 16К
				$returnStr .= "<h2 class='AdminTableTitle'>" . "Тркачи на 16К" . "</h2>";
				$RaceID = 7;
				$returnStr .= RunnersTable($RaceID);
				break;
			case 'other':
				//Останато
				$returnStr .= "<h2 class='AdminTableTitle'>" . "Останато" . "</h2>";
				//$returnStr .= DrawAdminTable("KMT110");
				break;
			default:
				//ne znam sto, daj dashboard
				$returnStr .= DrawMsg();
				//$returnStr .= "<h2 class='AdminTableTitle'>" . "Здравоо" . "</h2>";
				$returnStr .= DrawAdminDashboard();
		}
	}
	
	$returnStr .= "</div>"; //AdminContentWrapper
	
	return $returnStr;	
}

function Urgent() {
	$returnStr = '';
	//1. Proveri dali ima check od kontrolna a nema od start
	$entriesArray = $runnersArray = array();
	$entriesArray = getTableEntriesArray(0, '1');
	
	$runnersArray = getRunners_db('1');
	$NotStartedButCheckedArray = array();
	$i=0;
	foreach($runnersArray as $runnersRow) {
		if (!HasRunnerStarted($runnersRow[4], $entriesArray) && HasRunnerPassedCP($runnersRow[4], $entriesArray)) {
			//ALERT!

			//trka
			$NotStartedButCheckedArray[$i][0] = $runnersRow[6];
			//bib
			$NotStartedButCheckedArray[$i][1] = $runnersRow[4];
			//ime
			$NotStartedButCheckedArray[$i][2] = $runnersRow[2];
			//prezime
			$NotStartedButCheckedArray[$i][3] = $runnersRow[3];
			
			$i++;
		}
	}
	
	if (count($NotStartedButCheckedArray)>0) {
		
		$returnStr .= "<div class='urgent_title'>Имаме " . count($NotStartedButCheckedArray) . " тркачи што се чекирани на контролна, а ги нема на старт:</div>";
		$returnStr .= "
						<div class='live_results_table_wrapper'>
							<table class='live_results_table'>
								<tbody>";
		
		//header
		$returnStr .= "<tr class='table_header_row'>";
		
		$returnStr .= "<th class='table_header_cell'>Трка</th>";
		$returnStr .= "<th class='table_header_cell'>БИБ</th>";
		$returnStr .= "<th class='table_header_cell'>Име</th>";
		$returnStr .= "<th class='table_header_cell'>Презиме</th>";
		
		$returnStr .= "</tr>"; 
		
		foreach($NotStartedButCheckedArray as $row) {
			$returnStr .= "<tr>";
			
			$returnStr .= "<td class='table_cell'>" . $row[0] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[1] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[2] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[3] . "</td>";
			
			$returnStr .= "</tr>";

		}
		
		$returnStr .= "</tbody></table></div>";
	}
	
	
	//2. Vnesen e BIB sto ne postoi
	$EntriesNonExistentRunnersArray = array();
	$i=0;
	foreach($entriesArray as $entryRow) {
		if (($entryRow[11] == '1') && (!IsInRunners($entryRow[7], $runnersArray))) {
			
			$EntriesNonExistentRunnersArray[$i][0] = $entryRow[0];
			$EntriesNonExistentRunnersArray[$i][1] = $entryRow[1];
			$EntriesNonExistentRunnersArray[$i][2] = $entryRow[2];
			$EntriesNonExistentRunnersArray[$i][3] = $entryRow[3];
			$EntriesNonExistentRunnersArray[$i][4] = $entryRow[4];
			$EntriesNonExistentRunnersArray[$i][5] = $entryRow[5];
			$EntriesNonExistentRunnersArray[$i][6] = $entryRow[6];
			$EntriesNonExistentRunnersArray[$i][7] = $entryRow[7];
			$EntriesNonExistentRunnersArray[$i][8] = $entryRow[8];
			$EntriesNonExistentRunnersArray[$i][9] = $entryRow[9];
			$EntriesNonExistentRunnersArray[$i][10] = $entryRow[10];
			$EntriesNonExistentRunnersArray[$i][11] = $entryRow[11];
			$EntriesNonExistentRunnersArray[$i][12] = $entryRow[12];
			$EntriesNonExistentRunnersArray[$i][13] = $entryRow[13];
			
			$i++;		
		}
	}
		
		if (count($EntriesNonExistentRunnersArray)>0) {
		
		$returnStr .= "<div class='urgent_title'>Имаме " . count($EntriesNonExistentRunnersArray) . " записи за БИБ броеви за кои во база не постојат тркачи.</div>";
		$returnStr .= "<div class='live_results_table_wrapper'>
							<table class='live_results_table'>
								<tbody>";
		
		//header
		$returnStr .= "<tr class='table_header_row'>";
		
		$returnStr .= "<th class='table_header_cell'>EntryID</th>";
		$returnStr .= "<th class='table_header_cell'>LocalEntryID</th>";
		$returnStr .= "<th class='table_header_cell'>RaceName</th>";
		$returnStr .= "<th class='table_header_cell'>CPName</th>";
		$returnStr .= "<th class='table_header_cell'>Username</th>";
		$returnStr .= "<th class='table_header_cell'>CPNo</th>";
		$returnStr .= "<th class='table_header_cell'>Time</th>";
		$returnStr .= "<th class='table_header_cell'>BIB</th>";
		$returnStr .= "<th class='table_header_cell'>FirstName</th>";
		$returnStr .= "<th class='table_header_cell'>LastName</th>";
		$returnStr .= "<th class='table_header_cell'>EntryTypeName</th>";
		$returnStr .= "<th class='table_header_cell'>Valid</th>";
		$returnStr .= "<th class='table_header_cell'>ReasonInvalid</th>";
		$returnStr .= "<th class='table_header_cell'>Comment</th>";
		
		$returnStr .= "</tr>"; 
		
		foreach($EntriesNonExistentRunnersArray as $row) {
			$returnStr .= "<tr >";
			
			$returnStr .= "<td class='table_cell'>" . $row[0] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[1] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[2] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[3] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[4] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[5] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[6] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[7] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[8] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[9] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[10] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[11] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[12] . "</td>";
			$returnStr .= "<td class='table_cell'>" . $row[13] . "</td>";
			
			$returnStr .= "</tr>";

		}
		
		$returnStr .= "</tbody></table></div>";
	}
		
	
	
	return $returnStr;
}

function IsInRunners($BIB, $runnersArray) {
	$found = false;
	foreach ($runnersArray as $runnerRow) {
		if (!$found) {
			if ($runnerRow[4] == $BIB) {
					$found = true;
					return true;
				
			}
		}
	}	
	return $found;
}

function HasRunnerStarted($BIB, $entriesArray) {
	$found = false;
	foreach ($entriesArray as $entryRow) {
		if (!$found) {
			if ($entryRow[7] == $BIB) {
				if ($entryRow[5] == '0' && $entryRow[11] == '1') {
					$found = true;
					return true;
				}
			}
		}
	}	
	return $found;
}

function HasRunnerPassedCP($BIB, $entriesArray) {
	$found = false;
	foreach ($entriesArray as $entryRow) {
		if (!$found) {
			if ($entryRow[7] == $BIB) {
				if ($entryRow[5] != '0' && $entryRow[11] == '1') {
					$found = true;
					return true;
				}
			}
		}
	}
	return $found;
}

function getRunners_db($filter) {
	require 'conn.php';

	//retrieve entries
	$q = "SELECT ActiveRacerID, ActiveRacers.RacerID, FirstName,LastName, BIB, Races.RaceID, RaceName FROM ActiveRacers
			JOIN Racers on ActiveRacers.RacerID = Racers.RacerID
			JOIN Races on ActiveRacers.RaceID = Races.RaceID
			WHERE " . $filter;
	$returnArray = array ();
	$result = mysqli_query($conn,$q);
	if ($result->num_rows > 0) {
		$i = 0;
		while($row = $result->fetch_array(MYSQL_ASSOC)) {

			$returnArray[$i][0] = (isset($row['ActiveRacerID'])) ? $row['ActiveRacerID'] : "";
			$returnArray[$i][1] = (isset($row['RacerID'])) ? $row['RacerID'] : "";
			$returnArray[$i][2] = (isset($row['FirstName'])) ? $row['FirstName'] : "";
			$returnArray[$i][3] = (isset($row['LastName'])) ? $row['LastName'] : "";
			$returnArray[$i][4] = (isset($row['BIB'])) ? $row['BIB'] : "";
			$returnArray[$i][5] = (isset($row['RaceID'])) ? $row['RaceID'] : "";
			$returnArray[$i][6] = (isset($row['RaceName'])) ? $row['RaceName'] : "";

			$i++;
		}	

		//return $row['RaceGroupName'];
	} else {
		//nesto...
	}

	return $returnArray;
}

function EntriesTable() {
	$returnStr = '';
		$headerEntriesArray = array();
		$headerEntriesArray = getHeaderEntriesArray();
		$tableEntriesArray = getTableEntriesArray(1,'');
		//var_dump($tableEntriesArray);
		$returnStr .= DrawEntriesTable($headerEntriesArray, $tableEntriesArray);
		
		return $returnStr;
}

function getTableEntriesArray($filterFromSession, $filterQ) {
	
	require 'conn.php';
	if ($filterFromSession) {
		$entriesFilter = constructFilterQuery('query');
	} else {
		$entriesFilter = $filterQ;
	}

	//retrieve entries
	$q = "SELECT EntryID, LocalEntryID, RaceName, CPName, Username, CPNo, Time, CPEntries.BIB, FirstName, LastName, EntryTypeName, Valid, ReasonInvalid, CPEntries.Comment
			FROM `CPEntries`
			LEFT JOIN ActiveRacers ON CPEntries.BIB = ActiveRacers.BIB
			LEFT JOIN Races ON ActiveRacers.RaceID = Races.RaceID
			LEFT JOIN Racers ON Racers.RacerID = ActiveRacers.RacerID
			LEFT JOIN ControlPoints ON ControlPoints.CPID = CPEntries.CPID
			LEFT JOIN EntryTypes ON EntryTypes.EntryTypeID = CPEntries.EntryTypeID
			WHERE " . $entriesFilter . " ORDER BY Time DESC";

	$result = mysqli_query($conn,$q);
	if ($result->num_rows > 0) {
		$i = 0;
		while($row = $result->fetch_array(MYSQL_ASSOC)) {

			$returnArray[$i][0] = (isset($row['EntryID'])) ? $row['EntryID'] : "";
			$returnArray[$i][1] = (isset($row['LocalEntryID'])) ? $row['LocalEntryID'] : "";
			$returnArray[$i][2] = (isset($row['RaceName'])) ? $row['RaceName'] : "";
			$returnArray[$i][3] = (isset($row['CPName'])) ? $row['CPName'] : "";
			$returnArray[$i][4] = (isset($row['Username'])) ? $row['Username'] : "";
			$returnArray[$i][5] = (isset($row['CPNo'])) ? $row['CPNo'] : "";
			$returnArray[$i][6] = (isset($row['Time'])) ? $row['Time'] : "";
			$returnArray[$i][7] = (isset($row['BIB'])) ? $row['BIB'] : "";
			$returnArray[$i][8] = (isset($row['FirstName'])) ? $row['FirstName'] : "";
			$returnArray[$i][9] = (isset($row['LastName'])) ? $row['LastName'] : "";
			$returnArray[$i][10] = (isset($row['EntryTypeName'])) ? $row['EntryTypeName'] : "";
			$returnArray[$i][11] = (isset($row['Valid'])) ? $row['Valid'] : "";
			$returnArray[$i][12] = (isset($row['ReasonInvalid'])) ? $row['ReasonInvalid'] : "";
			$returnArray[$i][13] = (isset($row['Comment'])) ? $row['Comment'] : "";

			$i++;
		}	

		//return $row['RaceGroupName'];
	} else {
		//nesto...
	}

	return $returnArray;
}

function constructFilterQuery($whatFor) {
	
	//EntryID, LocalEntryID, RaceName, CPName, Username, CPNo, Time, CPEntries.BIB, FirstName, LastName, EntryTypeName, Valid, ReasonInvalid, CPEntries.Comment
	
	
	$returnFilterQuery = '';
	
	if ($whatFor == 'query') {
		$returnFilterQuery = '1';
		if (!empty($_SESSION['EntriesFilter_EntryID'])) {
			if ($_SESSION['EntriesFilter_EntryID'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_EntryID']; }
			$returnFilterQuery .= " AND EntryID LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_LocalEntryID'])) {
			if ($_SESSION['EntriesFilter_LocalEntryID'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_LocalEntryID']; }
			$returnFilterQuery .= " AND LocalEntryID LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_RaceName'])) {
			if ($_SESSION['EntriesFilter_RaceName'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_RaceName']; }
			$returnFilterQuery .= " AND RaceName LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_CPName'])) {
			if ($_SESSION['EntriesFilter_CPName'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_CPName']; }
			$returnFilterQuery .= " AND CPName LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_Username'])) {
			if ($_SESSION['EntriesFilter_Username'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_Username']; }
			$returnFilterQuery .= " AND Username LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_CPNo'])) {
			if ($_SESSION['EntriesFilter_CPNo'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_CPNo']; }
			$returnFilterQuery .= " AND CPNo LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_Time'])) {
			if ($_SESSION['EntriesFilter_Time'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_Time']; }
			$returnFilterQuery .= " AND Time LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_BIB'])) {
			if ($_SESSION['EntriesFilter_BIB'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_BIB']; }
			$returnFilterQuery .= " AND CPEntries.BIB LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_FirstName'])) {
			if ($_SESSION['EntriesFilter_FirstName'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_FirstName']; }
			$returnFilterQuery .= " AND FirstName LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_LastName'])) {
			if ($_SESSION['EntriesFilter_LastName'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_LastName']; }
			$returnFilterQuery .= " AND LastName LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_EntryTypeName'])) {
			if ($_SESSION['EntriesFilter_EntryTypeName'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_EntryTypeName']; }
			$returnFilterQuery .= " AND EntryTypeName LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_Valid'])) {
			if ($_SESSION['EntriesFilter_Valid'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_Valid']; }
			$returnFilterQuery .= " AND Valid LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_ReasonInvalid'])) {
			if ($_SESSION['EntriesFilter_ReasonInvalid'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_ReasonInvalid']; }
			$returnFilterQuery .= " AND ReasonInvalid LIKE '%" . $tempSession . "%'";
		}
		if (!empty($_SESSION['EntriesFilter_Comment'])) {
			if ($_SESSION['EntriesFilter_Comment'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_Comment']; }
			$returnFilterQuery .= " AND CPEntries.Comment LIKE '%" . $tempSession . "%'";
		}	
	}
	if ($whatFor == 'display') {
		if (!empty($_SESSION['EntriesFilter_EntryID'])) {
			if ($_SESSION['EntriesFilter_EntryID'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_EntryID']; }
			$returnFilterQuery .= "<span>EntryID: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_LocalEntryID'])) {
			if ($_SESSION['EntriesFilter_LocalEntryID'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_LocalEntryID']; }
			$returnFilterQuery .= "<span>LocalEntryID: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_RaceName'])) {
			if ($_SESSION['EntriesFilter_RaceName'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_RaceName']; }
			$returnFilterQuery .= "<span>RaceName: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_CPName'])) {
			if ($_SESSION['EntriesFilter_CPName'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_CPName']; }
			$returnFilterQuery .= "<span>CPName: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_Username'])) {
			if ($_SESSION['EntriesFilter_Username'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_Username']; }
			$returnFilterQuery .= "<span>Username: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_CPNo'])) {
			if ($_SESSION['EntriesFilter_CPNo'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_CPNo']; }
			$returnFilterQuery .= "<span>CPNo: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_Time'])) {
			if ($_SESSION['EntriesFilter_Time'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_Time']; }
			$returnFilterQuery .= "<span>Time: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_BIB'])) {
			if ($_SESSION['EntriesFilter_BIB'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_BIB']; }
			$returnFilterQuery .= "<span>BIB: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_FirstName'])) {
			if ($_SESSION['EntriesFilter_FirstName'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_FirstName']; }
			$returnFilterQuery .= "<span>FirstName: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_LastName'])) {
			if ($_SESSION['EntriesFilter_LastName'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_LastName']; }
			$returnFilterQuery .= "<span>LastName: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_EntryTypeName'])) {
			if ($_SESSION['EntriesFilter_EntryTypeName'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_EntryTypeName']; }
			$returnFilterQuery .= "<span>EntryTypeName: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_Valid'])) {
			if ($_SESSION['EntriesFilter_Valid'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_Valid']; }
			$returnFilterQuery .= "<span>Valid: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_ReasonInvalid'])) {
			if ($_SESSION['EntriesFilter_ReasonInvalid'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_ReasonInvalid']; }
			$returnFilterQuery .= "<span>ReasonInvalid: \"" . $tempSession . "\"</span>";
		}
		if (!empty($_SESSION['EntriesFilter_Comment'])) {
			if ($_SESSION['EntriesFilter_Comment'] == "<<zero>>") { $tempSession = '0'; }
			else { $tempSession = $_SESSION['EntriesFilter_Comment']; }
			$returnFilterQuery .= "<span>Comment: \"" . $tempSession . "\"</span>";
		}
	}
	
	return $returnFilterQuery;
}

function getHeaderEntriesArray() {
		/*
		
		EntryID
LocalEntryID
CPName
Username
CPNo
Time
BIB
FirstName
LastName
EntryTypeName
Valid
ReasonInvalid
Comment
			*/	
		
	$headerEntriesArray[0] = "EntryID";
	$headerEntriesArray[1] = "LocalEntryID";
	$headerEntriesArray[2] = "RaceName";
	$headerEntriesArray[3] = "CPName";
	$headerEntriesArray[4] = "Username";
	$headerEntriesArray[5] = "CPNo";
	$headerEntriesArray[6] = "Time";
	$headerEntriesArray[7] = "BIB";
	$headerEntriesArray[8] = "FirstName";
	$headerEntriesArray[9] = "LastName";
	$headerEntriesArray[10] = "EntryTypeName";
	$headerEntriesArray[11] = "Valid";
	$headerEntriesArray[12] = "ReasonInvalid";
	$headerEntriesArray[13] = "Comment";

	return $headerEntriesArray;
}

function DrawEntriesTable($headerArray, $tableArray) {

	$returnStr = "";
	//Count
	$returnStr .= "<div class='entries_rows_count'>No. of rows: " . count($tableArray) . "</div><br>";
	//Filter Info
	$filterQuery = constructFilterQuery('display');
	if ($filterQuery != '') {
		$returnStr .= "	<div class='filter_info'>";
			$returnStr .= "	<div class='filter_info_label'>Filter: </div>";
			$returnStr .= "	<div class='filter_info_display'>" . $filterQuery . "</div>";
			$returnStr .= "<form class='filter_delete_form' method='post' action=''>
								<input type='hidden' name='EntriesFilter' value='false'>
								<input type='submit' class='FilterDeleteSubmit' name='EntriesFilterSubmit' value='Delete Filter(s)'>
								</form>";
		$returnStr .= "</div>";
	}

	$returnStr .= "
	<div class='live_results_table_wrapper'>
		<table class='live_results_table'>
			<tbody>
			
			
			

				<tr class='table_header_row'>";
				//header row
		$headerCounter = 0;
		while (isset($headerArray[$headerCounter])) {
			
			$returnStr .= "	<th class='table_header_cell table_header_cell_col" . $headerCounter . "'>";
			$returnStr .= $headerArray[$headerCounter];	
			$returnStr .= "	</th>";
			
			$headerCounter++;
		}
			$returnStr .= "	</tr>";
				//filter row
				$returnStr .= "<tr class='table_filter_row'>";
		$filterCounter = 0;
		while (isset($headerArray[$filterCounter])) {
			
			$returnStr .= "	<td class='table_filter_cell table_filter_cell_col" . $filterCounter . " table_filter_cell_" . $headerArray[$filterCounter] . "'>";
			$returnStr .= "<form class='filter_form filter_form_" . $headerArray[$filterCounter] . "' method='post' action=''>
								<input type='hidden' name='EntriesFilter' value='true'>
								<input type='hidden' name='EntriesFilter_" . $headerArray[$filterCounter] . "' value='true'>
								<input type='text' class='EntriesFilterInput EntriesFilterInput_" . $headerArray[$filterCounter] . "' name='EntriesFilterInput' value='" . getFilter($headerArray[$filterCounter]) . "' >
								<input type='submit' class='EntriesFilterSubmit EntriesFilterSubmit_" . $headerArray[$filterCounter] . "' name='EntriesFilterSubmit' value='Filter'>
								</form>";	
			$returnStr .= "	</td>";
			
			$filterCounter++;
		}
			$returnStr .= "	</tr>";		
				
				//entries
		$rowCounter = 0;
		while (isset($tableArray[$rowCounter][1])) {
			$dataCounter = 0;
			$returnStr .= "	<tr class='table_row table_row_" . $rowCounter . "'>";
			while (isset($tableArray[$rowCounter][$dataCounter])) {
				$returnStr .= "	<td class='table_cell table_cell_col" . $dataCounter;			
				$returnStr .= "'>";
				switch ($dataCounter) {
					/*
					case 2:
					//hide
					$returnStr .= '
						<form class="change_hide" action="" method="post">
							<input type="hidden" name="ChangeHide" value="true">
							<input type="hidden" name="ActiveRacerID" value="' . $tableArray[$rowCounter][0] . '">
							<input id="admin_change_hide_' . $tableArray[$rowCounter][0] . '" type="text" name="NewHide" value="' . $tableArray[$rowCounter][2] . '" required>
							<input class="change_hide_submit" type="submit" value="Смени Hide">
						</form>';
						break;
					case 3:
					//status
					$returnStr .= '
						<form class="change_status" action="" method="post">
							<input type="hidden" name="ChangeStatus" value="true">
							<input type="hidden" name="ActiveRacerID" value="' . $tableArray[$rowCounter][0] . '">
							<input id="admin_change_status_' . $tableArray[$rowCounter][0] . '" type="text" name="NewStatus" value="' . $tableArray[$rowCounter][3] . '" required>
							<input class="change_status_submit" type="submit" value="Смени Status">
						</form>';
						break;
					case 4:
					//bib
					$returnStr .= '
						<form class="change_bib" action="" method="post">
							<input type="hidden" name="ChangeBIB" value="true">
							<input type="hidden" name="ActiveRacerID" value="' . $tableArray[$rowCounter][0] . '">
							<input id="admin_change_bib_' . $tableArray[$rowCounter][0] . '" type="text" name="NewBIB" value="' . $tableArray[$rowCounter][4] . '" required>
							<input class="change_bib_submit" type="submit" value="Смени BIB">
						</form>';
						break;
						
						*/
					default:
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

function getFilter($collumn) {
	if (!empty($_SESSION['EntriesFilter_' . $collumn])) {
		if ($_SESSION['EntriesFilter_' . $collumn] == '<<zero>>') {
			return '0';
		} else {
			return $_SESSION['EntriesFilter_' . $collumn];
		}
	} else {
		return "";
	}
}

function RunnersTable($RaceID) {
	
		$returnStr = '';
		$RaceGroupID = 2;
		
		
		$headerIDsArray = array();
		$headerArray = getHeaderArrayAdmin($RaceGroupID, $RaceID, $headerIDsArray);
		$tableArray = getTableArrayAdmin($headerIDsArray, $RaceGroupID, $RaceID);
		$returnStr .= DrawTheTableAdmin($headerArray, $tableArray, $RaceID);
		
		return $returnStr;
}

function getHeaderArrayAdmin($RaceGroupID, $RaceID, &$headerIDsArray) {
	
	$headerArray[0] = "ActRacerID";
	$headerArray[1] = "RacerID";
	$headerArray[2] = "Hide";
	$headerArray[3] = "Status";
	$headerArray[4] = "BIB";
	$headerArray[5] = "FirstName";
	$headerArray[6] = "LastName";
	$headerArray[7] = "Comment";
	$headerArray[8] = "Gender";
	$headerArray[9] = "DateOfBirth";
	$headerArray[10] = "Country";
	$headerArray[11] = "TeamName";
	$headerArray[12] = "TShirtSize";
	$headerArray[13] = "Email";
	$headerArray[14] = "Tel";

	return $headerArray;
}

function getTableArrayAdmin($headerIDsArray, $RaceGroupID, $RaceID) {
	
	require 'conn.php';

	//retrieve racers
	$q = "SELECT ActiveRacerID, ActiveRacers.RacerID, FirstName, LastName, Gender, DateOfBirth, Country, TeamName, TShirtSize, Email, Tel, Racers.Comment, BIB, Status, Hide
			FROM `ActiveRacers`
			JOIN Racers ON ActiveRacers.RacerID = Racers.RacerID
			JOIN Teams ON Racers.TeamID = Teams.TeamID
			WHERE RaceID = " . $RaceID . " ORDER BY BIB";
	$racers_result = mysqli_query($conn,$q);
	if ($racers_result->num_rows > 0) {
		$racersCounter = 0;
		while($row = $racers_result->fetch_array(MYSQL_ASSOC)) {
			$racersArray[] = $row;
			$returnArray[$racersCounter][0] = $row['ActiveRacerID'];
			$returnArray[$racersCounter][1] = $row['RacerID'];	
			$returnArray[$racersCounter][2] = $row['Hide'];	
			$returnArray[$racersCounter][3] = $row['Status'];
			$returnArray[$racersCounter][4] = $row['BIB'];			
			$returnArray[$racersCounter][5] = $row['FirstName'];
			$returnArray[$racersCounter][6] = $row['LastName'];
			$returnArray[$racersCounter][7] = $row['Comment'];
			$returnArray[$racersCounter][8] = $row['Gender'];
			$returnArray[$racersCounter][9] = $row['DateOfBirth'];
			$returnArray[$racersCounter][10] = "<img src='/flags-mini/" . strtolower($row['Country']) . ".png'/> " . $row['Country'];
			$returnArray[$racersCounter][11] = $row['TeamName'];	
			$returnArray[$racersCounter][12] = $row['TShirtSize'];	
			$returnArray[$racersCounter][13] = $row['Email'];	
			$returnArray[$racersCounter][14] = $row['Tel'];

			$racersCounter++;
		}	

		//return $row['RaceGroupName'];
	} else {
		//nesto...
	}

	return $returnArray;
}

function DrawTheTableAdmin($headerArray, $tableArray, $RaceID) {

	$returnStr = "";
	$returnStr .= "
	<div class='live_results_table_wrapper'>
		<table class='live_results_table'>
			<tbody>
				<tr class='table_header_row table_header_row_race" . $RaceID . "'>";
		$headerCounter = 0;
		while (isset($headerArray[$headerCounter])) {
			
			$returnStr .= "	<th class='table_header_cell table_header_cell_col" . $headerCounter . "'>";
			$returnStr .= $headerArray[$headerCounter];	
			$returnStr .= "	</th>";
			
			$headerCounter++;
		}
			$returnStr .= "	</tr>";
			
		$rowCounter = 0;
		while (isset($tableArray[$rowCounter][0])) {
			$dataCounter = 0;
			$returnStr .= "	<tr class='table_row table_row_race_" . $RaceID . " table_row_" . $rowCounter . "'>";
			while (isset($tableArray[$rowCounter][$dataCounter])) {
				$returnStr .= "	<td class='table_cell table_cell_col" . $dataCounter;			
				$returnStr .= "'>";
				switch ($dataCounter) {
					case 2:
					//hide
					$returnStr .= '
						<form class="change_hide" action="" method="post">
							<input type="hidden" name="ChangeHide" value="true">
							<input type="hidden" name="ActiveRacerID" value="' . $tableArray[$rowCounter][0] . '">
							<input id="admin_change_hide_' . $tableArray[$rowCounter][0] . '" type="text" name="NewHide" value="' . $tableArray[$rowCounter][2] . '" required>
							<input class="change_hide_submit" type="submit" value="Смени Hide">
						</form>';
						break;
					case 3:
					//status
					$returnStr .= '
						<form class="change_status" action="" method="post">
							<input type="hidden" name="ChangeStatus" value="true">
							<input type="hidden" name="ActiveRacerID" value="' . $tableArray[$rowCounter][0] . '">
							<input id="admin_change_status_' . $tableArray[$rowCounter][0] . '" type="text" name="NewStatus" value="' . $tableArray[$rowCounter][3] . '" required>
							<input class="change_status_submit" type="submit" value="Смени Status">
						</form>';
						break;
					case 4:
					//bib
					$returnStr .= '
						<form class="change_bib" action="" method="post">
							<input type="hidden" name="ChangeBIB" value="true">
							<input type="hidden" name="RaceID" value="' . $RaceID . '">
							<input type="hidden" name="OldBIB" value="' . $tableArray[$rowCounter][4] . '">
							<input type="hidden" name="ActiveRacerID" value="' . $tableArray[$rowCounter][0] . '">
							<input id="admin_change_bib_' . $tableArray[$rowCounter][0] . '" type="text" name="NewBIB" value="' . $tableArray[$rowCounter][4] . '" required>
							<input class="change_bib_submit" type="submit" value="Смени BIB">
						</form>';
						break;
					default:
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

function updateRaceID_db($ActiveRacerID, $NewRaceID) {
	require 'conn.php';
	$query = "	UPDATE ActiveRacers
				SET RaceID='" . $NewRaceID . "'
				WHERE ActiveRacerID=" . $ActiveRacerID;
	$result = mysqli_query($conn,$query);
	
	if (mysqli_affected_rows($conn) > 0) {
		return true;
	} else { 
		if (mysqli_error($conn)) {
			if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
			$_SESSION["msg"] .= mysqli_error($conn) . "<br>";
		}
		return false;
	}
}

function updateBIB_db($ActiveRacerID, $NewBIB) {
	require 'conn.php';
	$query = "	UPDATE ActiveRacers
				SET BIB='" . $NewBIB . "'
				WHERE ActiveRacerID=" . $ActiveRacerID;
	$result = mysqli_query($conn,$query);
	
	if (mysqli_affected_rows($conn) > 0) {
		return true;
	} else { 
		if (mysqli_error($conn)) {
			if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
			$_SESSION["msg"] .= mysqli_error($conn) . "<br>";
		}
		return false;
	}
}

function getRaceIDByBIB($BIB) {
if ((int)$BIB > 0 && (int)$BIB <= 200) {
	return 4;
}
if ((int)$BIB > 200 && (int)$BIB <= 400) {
	return 5;
}
if ((int)$BIB > 400 && (int)$BIB <= 700) {
	return 6;
}
if ((int)$BIB > 700 && (int)$BIB <= 1000) {
	return 7;
}
return 0;
}

function getRaceNameByBIB($BIB) {
if ((int)$BIB > 0 && (int)$BIB <= 200) {
	return "KMUT 110K";
}
if ((int)$BIB > 200 && (int)$BIB <= 400) {
	return "Kozjak Trail 65K";
}
if ((int)$BIB > 400 && (int)$BIB <= 700) {
	return "Kamena Baba Trail 31K";
}
if ((int)$BIB > 700 && (int)$BIB <= 1000) {
	return "Treskavec Trail 16K";
}
return 0;
}

function updateStatus_db($ActiveRacerID, $NewStatus) {
	require 'conn.php';
	$query = "	UPDATE ActiveRacers
				SET Status='" . $NewStatus . "'
				WHERE ActiveRacerID=" . $ActiveRacerID;
	$result = mysqli_query($conn,$query);
	
	if (mysqli_affected_rows($conn) > 0) {
		return true;
	} else { 
		if (mysqli_error($conn)) {
			if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
			$_SESSION["msg"] .= mysqli_error($conn) . "<br>";
		}
		return false;
	}	
}

function updateHide_db($ActiveRacerID, $NewHide) {
	require 'conn.php';
	$query = "	UPDATE ActiveRacers
				SET Hide='" . $NewHide . "'
				WHERE ActiveRacerID=" . $ActiveRacerID;
	$result = mysqli_query($conn,$query);
	
	if (mysqli_affected_rows($conn) > 0) {
		return true;
	} else { 
		if (mysqli_error($conn)) {
			if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
			$_SESSION["msg"] .= mysqli_error($conn) . "<br>";
		}
		return false;
	}	
}

function NewRunner() {
	$races = getRaces_db();
	$returnStr = '';
	
	$returnStr .= '
	<form action="" method="post">
	
		<input type="hidden" name="NewRunner" value="1" required>
		
	<div class="newRunner_field">
		<label for="Race"> Трка: </label>
		<select name="Race">
		  <option value="7">16K</option>
		  <option value="6">31K</option>
		  <option value="5">65K</option>
		  <option value="4">110K</option>
		</select>
	</div>
	<div class="newRunner_field">
		<input name="BIB" type="text" id="БИБ" placeholder="BIB" required>
	</div>
	<div class="newRunner_field">
		<input name="FirstName" type="text" id="FirstName" placeholder="Име" required>
	</div>
	<div class="newRunner_field">
		<input name="LastName" type="text" id="LastName" placeholder="Презиме" required>
	</div>
	<div class="newRunner_field">
		<label for="Gender"> Пол: </label>
		<select name="Gender">
		  <option value="M">M</option>
		  <option value="F">F</option>
		</select>
	</div>
	<div class="newRunner_field">	
		<input name="DateOfBirth" type="text" id="DateOfBirth" placeholder="Дата на раѓање (YYYY-m-d)" required>
	</div>
	<div class="newRunner_field">
		<input name="Country" type="text" id="Country" placeholder="Држава (2 букви! Пример: МК)" required>		
	</div>
	<div class="newRunner_field">
		<input name="Team" type="text" id="Team" placeholder="Клуб">	
	</div>
	<div class="newRunner_field">	
		<label for="TShirtSize"> Величина облека: </label>
		<select name="TShirtSize">
		  <option value="S">S</option>
		  <option value="M">M</option>
		  <option value="L">L</option>
		  <option value="XL">XL</option>
		</select>
	</div>
	<div class="newRunner_field">	
		<input name="Email" type="text" id="Email" placeholder="Email" required>	
	</div>
	<div class="newRunner_field">
		<input name="Tel" type="text" id="Tel" placeholder="Телефон" required>	
	</div>
	<div class="newRunner_field">		
		<input name="Comment" type="text" id="Comment" placeholder="Коментар">	
	</div>
	<div class="newRunner_field">	
		<input type="submit" value="Внеси">
	</div>
	</form>';
	return $returnStr;
}

function ChangeStart() {
	$races = getRaces_db();
	
	$returnStr = '';
	
	$returnStr .= '
	<div class="race_set_start race110_set_start">
		<h3>' . $races[0]['Description'] . '</h3>
		<form action="" method="post">
		<input type="hidden" name="RaceID" value="' . $races[0]['RaceID'] . '">
		<input type="hidden" name="RaceName" value="' . $races[0]['RaceName'] . '">
		<input type="hidden" name="setStart" value="now">
		<input type="submit" name="set110now" value="Означи сега!">
		</form>
		<br>
		<form action="" method="post">
		<input type="hidden" name="setStart" value="input">
		<input type="hidden" name="RaceID" value="' . $races[0]['RaceID'] . '">
		<input type="hidden" name="RaceName" value="' . $races[0]['RaceName'] . '">
		<input class="set_input_text set110" type="text" name="inputStart" value="' . $races[0]['StartTime'] . '"><br>
		<input type="submit" name="set110" value="Смени го рачно">
		</form>
		<br>
	</div>
	';
	
		$returnStr .= '
	<div class="race_set_start race65_set_start">
		<h3>' . $races[1]['Description'] . '</h3>
		<form action="" method="post">
		<input type="hidden" name="setStart" value="now">
		<input type="hidden" name="RaceID" value="' . $races[1]['RaceID'] . '">
		<input type="hidden" name="RaceName" value="' . $races[1]['RaceName'] . '">
		<input type="submit" name="set65now" value="Означи сега!">
		</form>
		<br>
		<form action="" method="post">
		<input type="hidden" name="setStart" value="input">
		<input type="hidden" name="RaceID" value="' . $races[1]['RaceID'] . '">
		<input type="hidden" name="RaceName" value="' . $races[1]['RaceName'] . '">
		<input class="set_input_text set65" type="text" name="inputStart"  value="' . $races[1]['StartTime'] . '"><br>
		<input type="submit" name="set65" value="Смени го рачно">
		</form>
		<br>
	</div>
	';
	
		$returnStr .= '
	<div class="race_set_start race31_set_start">
		<h3>' . $races[2]['Description'] . '</h3>
		<form action="" method="post">
		<input type="hidden" name="setStart" value="now">
		<input type="hidden" name="RaceID" value="' . $races[2]['RaceID'] . '">
		<input type="hidden" name="RaceName" value="' . $races[2]['RaceName'] . '">
		<input type="submit" name="set31now" value="Означи сега!">
		</form>
		<br>
		<form action="" method="post">
		<input type="hidden" name="setStart" value="input">
		<input type="hidden" name="RaceID" value="' . $races[2]['RaceID'] . '">
		<input type="hidden" name="RaceName" value="' . $races[2]['RaceName'] . '">
		<input class="set_input_text set31" type="text" name="inputStart" value="' . $races[2]['StartTime'] . '"><br>
		<input type="submit" name="set31" value="Смени го рачно">
		</form>
		<br>
	</div>
	';
	
		$returnStr .= '
	<div class="race_set_start race16_set_start">
		<h3>' . $races[3]['Description'] . '</h3>
		<form action="" method="post">
		<input type="hidden" name="setStart" value="now">
		<input type="hidden" name="RaceID" value="' . $races[3]['RaceID'] . '">
		<input type="hidden" name="RaceName" value="' . $races[3]['RaceName'] . '">
		<input type="submit" name="set16now" value="Означи сега!">
		</form>
		<br>
		<form action="" method="post">
		<input type="hidden" name="setStart" value="input">
		<input type="hidden" name="RaceID" value="' . $races[3]['RaceID'] . '">
		<input type="hidden" name="RaceName" value="' . $races[3]['RaceName'] . '">
		<input class="set_input_text set16" type="text" name="inputStart" value="' . $races[3]['StartTime'] . '"><br>
		<input type="submit" name="set16" value="Смени го рачно">
		</form>
		<br>
	</div>
	';
	
	return $returnStr;
}

function setStart_db($RaceID, $NewStartValue) {
	require 'conn.php';
	$query = "	UPDATE Races
				SET StartTime='" . $NewStartValue . "'
				WHERE RaceID=" . $RaceID;
	$result = mysqli_query($conn,$query);
	
	if (mysqli_affected_rows($conn) > 0) {
		return true;
	} else { 
		if (mysqli_error($conn)) {
			if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
			$_SESSION["msg"] .= mysqli_error($conn) . "<br>";
		}
		return false;
	}
}

function insertNewRunner_db($RaceID, $BIB, $FirstName, $LastName, $Gender, $DateOfBirth, $Country, $Team, $TShirtSize, $Email, $Comment, $Tel) {
	require 'conn.php';
	mysqli_begin_transaction($conn, MYSQLI_TRANS_START_READ_WRITE);
	$error = false;
	//1. PRoveri dali go ima vo Team, ako da, zemi ID, ako ne, vnesi i zemi ID
	$query = "SELECT TeamID FROM Teams WHERE TeamName = '" . $Team . "'";
	$result = mysqli_query($conn,$query);
	if ($result->num_rows > 0) {
		$row = $result->fetch_array(MYSQL_ASSOC);
		$TeamID = $row['TeamID'];
	} else {
		//nema, vnesi go
		$query = "INSERT INTO Teams (TeamName) VALUES ('" . $Team . "');";
		$result = mysqli_query($conn,$query);
		if (mysqli_affected_rows($conn) > 0) {
			$TeamID = mysqli_insert_id($conn);
		} else {
			$error = true;
			if (mysqli_error($conn)) {
				if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
				$_SESSION["msg"] .= "Error inserting into Teams: " . mysqli_error($conn) . "<br>";
			}
		}
	}
	
	if (!$error) {

		//2. Vnesi vo Racers, zemi RacerID
		$query = "	INSERT INTO Racers (FirstName, LastName, Gender, DateOfBirth, Country, TeamID, TShirtSize, Email, Tel, Comment) VALUES (
					'" . $FirstName . "',
					'" . $LastName . "', 
					'" . $Gender . "', 
					'" . $DateOfBirth . "', 
					'" . $Country . "', 
					'" . $TeamID . "', 
					'" . $TShirtSize . "', 
					'" . $Email . "', 
					'" . $Tel . "', 
					'" . $Comment . "');";
		$result = mysqli_query($conn,$query);
		if (mysqli_affected_rows($conn) > 0) {
			$RacerID = mysqli_insert_id($conn);
		} else {
			$error = true;
			if (mysqli_error($conn)) {
				if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
				$_SESSION["msg"] .= "Error inserting into Racers: " . mysqli_error($conn) . "<br>";
			}
		}	
	}
	
	if (!$error) {
		//3. Vnesi vo ActiveRacers
				$query = "	INSERT INTO ActiveRacers (RacerID, RaceID, BIB) VALUES (
					'" . $RacerID . "',
					'" . $RaceID . "', 
					'" . $BIB . "');";
					
		$result = mysqli_query($conn,$query);
		if (mysqli_affected_rows($conn) > 0) {
			$ActiveRacerID = mysqli_insert_id();
		} else {
			$error = true;
			if (mysqli_error($conn)) {
				if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
				$_SESSION["msg"] .= "Error inserting into ActiveRacers: " . mysqli_error($conn) . "<br>";
			}
		}
	}
	
	if (!$error) {
		mysqli_commit($conn);
		return true;
	} else {
		mysqli_rollback($conn);
		return false;
	}
	
}

function getRaces_db() {
	require 'conn.php';
	$query = "SELECT * FROM Races";
	$result = mysqli_query($conn,$query);
	$races = array();
	$racesCount = 0;
	if ($result->num_rows > 0) {
		while($row = $result->fetch_array(MYSQL_ASSOC)) {
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

function drawEditorPage() {
	$returnStr = "";
	//nisto za sega
	return $returnStr;
}

function drawUserPage() {
	$returnStr = "";
	//nisto za sega
	return $returnStr;
}

function DrawAdminDashboard() {
	$returnStr = "";
	
	$returnStr .= "<div class='adminDashboardMsg'>";
	$returnStr .= "</div>";
	$returnStr .= Urgent();
	return $returnStr;
}

function DrawMsg() {
	$returnStr = "";
	if (!empty($_SESSION["msg"])) {
		$returnStr .= "<div class='msgPost'>";
		$returnStr .= $_SESSION["msg"];
		$returnStr .= "</div>";
		
		$_SESSION["msg"] = "";
	}
	return $returnStr;
}

function IsBIBAvailable_db($BIB) {
	require "conn.php";
	$query = "SELECT ActiveRacerID FROM ActiveRacers WHERE BIB = '" . $BIB . "'";
	$result = mysqli_query($conn,$query);
	if ($result->num_rows > 0) {
		return false;
	} else {
		return true;
	}
}

function PostToSession($collumn) {
	if (!empty($_POST["EntriesFilter_" . $collumn])) {
		if ($_POST["EntriesFilter_" . $collumn] == "true") {
			if ($_POST["EntriesFilterInput"] == "") {
				unset($_SESSION["EntriesFilter_" . $collumn]);	
			} else {
				if ($_POST["EntriesFilterInput"] == '0') {
					$_SESSION["EntriesFilter_" . $collumn] = '<<zero>>';
				} else {
					$_SESSION["EntriesFilter_" . $collumn] = $_POST["EntriesFilterInput"];
				}
			}
		}
	} 
}

function UnsetFilterSession($collumn) {	
	if (!empty($_SESSION["EntriesFilter_" . $collumn])) {
		unset($_SESSION["EntriesFilter_" . $collumn]);
	}
}
/******************************** GENERAL FUNCTIONS *************************/
function getCurrentUrl() {
	$current_url="http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	return $current_url;
}

?>
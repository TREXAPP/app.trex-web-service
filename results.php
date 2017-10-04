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

require "results_functions.php";
require 'conn.php';
$TEST_MODE = true;
//**************************************
//      TEST URL ZA KRIENJE:
//      http://app.trex.mk/index.php?RaceGroupID=2&RaceID=6&HideHeader=true&HideTopnav=true&HideRaceinfo=true
//      menjaj go samo RaceID=4 za 110k do RaceID=7 za 16K
//*********************************
$outhtml = "";
$drawSubRacesList = false;
	
$hideHeader = false;
if (isset($_GET['HideHeader'])) {
	if ($_GET['HideHeader'] == 'true') {
		$hideHeader = true;
	}
}

$hideTopnav = false;
if (isset($_GET['HideTopnav'])) {
	if ($_GET['HideTopnav'] == 'true') {
		$hideTopnav = true;
	}
}

$hideRaceinfo = false;
if (isset($_GET['HideRaceinfo'])) {
	if ($_GET['HideRaceinfo'] == 'true') {
		$hideRaceinfo = true;
	}
}

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
	
	if (!$hideHeader) {
		$outhtml .= DrawResultsHeader($raceName);
	}
	if (!$hideTopnav) {
		$outhtml .= DrawTopnav($RaceID);	
	}
		
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
		
		if (!$hideRaceinfo) {
			$outhtml .= DrawRaceInfo($RaceID, $races, $StartTime);
		}
		
		
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
<html lang="en-US">
<head profile="http://www.w3.org/2005/10/profile">
<meta property="og:title" content="Results - Krali Marko Trails 2017"/>
<meta property="og:image" content="http://app.trex.mk/assets/facebook_2.jpg"/>
<meta property="og:site_name" content="Results - Krali Marko Trails 2017"/>
<meta property="og:description" content="Live and Official results from the Krali Marko Trails 2017 race, Macedonia"/>
<link rel="icon" 
      type="image/png" 
      href="http://kmt.mk/wp-content/uploads/2016/02/bozdogan.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" data-reactid="4"/>
<title>Results - Krali Marko Trails 2017</title>
<meta name="Description" content="Live and Official results from the Krali Marko Trails 2017 race, Macedonia">
<meta name="Keywords" content="Krali Marko, Krali Marko Trails, KMT, KMT2017, Running, Trail Running, Ultra Marathon, Macedonia, Ultra Trail Running">
<?php //if (IsStartPassed($StartTime)) { ?>
	<!-- <meta http-equiv="refresh" content="60"/> -->
<?php //} ?>
<link href="https://fonts.googleapis.com/css?family=Cuprum|Fira+Sans+Extra+Condensed|Open+Sans+Condensed:300|Roboto+Condensed|Ubuntu+Condensed" rel="stylesheet">
<link rel="stylesheet" href="/style_results.css">
<?php echo loadCSS($races, $drawSubRacesList); ?>
<script src="results.js"></script>
</head>
<body>
<?php echo $outhtml; ?>
</body>
</html>

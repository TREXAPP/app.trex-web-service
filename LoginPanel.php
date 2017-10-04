<?php
require 'functions.php';
echo LoginPanel_func();


function LoginPanel_func () {
//function LoginPanel_func ($atts, $content = null) {

	$returnStr = "";
	
	CheckPostAndGetParameters();
	
	if (isLoggedIn()) {
		redirectToPage();
	} else {
		$returnStr .= DrawHead("LoginPanel");
		$returnStr .= DrawHeader("KMT кориснички панел");
		$returnStr .= DrawContent();
		$returnStr .= DrawFooter();
	}

	return $returnStr;	
}
		

function CheckPostAndGetParameters () {
	//$returnStr = "";

	
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		
		//logout
		if (!empty($_POST["LogOut"])) {
			if ($_POST["LogOut"] == 'true') {
				LogOut();
			}
		}
		
		//login
		if (!empty($_POST["LogIn"])) {
			if ($_POST["LogIn"] == "true") {
				$user = $_POST["user"];
				$pass = $_POST["pass"];
				if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
				if (LogIn($user, $pass)) {
					$_SESSION["msg"] .= "Успешно логиран корисник " . $user . "<br/>";
				} else {
					$_SESSION["msg"] .= "Погрешен корисник или лозинка.<br/>";

				}
			}
		}
		
		
		
	} else {
		if (!empty($_GET["msgType"])) {
			if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
			switch ($_GET["msgType"]) {
				case 1:
					$_SESSION["msg"] .= "Успешно се одлогиравте.";
					break;
				case 2:
					$_SESSION["msg"] .= "Немате привилегии за администраторската страна. Ве молиме најавете се.";
					break;
				default:
					$_SESSION["msg"] = "";
			}
		}
	}
	
	
	

}
		
?>
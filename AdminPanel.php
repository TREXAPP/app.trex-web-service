<?php
require 'functions.php';
require 'results_functions.php';
//admin panel
echo AdminPanel_func();

function AdminPanel_func () {
//function AdminPanel_func ($atts, $content = null) {

	session_start();

		$returnStr = "";
		
		//check if logout
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {	
	
		//logout
		if (!empty($_POST["LogOut"])) {
			if ($_POST["LogOut"] == 'true') {
				LogOut();
				header('Location: http://app.trex.mk/AdminPanel.php?msgType=1');
				exit;
			}
		}
	}
		
		$loggedInAdmin = false;
		if ((!empty($_SESSION["Logged"])) && (!empty($_SESSION["AuthLevel"]))) {
			if ($_SESSION["Logged"] == "true" && $_SESSION["AuthLevel"] == 1) {
				$loggedInAdmin = true;
			}
		}

		if ($loggedInAdmin) {
			
			ManagePostDataAdmin();
			$returnStr .= DrawHead("AdminPanel");
			$returnStr .= DrawHeader("КМТ 2017 Админ Панел");
			$returnStr .= DrawContent();
			$returnStr .= DrawFooter();
		
		} else {
				LogOut();
				header('Location: http://app.trex.mk/LoginPanel.php?msgType=2');
				exit;
			//$returnStr .= "Немате привилегии за оваа страна. Вратете се на <a href='http://kmt.mk/login'>страната за логирање</a> и обидете се повторно.";
		}

		return $returnStr;
}

function ManagePostDataAdmin() {
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {	
	
		//logout
		if (!empty($_POST["LogOut"])) {
			if ($_POST["LogOut"] == 'true') {
				LogOut();
				header('Location: http://app.trex.mk/AdminPanel.php?msgType=2');
				exit;
			}
		}
		
		//oznaci Start
		if (!empty($_POST["setStart"])) {
			$RaceID = $_POST["RaceID"];
			$RaceName = $_POST["RaceName"];
			if ($_POST["setStart"] == "input") {
				$NewStartValue = $_POST["inputStart"];				
			} else {
				//$NewStartValue = date("Y-m-d H:i:s");
				$NewStartValue = date("Y-m-d H:i:s", strtotime('+2 hours'));
			}
			if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
			if (setStart_db($RaceID, $NewStartValue)) {
				$_SESSION["msg"] .= "УСПЕШНО е ажуриран стартот за трката " . $RaceName . ".<br/>"; 
			} else {
				$_SESSION["msg"] .= "Грешка: Ажурирањето на стартот за трката " . $RaceName . " беше НЕУСПЕШНО!<br/>";
			}
		}
		
		//vnesi nov trkach
		if (!empty($_POST["NewRunner"])) {
			if ($_POST["NewRunner"] == '1') {
				/*
				Logic:
				begin transaction
				-proveri dali timot go ima vo Team, ako da, zemi TeamID, ako ne, vnesi vo Teams, i zemi ID
				-vnesi vo Racers, zemi RacerID
				-vnesi vo ActiveRacers
				commit/rollback transaction
				*/
				$Race = $_POST["Race"];
				$BIB = $_POST["BIB"];
				$FirstName = $_POST["FirstName"];
				$LastName = $_POST["LastName"];
				$Gender = $_POST["Gender"];
				$DateOfBirth = $_POST["DateOfBirth"];
				$Country = $_POST["Country"];
				$Team = $_POST["Team"];
				$TShirtSize = $_POST["TShirtSize"];
				$Email = $_POST["Email"];
				$Tel = $_POST["Tel"];
				$Comment = $_POST["Comment"];
				
				if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
				if (insertNewRunner_db($Race, $BIB, $FirstName, $LastName, $Gender, $DateOfBirth, $Country, $Team, $TShirtSize, $Email, $Comment, $Tel)) {
					$_SESSION["msg"] .= "УСПЕШНО е внесен тркачот " . $FirstName . " " . $LastName .  " со број " . $BIB . ".<br/>"; 
				} else {
					$_SESSION["msg"] .= "Грешка: Внесот на тркачот беше НЕУСПЕШЕН!<br/>";
				}
				
			}
		}
		
		//promena na Status
		if (!empty($_POST["ChangeStatus"])) {
			if ($_POST["ChangeStatus"] == "true") {
				$ActiveRacerID = $_POST["ActiveRacerID"];
				$NewStatus = $_POST["NewStatus"];
				if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
				if (updateStatus_db($ActiveRacerID, $NewStatus)) {
					$_SESSION["msg"] .= "УСПЕШНО е ажуриран статусот на тркачот со ActRacerID = " . $ActiveRacerID . "<br/>"; 
				} else {
					$_SESSION["msg"] .= "Грешка: Обидот за ажурирање на статусот на тркачот со ActRacerID = " . $ActiveRacerID . " беше НЕУСПЕШЕН!<br/>";
				}
			}
		}
		
		//promena na Hide
		if (!empty($_POST["ChangeHide"])) {
			if ($_POST["ChangeHide"] == "true") {
				$ActiveRacerID = $_POST["ActiveRacerID"];
				$NewHide = $_POST["NewHide"];
				if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
				if (updateHide_db($ActiveRacerID, $NewHide)) {
					$_SESSION["msg"] .= "УСПЕШНО е ажуриранa вредноста за Hide кај тркачот со ActRacerID = " . $ActiveRacerID . "<br/>"; 
				} else {
					$_SESSION["msg"] .= "Грешка: Обидот за ажурирање на вредноста за Hide на тркачот со ActRacerID = " . $ActiveRacerID . " беше НЕУСПЕШЕН!<br/>";
				}
			}
		}
		
		//promena na BIB
		if (!empty($_POST["ChangeBIB"])) {
			if ($_POST["ChangeBIB"] == "true") {
					$RaceID = $_POST["RaceID"];
					$ActiveRacerID = $_POST["ActiveRacerID"];
					$NewBIB = $_POST["NewBIB"];
					$NewRaceID = getRaceIDByBIB($NewBIB);
				if (IsBIBAvailable_db($NewBIB)) {
					if (!isset($_SESSION["msg"])) $_SESSION["msg"] = '';
					if (updateBIB_db($ActiveRacerID, $NewBIB)) {
						$_SESSION["msg"] .= "УСПЕШНО е ажуриран BIB-от на тркачот со ActRacerID = " . $ActiveRacerID . "<br/>"; 
						if ($NewRaceID != $RaceID) {
							if (updateRaceID_db($ActiveRacerID, $NewRaceID)) {
								$_SESSION["msg"] .= "УСПЕШНО е префрлен тркачот ActRacerID = " . $ActiveRacerID . " на трката " . getRaceNameByBIB($NewBIB) . "<br/>"; 
							} else {
								$_SESSION["msg"] .= "Грешка: Обидот за ажурирање на RaceID на тркачот со ActRacerID = " . $ActiveRacerID . " беше НЕУСПЕШЕН!<br/>";
							}
						}
					} else {
						$_SESSION["msg"] .= "Грешка: Обидот за ажурирање на BIB-от на тркачот со ActRacerID = " . $ActiveRacerID . " беше НЕУСПЕШЕН!<br/>";
					}
				} else {
					$_SESSION["msg"] .= "Грешка: Бројчето " . $NewBIB . " е веќе зафатено, изберете друго.";
				}
			}
		}
	
		//Entries Filter
		if (!empty($_POST["EntriesFilter"])) {
			if ($_POST["EntriesFilter"] == "true") {
				//EntryID, LocalEntryID, RaceName, CPName, Username, CPNo, Time, CPEntries.BIB, FirstName, LastName, EntryTypeName, Valid, ReasonInvalid, CPEntries.Comment

				PostToSession("EntryID");
				PostToSession("LocalEntryID");
				PostToSession("RaceName");
				PostToSession("CPName");
				PostToSession("Username");
				PostToSession("CPNo");
				PostToSession("Time");
				PostToSession("BIB");
				PostToSession("FirstName");
				PostToSession("LastName");
				PostToSession("EntryTypeName");
				PostToSession("Valid");
				PostToSession("ReasonInvalid");
				PostToSession("Comment");	
				
			} else {
				UnsetFilterSession("EntryID");
				UnsetFilterSession("LocalEntryID");
				UnsetFilterSession("RaceName");
				UnsetFilterSession("CPName");
				UnsetFilterSession("Username");
				UnsetFilterSession("CPNo");
				UnsetFilterSession("Time");
				UnsetFilterSession("BIB");
				UnsetFilterSession("FirstName");
				UnsetFilterSession("LastName");
				UnsetFilterSession("EntryTypeName");
				UnsetFilterSession("Valid");
				UnsetFilterSession("ReasonInvalid");
				UnsetFilterSession("Comment");	
			}
		}
	
	/*
		//registriraj deka platil
		if (!empty($_POST["RegisterPayed"])) {
			if ($_POST["RegisterPayed"] == "true") {
				
				$racerID = $_POST["RacerID"];
				$raceID = $_POST["RaceID"];
				$amount = $_POST["AmountPayed"];
				$email = $_POST["Email"];
				$name = $_POST["FullName"];
				$lang = $_POST["lang"];
				$paymentType = $_POST["PaymentType"];
				
				$isAutoBIB = false;
				if (!empty($_POST["AutoBIB"])) {
					if ($_POST["AutoBIB"] == "true") {
						$isAutoBIB = true;
					}
				}
				
				if (!$isAutoBIB) {
					//check if bib is available
					if (!isBIBavailable($_POST["BIB"])) {
						$_SESSION["msg"] .= "BIB бројчето што сакаш да го внесеш е зафатено. Наместо тоа, доделено му е автоматско бројче. Можеш да го сменеш, само пази пак да не е веќе зафатено<br/>";
						$isAutoBIB = true;
					}
				}
				
				if ($isAutoBIB) {
					$BIB = GenerateNewBIB($raceID);
				} else {
					if (empty($_POST["BIB"])) {
						$_SESSION["msg"] .= "Епа ако веќе нејќеш автоматски да му се додели бројче, бар внеси кое сакаш. Не можеш празно да го оставиш.<br/>Ај вака, сеа му ставивме автоматски, па ако не ти се свиѓа, бујрум смени го.<br/>";
						$BIB = GenerateNewBIB($raceID);
					} else {
						$BIB = $_POST["BIB"];
					}
				}
				
				if (UpdatePayedInDb($racerID, $raceID, $amount, $paymentType, $BIB)) {
					$_SESSION["msg"] .= "Прибележано дека платил у база.<br/>"; 
					if (!empty($_POST["SendMail"])) {
						if ($_POST["SendMail"] == "true") {
							SendPayedSuccessMailAdmin($email, $name, $lang, $BIB);
						}
					}
				} else {
					$_SESSION["msg"] .= "Грешка: не е ажурирано во база! Или некој чепкал кај што не му е местото, или тој што го правел системов заебал работа.<br/>";
				}
			}
		}
		
		//smeni mu bib broj
		if (!empty($_POST["ChangeBIB"])) {
			if ($_POST["ChangeBIB"] == "true") {
				if (isBIBavailable($_POST["NewBIB"])) {
					$raceID = $_POST["RaceID"];
					$racerID = $_POST["RacerID"];
					$BIB = $_POST["NewBIB"];
					if (UpdateBIBInDb($racerID, $raceID, $BIB)) {
						$_SESSION["msg"] .= "Прибележано у база дека е сменетo BIB бројчето. Сега е ".$BIB."<br/>"; 
					} else {
						$_SESSION["msg"] .= "Грешка: не е ажурирано во база! Или некој чепкал кај што не му е местото, или тој што го правел системов утнал нешто.<br/>";
					}
				} else {
					$_SESSION["msg"] .= "Извини, ама тоа бројче е веќе зафатено. Најди некое друго слободно и пробај да го смениш пак.<br/>";
				}
			}
		} 
	
	*/
	}
}

?>
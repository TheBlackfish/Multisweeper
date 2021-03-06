<?php

#This file holds all functionality related to initializing the MySQL server for usage, as well as confirmation of said initialization.

require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/functional/security.php');

#checkMySQL()
#Checks the global variables table to see if MySQL has been fully initialized. If not, will call initMySQL().
function checkMySQL() {
	global $sqlhost, $sqlusername, $sqlpassword;

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	if ($query = $conn->prepare("SELECT v FROM sweepelite.globalvars WHERE k=?")) {
		$var = "mysqlInitialized";
		$query->bind_param("s", $var);
		$query->execute();
		$shouldInit = false;
		if ($query->num_rows === 0) {
			$shouldInit = true;
		}
		$query->close();
		if ($shouldInit) {
			initMySQL();
		}
	} else {
		error_log("Could not prepare MySQL check statement.");
		error_log("Trying to init anyways, since this seems to be related to not being initialized yet.");
		initMySQL();
	}
}

#initMySQL()
#Calls various MySQL queries to create all of the necessary tables. A global variable will be set if the creation encounters no errors.
function initMySQL() {
	global $sqlhost, $sqlusername, $sqlpassword;

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("initMySQL.php - Connection failed: " . $conn->connect_error);
	}

	$actionTableStatement = "CREATE TABLE IF NOT EXISTS sweepelite.actionqueue (
		`gameID` int(11) NOT NULL, 
		`playerID` int(11) NOT NULL, 
		`actionType` int(2) NOT NULL, 
		`xCoord` int(11) NOT NULL, 
		`yCoord` int(11) NOT NULL, 
		KEY `gameID_idx` (`gameID`), 
		KEY `playerID_idx` (`playerID`), 
		CONSTRAINT `gameIDx` FOREIGN KEY (`gameID`) REFERENCES `games` (`gameID`) ON DELETE NO ACTION ON UPDATE NO ACTION, 
		CONSTRAINT `playerIDx` FOREIGN KEY (`playerID`) REFERENCES `players` (`playerID`) ON DELETE CASCADE ON UPDATE CASCADE
	)";

	$chatTableStatement = "CREATE TABLE IF NOT EXISTS sweepelite.chatmessages (
		`playerID` int(11) NOT NULL, 
		`message` varchar(500) NOT NULL DEFAULT 'ERROR', 
		`time` datetime NOT NULL, 
		`forCurrentGame` tinyint(1) NOT NULL DEFAULT '1', 
		KEY `playerID_idx` (`playerID`), CONSTRAINT `chatPlayerID` FOREIGN KEY (`playerID`) REFERENCES `players` (`playerID`) ON DELETE CASCADE ON UPDATE CASCADE
	)";

	$gameTableStatement = "CREATE TABLE sweepelite.games (
	  	`gameID` int(11) NOT NULL AUTO_INCREMENT,
  		`map` varchar(20000) NOT NULL,
  		`visibility` varchar(20000) NOT NULL,
  		`height` int(11) NOT NULL,
  		`width` int(11) NOT NULL,
  		`status` varchar(45) NOT NULL,
  		`friendlyTankCountdown` int(4) NOT NULL DEFAULT '3',
  		`friendlyTanks` varchar(2000) NOT NULL,
  		`enemyTankCountdown` int(6) NOT NULL DEFAULT '15',
  		`enemyTankCountdownReset` int(6) NOT NULL DEFAULT '15',
  		`enemyTanks` varchar(2000) NOT NULL,
  		`wrecks` varchar(2000) NOT NULL,
  		`traps` varchar(2000) NOT NULL,
  		`lastUpdated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  		`fullUpdate` tinyint(1) NOT NULL DEFAULT '1',
	  	PRIMARY KEY (`gameID`)
	)";

	$globalTableStatement = "CREATE TABLE IF NOT EXISTS sweepelite.globalvars (
		`k` varchar(60) NOT NULL, 
		`v` varchar(60) NOT NULL, 
		UNIQUE KEY `key_UNIQUE` (`k`)
	)";

	$playerTableStatement = "CREATE TABLE IF NOT EXISTS sweepelite.players (
		`playerID` INT(11) NOT NULL AUTO_INCREMENT, 
		`username` VARCHAR(45) NOT NULL, 
		`password` varchar(128) NOT NULL,
		`salt` varchar(32) NOT NULL, 
		`totalScore` int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (`playerID`), 
		UNIQUE KEY `username_UNIQUE` (`username`)
	)";

	$fakePassword = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
	$fakeSalt = sec_getNewSalt();

	$highestScoreStatement = "INSERT INTO sweepelite.players (username, password, salt, totalScore) VALUES ('highestScore', " . sec_getHashedValue($fakePassword, $fakeSalt) . ", " . $fakeSalt . ", 1000)";

	$signupTableStatement = "CREATE TABLE IF NOT EXISTS sweepelite.upcomingsignup (
		`playerID` int(11) NOT NULL,
 		UNIQUE KEY `playerID_UNIQUE` (`playerID`),
  		KEY `playerIDy_idx` (`playerID`),
  		CONSTRAINT `playerIDy` FOREIGN KEY (`playerID`) REFERENCES `players` (`playerID`) ON DELETE CASCADE ON UPDATE CASCADE
	)";

	$statusTableStatement = "CREATE TABLE sweepelite.playerstatus (
		`status` int(2) NOT NULL DEFAULT '1',
		`awaitingAction` bit(1) NOT NULL,
		`gameID` int(11) NOT NULL,
		`playerID` int(11) NOT NULL,
		`afkCount` int(4) NOT NULL DEFAULT '0',
		`trapType` int(4) NOT NULL DEFAULT '0',
		`trapCooldown` int(6) NOT NULL DEFAULT '0',
		`digNumber` int(11) NOT NULL DEFAULT '0',
		`correctFlags` int(11) NOT NULL DEFAULT '0',
		KEY `gameID_idx` (`gameID`),
		KEY `playerID_idx` (`playerID`),
		CONSTRAINT `gameID` FOREIGN KEY (`gameID`) REFERENCES `games` (`gameID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
		CONSTRAINT `playerID` FOREIGN KEY (`playerID`) REFERENCES `players` (`playerID`) ON DELETE CASCADE ON UPDATE CASCADE
	)";

	$gameTableCreated = false;
	$playerTableCreated = false;
	$noErrors = true;

	if ($query = $conn->prepare($playerTableStatement)) {
		if ($query->execute()) {
			$playerTableCreated = true;
			error_log("initMySQL.php - Player table successfully created.");
		} else {
			$noErrors = false;
			error_log("initMySQL.php - Player table not created. " . $query->errno . ": " . $query->error);
			error_log("Please run the following MySQL query manually:");
			error_log($playerTableStatement);
		}
		$query->close();
	} else {
		$noErrors = false;
		error_log("initMySQL.php - Failed to prepare player table statement. " . $conn->errno . ": " . $conn->error);
		error_log("Please run the following MySQL query manually:");
		error_log($playerTableStatement);
	}

	if ($query = $conn->prepare($gameTableStatement)) {
		if ($query->execute()) {
			$gameTableCreated = true;
			error_log("initMySQL.php - Game table successfully created.");
		} else {
			$noErrors = false;
			error_log("initMySQL.php - Game table not created. " . $query->errno . ": " . $query->error);
			error_log("Please run the following MySQL query manually:");
			error_log($gameTableStatement);
		}
		$query->close();
	} else {
		$noErrors = false;
		error_log("initMySQL.php - Failed to prepare game table statement. " . $conn->errno . ": " . $conn->error);
		error_log("Please run the following MySQL query manually:");
		error_log($gameTableStatement);
	}

	if ($query = $conn->prepare($globalTableStatement)) {
		if ($query->execute()) {
			error_log("initMySQL.php - Global variables table successfully created.");
		} else {
			$noErrors = false;
			error_log("initMySQL.php - Global variables table not created. " . $query->errno . ": " . $query->error);
			error_log("Please run the following MySQL query manually:");
			error_log($globalTableStatement);
		}
		$query->close();
	} else {
		$noErrors = false;
		error_log("initMySQL.php - Failed to prepare global variables table statement. " . $conn->errno . ": " . $conn->error);
		error_log("Please run the following MySQL query manually:");
		error_log($globalTableStatement);
	}

	if ($playerTableCreated) {
		if ($query = $conn->prepare($highestScoreStatement)) {
			if ($query->execute()) {
				error_log("initMySQL.php - Highest score implemented.");
			} else {
				$noErrors = false;
				error_log("initMySQL.php - Highest score not implemented.");
				error_log("Please run the following MySQL query manually:");
				error_log($highestScoreStatement);
			}
			$query->close();
		}
		if ($query = $conn->prepare($signupTableStatement)) {
			if ($query->execute()) {
				error_log("initMySQL.php - Upcoming sign-up table successfully created.");
			} else {
				$noErrors = false;
				error_log("initMySQL.php - Upcoming sign-up table not created. " . $query->errno . ": " . $query->error);
				error_log("Please run the following MySQL query manually:");
				error_log($signupTableStatement);
			}
			$query->close();
		} else {
			$noErrors = false;
			error_log("initMySQL.php - Failed to prepare Upcoming sign-up table statement. " . $conn->errno . ": " . $conn->error);
			error_log("Please run the following MySQL query manually:");
			error_log($signupTableStatement);
		}

		if ($query = $conn->prepare($chatTableStatement)) {
			if ($query->execute()) {
				error_log("initMySQL.php - Chat table successfully created.");
			} else {
				$noErrors = false;
				error_log("initMySQL.php - Chat table not created. " . $query->errno . ": " . $query->error);
				error_log("Please run the following MySQL query manually:");
				error_log($chatTableStatement);
			}
			$query->close();
		} else {
			$noErrors = false;
			error_log("initMySQL.php - Failed to prepare Upcoming sign-up table statement. " . $conn->errno . ": " . $conn->error);
			error_log("Please run the following MySQL query manually:");
			error_log($chatTableStatement);
		}
	} else {
		$noErrors = false;
		error_log("initMySQL.php - Due to player table creation failure, several important tables were not created.");
		error_log("Player run the following MySQL queries manually:");
		error_log($signupTableStatement);
		error_log($chatTableStatement);
	}

	if ($gameTableCreated && $playerTableCreated) {
		if ($query = $conn->prepare($actionTableStatement)) {
			if ($query->execute()) {
				error_log("initMySQL.php - Action table successfully created.");
			} else {
				$noErrors = false;
				error_log("initMySQL.php - Action table not created. " . $query->errno . ": " . $query->error);
				error_log("Please run the following MySQL query manually:");
				error_log($actionTableStatement);
			}
			$query->close();
		} else {
			$noErrors = false;
			error_log("initMySQL.php - Failed to prepare action table statement. " . $conn->errno . ": " . $conn->error);
			error_log("Please run the following MySQL query manually:");
			error_log($actionTableStatement);
		}

		if ($query = $conn->prepare($statusTableStatement)) {
			if ($query->execute()) {
				error_log("initMySQL.php - Player status table successfully created.");
			} else {
				$noErrors = false;
				error_log("initMySQL.php - Player status table not created. " . $query->errno . ": " . $query->error);
				error_log("Please run the following MySQL query manually:");
				error_log($statusTableStatement);
			}
			$query->close();
		} else {
			$noErrors = false;
			error_log("initMySQL.php - Failed to prepare player status table statement. " . $conn->errno . ": " . $conn->error);
			error_log("Please run the following MySQL query manually:");
			error_log($statusTableStatement);
		}
	} else {
		$noErrors = false;
		error_log("initMySQL.php - Due to failure to create both player and game tables, several important tables were not created.");
		error_log("Player run the following MySQL queries manually:");
		error_log($actionTableStatement);
		error_log($statusTableStatement);
	}

	if ($noErrors) {
		if ($query = $conn->prepare("INSERT INTO `sweepelite`.`globalvars` (k, v) VALUES (?, ?)")) {
			$str_one = "mysqlInitialized";
			$str_two = "true";
			$query->bind_param("ss", $str_one, $str_two);
			$query->execute();
		} else {
			error_log("initMySQL.php - Failed to prepare final insertion statement. " . $conn->errno . ": " . $conn->error);
		}
	}
}
?>
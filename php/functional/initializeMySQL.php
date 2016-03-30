<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

#checkMySQL()
#Checks the global variables table to see if MySQL has been fully initialized. If not, will call initMySQL().
function checkMySQL() {
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	if ($query = $conn->prepare("SELECT v FROM multisweeper.globalvars WHERE k=?")) {
		$query->bind_param("s", "mysqlInitialized");
		$query->execute();
		if ($query->num_rows === 0) {
			initMySQL();
		}
	} else {
		error_log("Could not prepare MySQL check statement.");
	}
}

#initMySQL()
#Calls various MySQL queries to create all of the necessary tables. A global variable will be set if the creation encounters no errors.
function initMySQL() {
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("initMySQL.php - Connection failed: " . $conn->connect_error);
	}

	$actionTableStatement = "CREATE TABLE IF NOT EXISTS multisweeper.actionqueue (
		`gameID` int(11) NOT NULL, 
		`playerID` int(11) NOT NULL, 
		`actionType` bit(1) NOT NULL, 
		`xCoord` int(11) NOT NULL, 
		`yCoord` int(11) NOT NULL, 
		KEY `gameID_idx` (`gameID`), 
		KEY `playerID_idx` (`playerID`), 
		CONSTRAINT `gameIDx` FOREIGN KEY (`gameID`) REFERENCES `games` (`gameID`) ON DELETE NO ACTION ON UPDATE NO ACTION, 
		CONSTRAINT `playerIDx` FOREIGN KEY (`playerID`) REFERENCES `players` (`playerID`) ON DELETE NO ACTION ON UPDATE NO ACTION
	)";

	$chatTableStatement = "CREATE TABLE IF NOT EXISTS multisweeper.chatmessages (
		`playerID` int(11) NOT NULL, 
		`message` varchar(500) NOT NULL DEFAULT 'ERROR', 
		`time` datetime NOT NULL, 
		`forCurrentGame` tinyint(1) NOT NULL DEFAULT '1', 
		KEY `playerID_idx` (`playerID`), CONSTRAINT `chatPlayerID` FOREIGN KEY (`playerID`) REFERENCES `players` (`playerID`) ON DELETE NO ACTION ON UPDATE NO 
	)";

	$gameTableStatement = "CREATE TABLE `games` (
	  	`gameID` int(11) NOT NULL AUTO_INCREMENT,
	  	`map` varchar(20000) NOT NULL,
	  	`visibility` varchar(20000) NOT NULL,
	  	`height` int(11) NOT NULL,
	  	`width` int(11) NOT NULL,
	 	`status` varchar(45) NOT NULL,
	  	`friendlyTankCountdown` int(4) NOT NULL DEFAULT '3',
	  	`friendlyTanks` varchar(2000) NOT NULL,
	  	`enemyTankCountdown` int(6) NOT NULL DEFAULT '15',
	  	`enemyTankCountdownReset` int(6) NOT NULL DEFAULT '14',
	  	`enemyTanks` varchar(2000) NOT NULL,
	  	`wrecks` varchar(2000) NOT NULL,
	  	PRIMARY KEY (`gameID`)
	)";

	$globalTableStatement = "CREATE TABLE IF NOT EXISTS multisweeper.globalvars (
		`k` varchar(60) NOT NULL, 
		`v` varchar(60) NOT NULL, 
		UNIQUE KEY `key_UNIQUE` (`k`)
	)";

	$playerTableStatement = "CREATE TABLE IF NOT EXISTS multisweeper.players (
		`playerID` INT NOT NULL AUTO_INCREMENT, 
		`username` VARCHAR(45) NOT NULL, 
		`password` VARCHAR(45) NOT NULL, 
		PRIMARY KEY (`playerID`), 
		UNIQUE INDEX `username_UNIQUE` (`username` ASC)
	)";

	$signupTableStatement = "CREATE TABLE IF NOT EXISTS multisweeper.upcomingsignup (
		`playerID` int(11) NOT NULL, 
		KEY `playerIDy_idx` (`playerID`),
		CONSTRAINT `playerIDy` FOREIGN KEY (`playerID`) REFERENCES `players` (`playerID`) ON DELETE NO ACTION ON UPDATE NO ACTION
	)";

	$statusTableStatement = "CREATE TABLE IF NOT EXISTS multisweeper.playerstatus (
		`status` int(2) NOT NULL DEFAULT '1', 
		`awaitingAction` bit(1) NOT NULL, `gameID` int(11) NOT NULL, 
		`playerID` int(11) NOT NULL, 
		`afkCount` int(4) NOT NULL DEFAULT '0', 
		KEY `gameID_idx` (`gameID`), 
		KEY `playerID_idx` (`playerID`), 
		CONSTRAINT `gameID` FOREIGN KEY (`gameID`) REFERENCES `games` (`gameID`) ON DELETE NO ACTION ON UPDATE NO ACTION, 
		CONSTRAINT `playerID` FOREIGN KEY (`playerID`) REFERENCES `players` (`playerID`) ON DELETE NO ACTION ON UPDATE NO ACTION
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
		if ($query = $conn->prepare("INSERT INTO `multisweeper`.`globalvars` (k, v) VALUES (?, ?)")) {
			$query->bind_param("ss", "mysqlInitialized", "true");
			$query->execute();
		} else {
			error_log("initMySQL.php - Failed to prepare final insertion statement. " . $conn->errno . ": " . $conn->error);
		}
	}
}
?>
<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

function getLatestGameID() {
	global $sqlhost, $sqlusername, $sqlpassword;

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("getLatestGameID.php - Connection failed: " . $conn->connect_error);
	}

	#Select all information about the game from the game's status columns in the MySQL database and parse it into XML form. 
	if ($query = $conn->prepare("SELECT gameID FROM multisweeper.games ORDER BY gameID DESC LIMIT 1")) {
		$gameID = null;
		$query->execute();
		$query->bind_result($gameID);
		$query->fetch();
		$query->close();

		if ($gameID !== null) {
			return $gameID;
		}
	} else {
		error_log("getLatestGameID.php - Unable to prepare statement. " . $conn->errno . ": " . $conn->error);
	}

	return null;
}

?>
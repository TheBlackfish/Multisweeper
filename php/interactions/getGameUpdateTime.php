<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

function getGameUpdateTime($gameID) {
	global $sqlhost, $sqlusername, $sqlpassword;

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("getGameUpdateTime.php - Connection failed: " . $conn->connect_error);
	}

	#Select all information about the game from the game's status columns in the MySQL database and parse it into XML form. 
	if ($query = $conn->prepare("SELECT lastUpdated FROM multisweeper.games WHERE gameID=?")) {
		$lastUpdated = null;

		$query->bind_param("i", $gameID);
		$query->execute();
		$query->bind_result($lastUpdated);
		$query->fetch();
		$query->close();

		if ($lastUpdated !== null) {
			$dateTime = new DateTime($lastUpdated);
			return $dateTime->getTimestamp();
		}
	} else {
		error_log("getGameUpdateTime.php - Unable to prepare statement. " . $conn->errno . ": " . $conn->error);
	}

	return 0;
}

?>
<?php

#This file contains functionality relating to retrieving the last updated time for game updates.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

#getGameUpdateTime($gameID)
#Returns the most recent Unix timestamp of the game requested.
#@param gameID (int) The ID of the game to retrieve.
#@return The Unix timestamp of the most recent chat message.
function getGameUpdateTime($gameID) {
	global $sqlhost, $sqlusername, $sqlpassword;

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("getGameUpdateTime.php - Connection failed: " . $conn->connect_error);
	}

	#Select all information about the game from the game's status columns in the MySQL database and parse it into XML form. 
	if ($query = $conn->prepare("SELECT UNIX_TIMESTAMP(lastUpdated) FROM multisweeper.games WHERE gameID=?")) {
		$lastUpdated = null;

		$query->bind_param("i", $gameID);
		$query->execute();
		$query->bind_result($lastUpdated);
		$query->fetch();
		$query->close();

		if ($lastUpdated !== null) {
			return $lastUpdated;
		}
	} else {
		error_log("getGameUpdateTime.php - Unable to prepare statement. " . $conn->errno . ": " . $conn->error);
	}

	return 0;
}

?>
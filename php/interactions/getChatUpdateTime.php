<?php

#This file contains functionality relating to retrieving the last updated time for chat messages.

require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/constants/databaseConstants.php');

#getChatUpdateTime()
#Returns the most recent Unix timestamp of chat messages.
#@return The Unix timestamp of the most recent chat message.
function getChatUpdateTime() {
	global $sqlhost, $sqlusername, $sqlpassword;

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("getChatUpdateTime.php - Connection failed: " . $conn->connect_error);
	}

	#Select all information about the game from the game's status columns in the MySQL database and parse it into XML form. 
	if ($query = $conn->prepare("SELECT UNIX_TIMESTAMP(time) FROM sweepelite.chatmessages ORDER BY time DESC LIMIT 1")) {
		$lastUpdated = null;

		$query->execute();
		$query->bind_result($lastUpdated);
		$query->fetch();
		$query->close();

		if ($lastUpdated !== null) {
			return $lastUpdated;
		}
	} else {
		error_log("getChatUpdateTime.php - Unable to prepare statement. " . $conn->errno . ": " . $conn->error);
	}

	return 0;
}

?>
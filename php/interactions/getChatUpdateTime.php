<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

function getChatUpdateTime() {
	global $sqlhost, $sqlusername, $sqlpassword;

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("getChatUpdateTime.php - Connection failed: " . $conn->connect_error);
	}

	#Select all information about the game from the game's status columns in the MySQL database and parse it into XML form. 
	if ($query = $conn->prepare("SELECT time FROM multisweeper.chatmessages ORDER BY time DESC LIMIT 1")) {
		$lastUpdated = null;

		$query->execute();
		$query->bind_result($lastUpdated);
		$query->fetch();
		$query->close();

		if ($lastUpdated !== null) {
			$dateTime = new DateTime($lastUpdated);
			return $dateTime->getTimestamp();
		}
	} else {
		error_log("getChatUpdateTime.php - Unable to prepare statement. " . $conn->errno . ": " . $conn->error);
	}

	return 0;
}

?>
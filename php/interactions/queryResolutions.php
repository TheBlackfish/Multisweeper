<?php

#This file handles querying for automatic action resolutions.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

#queryResolutions($gameID)
#Checks if any more players need to submit actions for the game ID provided.
#@param gameID (int) The ID of the game to check for.
#@return The number of seconds until the next action resolution if all players are accounted for, or -1 if we are still waiting on action submissions.
function queryResolutions($gameID) {
	global $sqlhost, $sqlusername, $sqlpassword;

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		error_log("queryResolutions.php - Connection failed: " . $conn->connect_error);
		return -1;
	}

	if ($statusStmt = $conn->prepare("SELECT status, awaitingAction FROM multisweeper.playerstatus WHERE gameID=?")) {
		$allActed = true;

		$statusStmt->bind_param("i", $gameID);
		$statusStmt->execute();
		$statusStmt->bind_result($status, $awaiting);
		while ($statusStmt->fetch()) {
			if ((intval($awaiting) === 1) && (intval($status) === 1)) {
				$allActed = false;
			}
		}

		if ($allActed) {
			return 5;
		}
	}

	return -1;
}

?>
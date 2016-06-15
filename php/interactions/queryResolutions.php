<?php

#This file handles querying for automatic action resolutions.

require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/constants/databaseConstants.php');

#queryResolutions($gameID)
#Checks if any more players need to submit actions for the game ID provided.
#@param gameID (int) The ID of the game to check for.
#@return The amount of time before an auto-resolution should be scheduled for, or -1 if no auto-resolution should be set beyond the one set in the main control loop.
function queryResolutions($gameID) {
	global $sqlhost, $sqlusername, $sqlpassword;

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		error_log("queryResolutions.php - Connection failed: " . $conn->connect_error);
		return -1;
	}

	if ($statusStmt = $conn->prepare("SELECT status, awaitingAction FROM sweepelite.playerstatus WHERE gameID=?")) {
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
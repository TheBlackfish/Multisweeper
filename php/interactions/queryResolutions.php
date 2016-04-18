<?php

#Takes in an XML formatted for action submission with the following items:
#playerID, gameID, xCoord, yCoord, actionType
#These get inserted into the action queue, then the player status for that game is updated so that we are no longer waiting for an action from the player specified.
#If all players in the game who are alive have then submitted actions, the game is updated via the action queue.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

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
			error_log("queryResolutions.php - We think all players have submitted their actions, reducing to 15 seconds.");
			return 15;
		}
	}

	return -1;
}

?>
<?php

#This file checks the log-in information of a player and returns an XML form explaining if it was successful or not.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/security.php');

function logInPlayer($xml) {
	global $sqlhost, $sqlusername, $sqlpassword;
	global $numTraps;

	if (($xml->username == null) || ($xml->password == null)) {
		error_log("logInPlayer.php - Login rejected.");
		return -1;
	} else {
		$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
		if ($conn->connect_error) {
			error_log("logInPlayer.php - Connection failed: " . $conn->connect_error);
			return -1;
		}

		#Check if user exists.
		if ($stmt = $conn->prepare("SELECT playerID, password, salt FROM multisweeper.players WHERE username=?")) {
			$playerID = null;

			$stmt->bind_param("s", $xml->username);
			$stmt->execute();
			$stmt->bind_result($id, $controlPW, $salt);
			while ($stmt->fetch()) {
				$playerID = $id;
			}
			$stmt->close();

			#Check that the password is correct.
			$clientPW = sec_getHashedValue($xml->password, $salt);
			if ($clientPW !== $controlPW) {
				error_log("logInPlayer.php - Password failed the potato test.");
				return -1;
			}

			if ($playerID != null) {
				#Check if player is currently a part of the most recent game.
				if ($statusStmt = $conn->prepare("SELECT g.gameID FROM multisweeper.playerstatus AS p INNER JOIN (SELECT gameID FROM multisweeper.games ORDER BY gameID DESC LIMIT 1) as g ON p.gameID = g.gameID WHERE playerID=?")) {
					$gameID = null;
					$statusStmt->bind_param("i", $playerID);
					$statusStmt->execute();
					$statusStmt->bind_result($gid);
					while ($statusStmt->fetch()) {
						$gameID = $gid;
					}
					$statusStmt->close();
					if ($gameID === null) {

						#Sign player up for game.
						if ($gameIDStmt = $conn->prepare("SELECT gameID FROM multisweeper.games ORDER BY gameID DESC LIMIT 1")) {
							$gameIDStmt->execute();
							$gameIDStmt->bind_result($gid);
							while ($gameIDStmt->fetch()) {
								$gameID = $gid;
							}
							$gameIDStmt->close();
							if ($gameID !== null) {
								if ($signupStmt = $conn->prepare("INSERT INTO multisweeper.playerstatus (gameID, playerID, trapType, awaitingAction) VALUES (?, ?, ?, 1)")) {
									$trapID = ($gameID + $playerID) % $numTraps;
									$signupStmt->bind_param("iii", $gameID, $playerID, $trapID);
									$signupStmt->execute();
									$signupStmt->close();
								} else {
									error_log("loginPlayer.php - Unable to prepare sign up statement after logging in. " . $conn->errno . ": " . $conn->error);
								}
							} else {
								error_log("loginPlayer.php - Unable to retrieve latest game ID. " . $conn->errno . ": " . $conn->error);
							}
						} else {
							error_log("loginPlayer.php - Unable to prepare game ID retrieval statement after logging in. " . $conn->errno . ": " . $conn->error);
						}
					} 
				} else {
					error_log("loginPlayer.php - Unable to prepare checking statement after logging in. " . $conn->errno . ": " . $conn->error);
				}
				return $playerID;
			} else {
				error_log("logInPlayer.php - Unable to log in.");
			}
		} else {
			error_log("loginPlayer.php - Unable to prepare statement for logging in.");
		}
	}
	return -1;
}

?>
<?php

#Takes in an XML formatted for action submission with the following items:
#playerID, gameID, xCoord, yCoord, actionType
#These get inserted into the action queue, then the player status for that game is updated so that we are no longer waiting for an action from the player specified.
#If all players in the game who are alive have then submitted actions, the game is updated via the action queue.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

#submitAction($playerID, $gameID, $xml)
#Takes in an XML describing an action from the player specified for the game specified. This action is then parsed and inserted into the action queue.
#@param playerID (int) The ID of the player submitting the action.
#@param gameID (int) The ID of the game taking the action.
#@param xml (XML) The xml describing the action being submitted.
#return The XML describing any errors encountered and the success or failure of action submission.
function submitAction($playerID, $gameID, $xml) {
	global $sqlhost, $sqlusername, $sqlpassword;

	$result = new SimpleXMLElement("<action/>");

	if ($playerID > -1) {
		if (($xml->xCoord == null) or ($xml->yCoord == null) or ($xml->actionType == null)) {
			$error = $result->createElement('actionError', "Incomplete data in submission. Please try again.");
			$error = $resultBase->appendChild($error);
		} else {
			$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
			if ($conn->connect_error) {
				error_log("submitAction.php - Connection failed: " . $conn->connect_error);
				$error = $result->addChild('actionError', "Internal error occurred, please try again later.");
			}

			#Delete any previous actions from this player
			if ($deleteStmt = $conn->prepare("DELETE FROM multisweeper.actionqueue WHERE playerID=? AND gameID=?")) {
				$deleteStmt->bind_param("ii", $xml->playerID, $gameID);
				$deleteStmt->execute();
				$deleteStmt->close();
			} else {
				error_log("submitAction.php - Unable to prepare delete statement, forging ahead anyways. " . $conn->errno . ": " . $conn->error);
			}

			#Check if player can actually submit actions or not
			if ($openGameStmt = $conn->prepare("SELECT status FROM multisweeper.games WHERE gameID=?")) {
				$openGameStmt->bind_param("i", $gameID);
				$openGameStmt->execute();
				$openGameStmt->bind_result($gameStatus);
				$openGameStmt->fetch();
				$openGameStmt->close();

				if ($gameStatus === "OPEN") {
					if ($aliveStmt = $conn->prepare("SELECT COUNT(*) FROM multisweeper.playerstatus WHERE gameID=? AND playerID=? AND status!=0")) {
						$aliveStmt->bind_param("ii", $gameID, $playerID);
						$aliveStmt->execute();
						$aliveStmt->bind_result($count);
						$aliveStmt->fetch();
						$aliveStmt->close();

						if ($count > 0) {
							#Add the current action they have queued up instead.
							if ($insertStmt = $conn->prepare("INSERT INTO multisweeper.actionqueue (playerID, gameID, xCoord, yCoord, actionType) VALUES (?, ?, ?, ?, ?)")) {
								$insertStmt->bind_param("iiiii", $playerID, $gameID, $xml->xCoord, $xml->yCoord, $xml->actionType);
								$updated = $insertStmt->execute();
								if (!$updated) {
									error_log("submitAction.php - Error occurred inserting player action into queue. " . $insertStmt->errno . ": " . $insertStmt->error);
									$error = $result->addChild('error', "Internal error occurred, please try again later.");
									$insertStmt->close();
								} else {
									$insertStmt->close();
									$error = $result->addChild('action', "Action submitted!");
									
									#Update current player status to set current player's action awaiting status to false
									if ($updateStmt = $conn->prepare("UPDATE multisweeper.playerstatus SET awaitingAction=0 WHERE playerID=? AND gameID=?")) {
										$updateStmt->bind_param("ii", $playerID, $gameID);
										$updateStmt->execute();
										$updateStmt->close();
									} else {
										error_log("submitAction.php - Error occurred updating the fact that a player has submitted an action.");
									}
								}
							} else {
								error_log("submitAction.php - Unable to prepare insert statement, need to fail. " . $conn->errno . ": " . $conn->error);
								$error = $result->addChild('actionError', "Internal error occurred, please try again later.");
							}
						} else {
							error_log("submitAction.php - Player not allowed to submit actions.");

							#Find out why player is not allowed to submit actions.
							if ($deadStmt = $conn->prepare("SELECT COUNT(*) FROM multisweeper.playerstatus WHERE gameID=? AND playerID=? AND status=0")) {
								$deadStmt->bind_param("ii", $xml->playerID, $gameID);
								$deadStmt->execute();
								$deadStmt->bind_result($count);
								$deadStmt->fetch();
								$deadStmt->close();

								if ($count > 0) {
									$error = $result->addChild('actionError', "You are dead.");
								} else {
									$error = $result->addChild('actionError', "You are not a participant in this game.");
								}
							} else {
								error_log("submitAction.php - Unable to prepare dead check statement. " . $conn->errno . ": " . $conn->error);
								$error = $result->addChild('actionError', "You are not allowed to participate in this game at this time.");
							}
						}
					}
				} else {
					$error = $result->addChild('actionError', "This game is over. Please wait for the next game to be deployed.");
				}
			}
		}
	} else {
		$error = $result->addChild('actionError', "You must be logged in to submit actions.");
	}

	return str_replace('<?xml version="1.0"?>', "", $result->asXML());
}

?>
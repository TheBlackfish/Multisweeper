<?php

/*
	Takes in an XML formatted for action submission with the following items:
	playerID, gameID, xCoord, yCoord, actionType
	These get inserted into the action queue, then the player status for that game is updated so that we are no longer waiting for an action from the player specified.
	If all players in the game who are alive have then submitted actions, the game is updated via the action queue.
*/

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/resolveActions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/taskScheduler.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');

	$xml = simplexml_load_file('php://input');
	$shouldResolve = false;

	$result = new DOMDocument('1.0');
	$result->formatOutput = true;
	$resultBase = $result->createElement('submission');
	$resultBase = $result->appendChild($resultBase);

	if (($xml->playerID == null) or ($xml->gameID == null) or ($xml->xCoord == null) or ($xml->yCoord == null) or ($xml->actionType == null)) {
		error_log("Action rejected");
		$error = $result->createElement('error', "Incomplete data in submission. Please try again.");
		$error = $resultBase->appendChild($error);
	} else {
		//Delete any previously scheduled resolution tasks.
		deleteResolveActionsTask($xml->gameID);

		$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}

		//Delete any previous actions from this player
		if ($deleteStmt = $conn->prepare("DELETE FROM multisweeper.actionqueue WHERE playerID=? AND gameID=?")) {
			$deleteStmt->bind_param("ii", $xml->playerID, $xml->gameID);
			$deleteStmt->execute();
			$deleteStmt->close();
		} else {
			error_log("Unable to prepare delete statement, forging ahead anyways. " . $conn->errno . ": " . $conn->error);
		}

		//Check if player can actually submit actions or not
		//Is the current game not open?
		if ($openGameStmt = $conn->prepare("SELECT status FROM multisweeper.games WHERE gameID=?")) {
			$openGameStmt->bind_param("i", $xml->gameID);
			$openGameStmt->execute();
			$openGameStmt->bind_result($gameStatus);
			$openGameStmt->fetch();
			$openGameStmt->close();

			if ($gameStatus === "OPEN") {
				if ($aliveStmt = $conn->prepare("SELECT COUNT(*) FROM multisweeper.playerstatus WHERE gameID=? AND playerID=? AND status!=0")) {
					$aliveStmt->bind_param("ii", $xml->gameID, $xml->playerID);
					$aliveStmt->execute();
					$aliveStmt->bind_result($count);
					$aliveStmt->fetch();
					$aliveStmt->close();

					if ($count > 0) {
						//Add the current action they have queued up instead.
						if ($insertStmt = $conn->prepare("INSERT INTO multisweeper.actionqueue (playerID, gameID, xCoord, yCoord, actionType) VALUES (?, ?, ?, ?, ?)")) {
							$insertStmt->bind_param("iiiii", $xml->playerID, $xml->gameID, $xml->xCoord, $xml->yCoord, $xml->actionType);
							$updated = $insertStmt->execute();
							if (!$updated) {
								error_log("Error occurred inserting player action into queue. " . $insertStmt->errno . ": " . $insertStmt->error);
								$error = $result->createElement('error', "Internal error occurred, please try again later.");
								$error = $resultBase->appendChild($error);
								$insertStmt->close();
							} else {
								$insertStmt->close();
								$error = $result->createElement('action', "Action submitted!");
								$error = $resultBase->appendChild($error);
								
								//Update current player status to set current player's action awaiting status to false
								if ($updateStmt = $conn->prepare("UPDATE multisweeper.playerstatus SET awaitingAction=0 WHERE playerID=? AND gameID=?")) {
									$updateStmt->bind_param("ii", $xml->playerID, $xml->gameID);
									$updateStmt->execute();
									$updateStmt->close();
								} else {
									error_log("Error occurred updating the fact that a player has submitted an action. Can manually run the action resolution later.");
								}

								//Check if all players have submitted actions. If so, resolve the action queue.
								if ($checkStmt = $conn->prepare("SELECT COUNT(*) FROM multisweeper.playerstatus WHERE gameID=? AND awaitingAction=1 AND status=1")) {
									$checkStmt->bind_param("i", $xml->gameID);
									$checkStmt->execute();
									$checkStmt->bind_result($count);
									$checkStmt->fetch();
									$checkStmt->close();
									if ($count === 0) {
										$shouldResolve = true;
									} else {
										//Schedule timeout for next automatic resolution.
										createResolveActionsTask($xml->gameID);
									}
								} else {
									error_log("Unable to prepare check status statement. " . $conn->errno . ": " . $conn->error);
								}
							}
						} else {
							error_log("Unable to prepare insert statement, need to fail. " . $conn->errno . ": " . $conn->error);
							$error = $result->createElement('error', "Internal error occurred, please try again later.");
							$error = $resultBase->appendChild($error);
						}
					} else {
						error_log("Player not allowed to submit actions.");

						//Find out why player is not allowed to submit actions.
						if ($deadStmt = $conn->prepare("SELECT COUNT(*) FROM multisweeper.playerstatus WHERE gameID=? AND playerID=? AND status=0")) {
							$deadStmt->bind_param("ii", $xml->playerID, $xml->gameID);
							$deadStmt->execute();
							$deadStmt->bind_result($count);
							$deadStmt->fetch();
							$deadStmt->close();

							if ($count > 0) {
								$error = $result->createElement('error', "You are dead.");
								$error = $resultBase->appendChild($error);
							} else {
								$error = $result->createElement('error', "You are not a participant in this game.");
								$error = $resultBase->appendChild($error);
							}
						} else {
							error_log("Unable to prepare dead check statement. " . $conn->errno . ": " . $conn->error);
							$error = $result->createElement('error', "You are not allowed to participate in this game at this time.");
							$error = $resultBase->appendChild($error);
						}
					}
				}
			} else {
				$error = $result->createElement('error', "This game is over. Please wait for the next game to be deployed.");
				$error = $resultBase->appendChild($error); 
			}
		}
	}

	$r = $result->saveXML();
	echo $r;

	if ($shouldResolve) {
		error_log("We think we should resolve all actions, resolving");
		resolveAllActions($xml->gameID);
	}
}

?>
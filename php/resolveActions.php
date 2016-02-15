<?php

require_once('mineGameConstants.php');
require_once('translateData.php');

function resolveAllActions(gameID) {
	//Prepare MySQL connection
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	//Get map for game and visibility for the game.
	if ($stmt = $conn->prepare("SELECT map, visibility, height, width FROM multisweeper.games WHERE gameID=?")) {
		$stmt->bind_param("i", $gameID);
		$stmt->execute();
		$stmt->bind_result($m, $v, $h, $w);
		if ($stmt->fetch()) {
			$minefield = translateMinefieldToPHP($m, $h, $w);
			$visibility = translateMinefieldToPHP($v, $h, $w);
			
			//Get all actions for that game.
			if ($actionStmt = $conn->prepare("SELECT playerID, actionType, xCoord, yCoord FROM multisweeper.actionqueue WHERE gameID=?")) {
				$actionqueue = array();
				$playerstatus = array();

				$actionStmt->bind_param("i", $gameID);
				$actionStmt->execute();
				$actionStmt->bind_result($playerID, $actionType, $xCoord, $yCoord);

				while ($actionStmt->fetch()) {
					$temp = array(
						"playerID"		=>	$playerID,
						"actionType"	=>	$actionType,
						"x"				=>	$xCoord,
						"y"				=>	$yCoord
					);
					array_push($actionqueue, $temp);
				}

				if (count($actionqueue) > 0) {
					
					//For each action,
					while (count($actionqueue) > 0) {
						$cur = array_shift($actionqueue);

						//Add player to player status with alive status if they are not currently in there.
						if (!array_key_exists($cur["playerID", $playerstatus)) {
							$playerstatus[$cur["playerID"]] = 1;
						}

						//If shovel action
						if ($cur["actionType"] == 0) {
							//Reveal tile at coordinates
							$visibility[$cur["x"]][$cur["y"]] = 2;

							//If tile value is a mine,
							if ($minefield[$cur["x"]][$cur["y"]] == "M") {
								//Kill player
								$playerstatus[$cur["playerID"]] = 0;

							//If tile value is 0,
							} else if ($minefield[$cur["x"]][$cur["y"]] == 0) {
								//Add actions to queue that reveal all adjacent tiles
								foreach ($adjacencies as $adj) {
									$targetX = $cur["x"] + $adj[0];
									$targetY = $cur["y"] + $adj[1];

									$shouldAdd = true;

									if (($targetX < 0) or ($targetX >= $w)) {
										$shouldAdd = false;
									}
									if (($targetY < 0) or ($targetY >= $height)) {
										$shouldAdd = false;
									}

									if ($shouldAdd) {
										$newAction = array(
											"playerID"		=> $cur["playerID"],
											"actionType"	=> $cur["actionType"],
											"x"				=> $targetX,
											"y"				=> $targetY
										);
										array_push($actionqueue, $newAction);
									}
								}
							}
							
						//If flag action, don't actually implement because yeah
						} else {
							//TBD
						}
					}
						
					//Update map and visibility values for the game by saving to database.
					if ($updateStmt = $conn->prepare("UPDATE multisweeper.games SET map=?, visibility=? WHERE gameID=?")) {
						$updateStmt->bind_param("ssi", translateMinefieldToMySQL($minefield), translateMinefieldToMySQL($visibility), $gameID);
						$updated = $updateStmt->execute();

						if ($updated === false) {
							error_log("Error occurred during map update, full text: " . $updateStmt->error);
						} else {

							//Update all remaining players in game to be awaiting actions.
							foreach ($playerstatus as $id => $isAlive) {
								if ($updatePlayer = $conn->prepare("UPDATE multisweeper.playerstatus SET status=?, awaitingAction=1 WHERE gameID=? AND playerID=?")) {
									$updatePlayer->bind_param("iii", $isAlive, $gameID, $id);
									$updated = $updatePlayer->execute();

									if ($updated === false) {
										error_log("Error occurred during player status update, full text: " . $updatePlayer->error);
									}
								} else {
									error_log("Unable to prepare player status update after resolving action queue.");
								}
							}
						}
					} else {
						error_log("Unable to prepare map update after resolving action queue.");
					}
				} else {
					error_log("Found 0 actions in queue! Most likely something went terribly wrong!");
				}
			} else {
				error_log("Unable to prepare action statement for resolving action queue.");
			}
		} else {
			error_log("Unable to resolve map statement for resolving action queue.");
		}
	} else {
		error_log("Unable to prepare map statement for resolving action queue.");
	}
}

?>
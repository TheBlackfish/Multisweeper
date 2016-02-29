<?php

#This file contains the function 'resolveAllActions' to help resolve actions in the action queue appropriately.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/taskScheduler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/translateData.php');

#resolveAllActions($gameID)
#Takes all actions from the action queue relating to the game identified by $gameID and applies those actions to the game. All changes are applied to a local copy before uploading to the MySQL database.
#@param $gameID (Integer) The game ID that this operation is for.
function resolveAllActions($gameID) {
	global $sqlhost, $sqlusername, $sqlpassword, $adjacencies;

	#Initialize the connection to the MySQL database.
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	#Get map information, both the minefield and the visibility, for this game.
	if ($stmt = $conn->prepare("SELECT map, visibility, height, width FROM multisweeper.games WHERE gameID=?")) {
		$stmt->bind_param("i", $gameID);
		$stmt->execute();
		$stmt->bind_result($m, $v, $h, $w);
		if ($stmt->fetch()) {
			$stmt->close();
			$minefield = translateMinefieldToPHP($m, $h, $w);
			$visibility = translateMinefieldToPHP($v, $h, $w);
			
			#Retrieve all actions in the action queue for this game and throw them into unique objects in an array for easy access during resolution.
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

				$actionStmt->close();

				if (count($actionqueue) > 0) {
					while (count($actionqueue) > 0) {
						$cur = array_shift($actionqueue);

						#Add the ID for the current player
						if (!array_key_exists($cur["playerID"], $playerstatus)) {
							$playerstatus[$cur["playerID"]] = 1;
						}

						#Action Type 0 is a shovel action.
						#When shoveling, the current tile is revealed no matter what.
						#If the tile is a mine, this kills the current player.
						#If the tile had a value of 0, new actions are added to the queue for revealing all adjacent tiles to the shoveled tile, barring any that are already not flagged or visible. 
						if ($cur["actionType"] == 0) {
							$visibility[$cur["x"]][$cur["y"]] = 2;
							if ($minefield[$cur["x"]][$cur["y"]] === "M") {
								$playerstatus[$cur["playerID"]] = 0;
							} else if ($minefield[$cur["x"]][$cur["y"]] == 0) {
								foreach ($adjacencies as $adj) {
									$targetX = $cur["x"] + $adj[0];
									$targetY = $cur["y"] + $adj[1];
									$shouldAdd = true;
									if (($targetX < 0) or ($targetX >= $w)) {
										$shouldAdd = false;
									}
									if (($targetY < 0) or ($targetY >= $h)) {
										$shouldAdd = false;
									}
									if ($shouldAdd) {
										if ($visibility[$targetX][$targetY] == 0) {
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
							}
						#Action Type 1 is a flag action.
						#If the tile is unrevealed, a flag is placed there instead.
						} else if ($cur["actionType"] == 1) {
							if ($visibility[$cur["x"]][$cur["y"]] == 0) {
								$visibility[$cur["x"]][$cur["y"]] = 1;
							}
						}
					}

					#Any players who are in the game but did not have an action in the queue are set to AFK.
					if ($afkStmt = $conn->prepare("SELECT playerID FROM multisweeper.playerstatus WHERE status=1 AND awaitingAction=1 AND gameID=?")) {
						$afkStmt->bind_param("i", $gameID);
						$afkStmt->execute();
						$afkStmt->bind_result($afkID);
						$afkPlayers = array();
						while ($afkStmt->fetch()) {
							array_push($afkPlayers, $afkID);
						}
						$afkStmt->close();

						if ($afkUpdateStmt = $conn->prepare("UPDATE multisweeper.playerstatus SET status=2 WHERE gameID=? AND playerID=?")) {
							foreach ($afkPlayers as $key => $value) {
								$afkUpdateStmt->bind_param("ii", $gameID, $value);
								$afkUpdateStmt->execute();
							}
							$afkUpdateStmt->close();
						}
					}

					#All players who did submit actions are updated appropriately.
					foreach ($playerstatus as $id => $isAlive) {
						if ($updatePlayer = $conn->prepare("UPDATE multisweeper.playerstatus SET status=?, awaitingAction=1 WHERE gameID=? AND playerID=?")) {
							$updatePlayer->bind_param("iii", $isAlive, $gameID, $id);
							$updated = $updatePlayer->execute();
							if ($updated === false) {
								error_log("Error occurred during player status update. " . $updatePlayer->errno . ": " . $updatePlayer->error);
								$updatePlayer->close();
							} else {
								$updatePlayer->close();

								#Empty the action queue of actions relating to this specific game.
								if ($deleteStmt = $conn->prepare("DELETE FROM multisweeper.actionqueue WHERE gameID=?")) {
									$deleteStmt->bind_param("i", $gameID);
									$updated = $deleteStmt->execute();
									if ($updated === false) {
										error_log("Error occurred during action queue clean up. " . $deleteStmt->errno . ": " . $deleteStmt->error);
										$deleteStmt->close();
									} else {
										$deleteStmt->close();

										#Try to determine if the game is complete or not.
										$gameCompleted = false;
										if ($checkLivingPlayers = $conn->prepare("SELECT COUNT(playerID) FROM multisweeper.playerstatus WHERE gameID=? AND status=1")) {
											$checkLivingPlayers->bind_param("i", $gameID);
											$checkLivingPlayers->execute();
											$checkLivingPlayers->bind_result($count);
											$checkLivingPlayers->fetch();
											$checkLivingPlayers->close();
											if ($count > 0) {
												$gameCompleted = true;
												for ($x = 0; ($x < count($visibility)) && $gameCompleted; $x++) {
													for ($y = 0; ($y < count($visibility[$x])) && $gameCompleted; $y++) {
														if ($visibility[$x][$y] == 0) {
															if ($minefield[$x][$y] === "M") {
																$gameCompleted = false;
															}
														}
													}
												}
											} else {
												$gameCompleted = true;
											}
										} else {
											error_log("Unable to prepare living player status statement after resolving action queue. " . $conn->errno . ": " . $conn->error);
										}

										#If game is done, all unrevealed tiles become visible instead.
										if ($gameCompleted) {
											for ($x = 0; ($x < count($visibility)); $x++) {
												for ($y = 0; ($y < count($visibility[$x])); $y++) {
													if ($visibility[$x][$y] == 0) {
														$visibility[$x][$y] = 2;
													}
												}
											}
										}

										#Update map and visibility values for the game by saving to database.
										if ($updateStmt = $conn->prepare("UPDATE multisweeper.games SET map=?, visibility=?, status=? WHERE gameID=?")) {
											$statusStr = "OPEN";
											if ($gameCompleted) {
												$statusStr = "DONE";
											}
											$updateStmt->bind_param("sssi", translateMinefieldToMySQL($minefield), translateMinefieldToMySQL($visibility), $statusStr, $gameID);
											$updated = $updateStmt->execute();
											if ($updated === false) {
												error_log("Error occurred during map update. " . $updateStmt->errno . ": " . $updateStmt->error);
											} 
											$updateStmt->close();

											if ($gameCompleted) {
												createGameCreationTask();
											}
										} else {
											error_log("Unable to prepare map update after resolving action queue. " . $conn->errno . ": " . $conn->error);
										}
									}
								} else {
									error_log("Unable to prepare delete statement. " . $conn->errno . ": " . $conn->error);
								}
							}
						} else {
							error_log("Unable to prepare player status update after resolving action queue. " . $conn->errno . ": " . $conn->error);
						}
					}
				} else {
					error_log("Found 0 actions in queue! Most likely something went terribly wrong!");
				}
			} else {
				error_log("Unable to prepare action statement for resolving action queue. " . $conn->errno . ": " . $conn->error);
			}
		} else {
			error_log("Unable to resolve map statement for resolving action queue. " . $stmt->errno . ": " . $stmt->error);
		}
	} else {
		error_log("Unable to prepare map statement for resolving action queue. " . $conn->errno . ": " . $conn->error);
	}
}

?>
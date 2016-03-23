<?php

#This file contains the function 'resolveAllActions' to help resolve actions in the action queue appropriately.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/taskScheduler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/translateData.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/updateTanks.php');

#resolveAllActions($gameID)
#Takes all actions from the action queue relating to the game identified by $gameID and applies those actions to the game. All changes are applied to a local copy before uploading to the MySQL database.
#@param $gameID (Integer) The game ID that this operation is for.
function resolveAllActions($gameID) {
	global $sqlhost, $sqlusername, $sqlpassword, $adjacencies;

	#Initialize the connection to the MySQL database.
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("resolveActions.php - Connection failed: " . $conn->connect_error);
	}

	#Get map information, both the minefield and the visibility, for this game.
	if ($stmt = $conn->prepare("SELECT map, visibility, height, width, tankCountdown, tanks FROM multisweeper.games WHERE gameID=?")) {
		$stmt->bind_param("i", $gameID);
		$stmt->execute();
		$stmt->bind_result($m, $v, $h, $w, $tankCount, $t);
		if ($stmt->fetch()) {
			$stmt->close();
			$minefield = translateMinefieldToPHP($m, $h, $w);
			$visibility = translateMinefieldToPHP($v, $h, $w);
			$allTanks = translateTanksToPHP($t);

			#Determine if we need to expand the map first due to tank movement.
			$expand = false;
			
			foreach ($allTanks as $tankKey => $tankPos) {
				if ($tankPos[0] == ($w - 1)) {
					$expand = true;
				}
			}

			if ($expand) {
				error_log("Expanding field...");
				$newVals = expandMinefield($minefield, $visibility, 10, 0);
				$minefield = $newVals["minefield"];
				$visibility = $newVals["visibility"];
			}

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
					$shoveledTiles = array();

					while (count($actionqueue) > 0) {
						$cur = array_shift($actionqueue);

						#Add the ID for the current player
						if (!array_key_exists($cur["playerID"], $playerstatus)) {
							$playerstatus[$cur["playerID"]] = 1;
						}
						#Check that current action is legal according to tank placement.
						$legalMove = true;

						foreach ($allTanks as $tankKey => $tankPos) {
							if ($cur['x'] == $tankPos[0]) {
								if ($cur['y'] == $tankPos[1]) {
									$legalMove = false;
								}
							}
						}

						if ($legalMove) {
							#Action Type 0 is a shovel action.
							#When shoveling, the current tile is revealed no matter what.
							#If the tile is a mine, this kills the current player.
							#If the tile had a value of 0, new actions are added to the queue for revealing all adjacent tiles to the shoveled tile, barring any that are already not flagged or visible. 
							if ($cur["actionType"] == 0) {
								#Check if we have previously shoveled this tile or not.
								$notShoveled = true;
								foreach ($shoveledTiles as $key => $shoveled) {
									if ($cur['x'] == $shoveled[0]) {
										if ($cur['y'] == $shoveled[1]) {
											$notShoveled = false;
										}
									}
								}
								if ($notShoveled) {
									$visibility[$cur["x"]][$cur["y"]] = 2;
									array_push($shoveledTiles, array(
										$cur["x"],
										$cur["y"]
									));
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
												$noTanks = true;
												foreach ($allTanks as $tankKey => $tankPos) {
													if ($targetX == $tankPos[0]) {
														if ($targetY == $tankPos[1]) {
															$noTanks = false;
														}
													}
												}
												if ($noTanks) {
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
									}
								}
							#Action Type 1 is a flag action.
							#If the tile is unrevealed, a flag is placed there instead.
							} else if ($cur["actionType"] == 1) {
								if ($visibility[$cur["x"]][$cur["y"]] == 0) {
									$visibility[$cur["x"]][$cur["y"]] = 1;
								}
							}
						} else {
							error_log("resolveActions.php - Illegal move made by player ID " . $cur["playerID"] . " at coordinates " . $cur["x"] . "," . $cur["y"]);
						}
					}

					#All tanks are updated, and any stuff on the map is updated to reflect these changes.
					$updatedTanks = updateTanks($minefield, $visibility, $allTanks);
					$allTanks = $updatedTanks['updatedTanks'];
					$visibility = $updatedTanks['updatedVisibility'];

					#Update the tank count. If it is at 0, add a tank and reset the count to 3.
					$tankCount = $tankCount - 1;
					if ($tankCount <= 0) {
						$addedTank = addTank($minefield, $visibility);
						if ($addedTank['newTankPosition'] !== null) {
							array_push($allTanks, $addedTank['newTankPosition']);
						}
						if ($addedTank['newVisibility'] !== null) {
							$visibility = $addedTank['newVisibility'];
						}
						$tankCount = 3;	
					}

					#Any players who are in the game but did not have an action in the queue are set to AFK.
					#Any who were previously AFK have their internal 'AFK clock' incremented.
					#Any players left who are AFK and have their AFK clock at 5 or more are killed.
					if ($afkStmt = $conn->prepare("SELECT playerID, status FROM multisweeper.playerstatus WHERE status!=0 AND awaitingAction=1 AND gameID=?")) {
						$afkStmt->bind_param("i", $gameID);
						$afkStmt->execute();
						$afkStmt->bind_result($afkID, $afkStatus);
						$newAfkPlayers = array();
						$prevAfkPlayers = array();
						while ($afkStmt->fetch()) {
							if ($afkStatus == 1) {
								array_push($newAfkPlayers, $afkID);
							} else if ($afkStatus == 2) {
								array_push($prevAfkPlayers, $afkID);
							}
						}
						$afkStmt->close();

						if ($afkUpdateStmt = $conn->prepare("UPDATE multisweeper.playerstatus SET afkCount=afkCount+1 WHERE status=2 AND gameID=? AND playerID=?")) {
							foreach ($prevAfkPlayers as $key => $value) {
								$afkUpdateStmt->bind_param("ii", $gameID, $value);
								$afkUpdateStmt->execute();
							}
							$afkUpdateStmt->close();
						} else {
							error_log("resolveActions.php - Unable to prepare AFK add statement.");
						}

						if ($afkAddStmt = $conn->prepare("UPDATE multisweeper.playerstatus SET status=2 WHERE gameID=? AND playerID=?")) {
							foreach ($newAfkPlayers as $key => $value) {
								$afkAddStmt->bind_param("ii", $gameID, $value);
								$afkAddStmt->execute();
							}
							$afkAddStmt->close();
						} else {
							error_log("resolveActions.php - Unable to prepare AFK add statement.");
						}

						if ($afkKillStmt = $conn->prepare("UPDATE multisweeper.playerstatus SET status=0 WHERE status=2 AND afkCount>=5")) {
							$afkKillStmt->execute();
						} else {
							error_log("resolveActions.php - Unable to prepare AFK kill statement.");
						}
					}

					#All players who did submit actions are updated appropriately.
					foreach ($playerstatus as $id => $isAlive) {
						if ($updatePlayer = $conn->prepare("UPDATE multisweeper.playerstatus SET status=?, awaitingAction=1 WHERE gameID=? AND playerID=?")) {
							$updatePlayer->bind_param("iii", $isAlive, $gameID, $id);
							$updated = $updatePlayer->execute();
							if ($updated === false) {
								error_log("resolveActions.php - Error occurred during player status update. " . $updatePlayer->errno . ": " . $updatePlayer->error);
								$updatePlayer->close();
							} else {
								$updatePlayer->close();

								#Empty the action queue of actions relating to this specific game.
								if ($deleteStmt = $conn->prepare("DELETE FROM multisweeper.actionqueue WHERE gameID=?")) {
									$deleteStmt->bind_param("i", $gameID);
									$updated = $deleteStmt->execute();
									if ($updated === false) {
										error_log("resolveActions.php - Error occurred during action queue clean up. " . $deleteStmt->errno . ": " . $deleteStmt->error);
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
										}

										#If game is done, all unrevealed tiles become visible instead.
										if ($gameCompleted) {
											for ($x = 0; ($x < count($visibility)); $x++) {
												for ($y = 0; ($y < count($visibility[$x])); $y++) {
													if ($minefield[$x][$y] === "M") {
														$visibility[$x][$y] = 2;
													}
												}
											}
										}

										#Update map and visibility values for the game by saving to database.
										if ($updateStmt = $conn->prepare("UPDATE multisweeper.games SET map=?, visibility=?, tankCountdown=?, tanks=?, status=?, width=?, height=? WHERE gameID=?")) {
											$statusStr = "OPEN";
											if ($gameCompleted) {
												$statusStr = "GAME OVER";
											}
											$updateStmt->bind_param("ssissiii", translateMinefieldToMySQL($minefield), translateMinefieldToMySQL($visibility), $tankCount, translateTanksToMySQL($allTanks), $statusStr, count($minefield), count($minefield[0]), $gameID);
											$updated = $updateStmt->execute();
											if ($updated === false) {
												error_log("resolveActions.php - Error occurred during map update. " . $updateStmt->errno . ": " . $updateStmt->error);
											} 
											$updateStmt->close();

											if ($gameCompleted) {
												createGameCreationTask();
											}
										} else {
											error_log("resolveActions.php - Unable to prepare map update after resolving action queue. " . $conn->errno . ": " . $conn->error);
										}
									}
								} else {
									error_log("resolveActions.php - Unable to prepare delete statement. " . $conn->errno . ": " . $conn->error);
								}
							}
						} else {
							error_log("resolveActions.php - Unable to prepare player status update after resolving action queue. " . $conn->errno . ": " . $conn->error);
						}
					}
				} else {
					error_log("resolveActions.php - Found 0 actions in queue! Most likely something went terribly wrong!");
				}
			} else {
				error_log("resolveActions.php - Unable to prepare action statement for resolving action queue. " . $conn->errno . ": " . $conn->error);
			}
		} else {
			error_log("resolveActions.php - Unable to resolve map statement for resolving action queue. " . $stmt->errno . ": " . $stmt->error);
		}
	} else {
		error_log("resolveActions.php - Unable to prepare map statement for resolving action queue. " . $conn->errno . ": " . $conn->error);
	}
}

?>
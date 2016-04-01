<?php

#This file contains the function 'resolveAllActions' to help resolve actions in the action queue appropriately.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/minefieldExpander.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/minefieldPopulater.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/playerController.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/taskScheduler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/translateData.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/trapController.php');
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
	if ($stmt = $conn->prepare("SELECT map, visibility, height, width, friendlyTankCountdown, friendlyTanks, enemyTankCountdown, enemyTankCountdownReset, enemyTanks, wrecks, traps FROM multisweeper.games WHERE gameID=?")) {
		$stmt->bind_param("i", $gameID);
		$stmt->execute();
		$stmt->bind_result($m, $v, $h, $w, $tankCount, $t, $enemyTankCount, $enemyTankReset, $e, $wr, $tr);
		if ($stmt->fetch()) {
			$stmt->close();

			#Determine if this is the first ever move in the game. If so, different actions call for different outcomes.
			$firstMove = false;
			if ((strpos($v, "1") === false) && (strpos($v, "2") === false)) {
				error_log("This is the first move of the current game.");
				$firstMove = true;
			}

			$minefield = translateMinefieldToPHP($m, $h, $w);
			$visibility = translateMinefieldToPHP($v, $h, $w);
			$friendlyTanks = translateTanksToPHP($t);
			$enemyTanks = translateTanksToPHP($e);
			$wrecks = translateTanksToPHP($wr);
			$traps = translateTrapsToPHP($tr);

			#Determine if we need to expand the map first due to tank movement.
			$expand = false;
			$baseExploded = false;
			
			foreach ($friendlyTanks as $tankKey => $tankPos) {
				if ($tankPos[0] == ($w - 1)) {
					$expand = true;
				}
			}

			foreach ($enemyTanks as $tankKey => $tankPos) {
				if ($tankPos[0] == 0) {
					$baseExploded = true;
				}
			}

			if ($expand) {
				error_log("Expanding field...");
				$newVals = expandMinefield($minefield, $visibility, 10, 0);
				$minefield = $newVals["minefield"];
				$visibility = $newVals["visibility"];
				if ($enemyTankReset > 3) {
					$enemyTankReset = $enemyTankReset - 1;
				}
			}

			#Get all player information
			$allPlayers = getPlayersForGame($gameID);

			if (count($allPlayers) > 0) {
				#Retrieve all actions in the action queue for this game and throw them into unique objects in an array for easy access during resolution.
				if ($actionStmt = $conn->prepare("SELECT playerID, actionType, xCoord, yCoord FROM multisweeper.actionqueue WHERE gameID=?")) {
					$actionqueue = array();

					$actionStmt->bind_param("i", $gameID);
					$actionStmt->execute();
					$actionStmt->bind_result($playerID, $actionType, $xCoord, $yCoord);

					while ($actionStmt->fetch()) {
						$temp = array(
							"playerID"		=>	intval($playerID),
							"actionType"	=>	intval($actionType),
							"x"				=>	intval($xCoord),
							"y"				=>	intval($yCoord)
						);
						array_push($actionqueue, $temp);
					}

					$actionStmt->close();

					$shoveledTiles = array();
					$queuedTraps = array();

					while (count($actionqueue) > 0) {
						$cur = array_shift($actionqueue);

						#Check that current action is legal according to tank placement.
						$legalMove = true;

						foreach ($friendlyTanks as $tankKey => $tankPos) {
							if ($cur['x'] == $tankPos[0]) {
								if ($cur['y'] == $tankPos[1]) {
									$legalMove = false;
								}
							}
						}

						foreach ($enemyTanks as $tankKey => $tankPos) {
							if ($cur['x'] == $tankPos[0]) {
								if ($cur['y'] == $tankPos[1]) {
									$legalMove = false;
								}
							}
						}

						foreach ($wrecks as $wkey => $wval) {
							if ($cur['x'] == $wval[0]) {
								if ($cur['y'] == $wval[1]) {
									$legalMove = false;
								}
							}
						}

						foreach ($traps as $wkey => $tval) {
							if ($cur['x'] == $tval[1]) {
								if ($cur['y'] == $tval[2]) {
									$legalMove = false;
								}
							}
						}

						if ($legalMove) {
							#Action Type 0 is a shovel action.
							#When shoveling, the current tile is revealed no matter what.
							#If the tile is a mine, this kills the current player.
							#If the tile had a value of 0, new actions are added to the queue for revealing all adjacent tiles to the shoveled tile, barring any that are already not flagged or visible. 
							if ($cur["actionType"] === 0) {
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
										if ($firstMove) {
											#Temporarily just remove the mine.
											$minefield[$cur["x"]][$cur["y"]] = 0;
											$minefield = updateMinefieldNumbers($minefield);
											$newAction = array(
												"playerID"		=> $cur["playerID"],
												"actionType"	=> $cur["actionType"],
												"x"				=> $cur["x"],
												"y"				=> $cur["y"]
											);
											array_push($actionqueue, $newAction);
										} else {
											$allPlayers = alterPlayerValue($allPlayers, $cur["playerID"], 'status', 0);
										}
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
												foreach ($friendlyTanks as $tankKey => $tankPos) {
													if ($targetX == $tankPos[0]) {
														if ($targetY == $tankPos[1]) {
															$noTanks = false;
														}
													}
												}
												foreach ($enemyTanks as $tankKey => $tankPos) {
													if ($targetX == $tankPos[0]) {
														if ($targetY == $tankPos[1]) {
															$noTanks = false;
														}
													}
												}
												foreach ($wrecks as $wkey => $wval) {
													if ($targetX == $wval[0]) {
														if ($targetY == $wval[1]) {
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
							} else if ($cur["actionType"] === 1) {
								if ($visibility[$cur["x"]][$cur["y"]] == 0) {
									$visibility[$cur["x"]][$cur["y"]] = 1;
								}
							#Action Type 2 is a place trap action.
							#Place a trap on the space.
							} else if ($cur["actionType"] === 2) {
								$cooldown = getPlayerValue($allPlayers, $cur["playerID"], "trapCooldown");
								if ($cooldown <= 0) {
									$trapVal = getPlayerValue($allPlayers, $cur["playerID"], "trapType");
									$tempTrap = array($trapVal, $cur["x"], $cur["y"]);
									array_push($queuedTraps, $tempTrap);
									$allPlayers = alterPlayerValue($allPlayers, $cur["playerID"], "trapCooldown", getCooldownForTrapType($trapVal));
								}
							}
						} else {
							error_log("resolveActions.php - Illegal move made by player ID " . $cur["playerID"] . " at coordinates " . $cur["x"] . "," . $cur["y"]);
						}

						if (getPlayerValue($allPlayers, $cur['playerID'], 'hasActed') === 0) {
							if ($cur['actionType'] === 0) {
								$oldCooldown = getPlayerValue($allPlayers, $cur['playerID'], 'trapCooldown');
								if ($oldCooldown > 0) {
									$allPlayers = alterPlayerValue($allPlayers, $cur['playerID'], 'trapCooldown', $oldCooldown - 1);
								}
							}
						}
						$allPlayers = alterPlayerValue($allPlayers, $cur['playerID'], 'hasActed', 1);
					}

					#Resolve traps
					$trapResults = resolveTraps($minefield, $visibility, $friendlyTanks, $enemyTanks, $traps, $wrecks);
					$traps = $trapResults['traps'];

					#Add queued traps
					foreach ($queuedTraps as $trapKey => $trapSpecifics) {
						$traps = addTrap($traps, $trapSpecifics[0], $trapSpecifics[1], $trapSpecifics[2]);
					}

					#All tanks are updated, and any stuff on the map is updated to reflect these changes.
					$updatedTanks = updateTanks($trapResults['map'], $trapResults['visibility'], $trapResults['friendlyTanks'], $trapResults['enemyTanks'], $trapResults['wrecks']);
					$friendlyTanks = $updatedTanks['updatedFriendlyTanks'];
					$enemyTanks = $updatedTanks['updatedEnemyTanks'];
					$wrecks = $updatedTanks['updatedWrecks'];
					$visibility = $updatedTanks['updatedVisibility'];

					#Update the tank count. If it is at 0, add a tank and reset the count to 3.
					$tankCount = $tankCount - 1;
					if ($tankCount <= 0) {
						$addedTank = addFriendlyTank($minefield, $visibility);
						if ($addedTank['newTankPosition'] !== null) {
							array_push($friendlyTanks, $addedTank['newTankPosition']);
						}
						if ($addedTank['newVisibility'] !== null) {
							$visibility = $addedTank['newVisibility'];
						}
						$tankCount = 3;	
					}

					#Update the enemy tank count. If it is at 0, add an enemy and reset the count to the current threshold.
					$enemyTankCount = $enemyTankCount - 1;
					if ($enemyTankCount <= 0) {
						$addedTank = addEnemyTank($minefield, $visibility);
						if ($addedTank['newTankPosition'] !== null) {
							array_push($enemyTanks, $addedTank['newTankPosition']);
						}
						if ($addedTank['newVisibility'] !== null) {
							$visibility = $addedTank['newVisibility'];
						}
						$enemyTankCount = $enemyTankReset;
					}					

					#All players who did submit actions are updated appropriately.
					if (savePlayersForGame($allPlayers, $gameID)) {
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
								if ($baseExploded) {
									$gameCompleted = true;
									error_log("Game Over - Base blown up!");
								} else {
									if (countPlayersWithValue($allPlayers, 'status', 1) > 0) {
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
										if ($gameCompleted) {
											error_log("Game Over - No unrevealed non-mine tiles left!");
										}
									} else {
										$gameCompleted = true;
										error_log("Game Over - No living players left!");
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
								if ($updateStmt = $conn->prepare("UPDATE multisweeper.games SET map=?, visibility=?, friendlyTankCountdown=?, friendlyTanks=?, enemyTankCountdown=?, enemyTanks=?, enemyTankCountdownReset=?, wrecks=?, traps=?, status=?, width=?, height=? WHERE gameID=?")) {
									$statusStr = "OPEN";
									if ($gameCompleted) {
										$statusStr = "GAME OVER";
									}
									$updateStmt->bind_param("ssisisisssiii", translateMinefieldToMySQL($minefield), translateMinefieldToMySQL($visibility), $tankCount, translateTanksToMySQL($friendlyTanks), $enemyTankCount, translateTanksToMySQL($enemyTanks), $enemyTankReset, translateTanksToMySQL($wrecks), translateTrapsToMySQL($traps), $statusStr, count($minefield), count($minefield[0]), $gameID);
									$updated = $updateStmt->execute();
									if ($updated === false) {
										error_log("resolveActions.php - Error occurred during map update. " . $updateStmt->errno . ": " . $updateStmt->error);
									} 
									$updateStmt->close();

									if ($gameCompleted) {
										createGameCreationTask();
									} else {
										createResolveActionsTask($gameID);
									}
								} else {
									error_log("resolveActions.php - Unable to prepare map update after resolving action queue. " . $conn->errno . ": " . $conn->error);
								}
							}
						} else {
							error_log("resolveActions.php - Unable to prepare delete statement. " . $conn->errno . ": " . $conn->error);
						}
					} else {
						error_log("resolveActions.php - Unable to save player statuses.");
					}
				} else {
					error_log("resolveActions.php - Unable to prepare action statement for resolving action queue. " . $conn->errno . ": " . $conn->error);
				}
			} else {
				error_log("resolveActions.php - Did not find any players for current game.");
			}
		} else {
			error_log("resolveActions.php - Unable to resolve map statement for resolving action queue. " . $stmt->errno . ": " . $stmt->error);
		}
	} else {
		error_log("resolveActions.php - Unable to prepare map statement for resolving action queue. " . $conn->errno . ": " . $conn->error);
	}
}

?>
<?php

require_once('../../../database.php');
require_once('mineGameConstants.php');
require_once('translateData.php');

function resolveAllActions($gameID) {
	global $sqlhost, $sqlusername, $sqlpassword, $adjacencies;

	error_log("Attempting to resolve action queue.");

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
			$stmt->close();
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
					error_log("Found action at " . $xCoord . "," . $yCoord);
					array_push($actionqueue, $temp);
				}

				$actionStmt->close();

				if (count($actionqueue) > 0) {
					
					//For each action,
					while (count($actionqueue) > 0) {
						$cur = array_shift($actionqueue);

						//Add player to player status with alive status if they are not currently in there.
						if (!array_key_exists($cur["playerID"], $playerstatus)) {
							$playerstatus[$cur["playerID"]] = 1;
						}

						//If shovel action
						if ($cur["actionType"] == 0) {
							error_log("Resolving dig at " . $cur["x"] . "," . $cur["y"]);

							//Reveal tile at coordinates
							$visibility[$cur["x"]][$cur["y"]] = 2;

							error_log("Visibility at the coordinate now " . $visibility[$cur["x"]][$cur["y"]]);
							error_log("Map value there is " . $minefield[$cur["x"]][$cur["y"]]);

							//If tile value is a mine,
							if ($minefield[$cur["x"]][$cur["y"]] === "M") {
								error_log("Mine encountered!");
								//Kill player
								$playerstatus[$cur["playerID"]] = 0;
							//If tile value is 0,
							} else if ($minefield[$cur["x"]][$cur["y"]] == 0) {
								error_log("Zero encountered!");
								//Add actions to queue that reveal all adjacent tiles
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
							
						//If flag action, don't actually implement because yeah
						} else {
							//TBD
						}
					}

					//Update all remaining players in game to be awaiting actions.
					foreach ($playerstatus as $id => $isAlive) {
						if ($updatePlayer = $conn->prepare("UPDATE multisweeper.playerstatus SET status=?, awaitingAction=1 WHERE gameID=? AND playerID=?")) {
							$updatePlayer->bind_param("iii", $isAlive, $gameID, $id);
							$updated = $updatePlayer->execute();
							if ($updated === false) {
								error_log("Error occurred during player status update. " . $updatePlayer->errno . ": " . $updatePlayer->error);
								$updatePlayer->close();
							} else {
								$updatePlayer->close();

								//Delete all player actions from the action queue.
								if ($deleteStmt = $conn->prepare("DELETE FROM multisweeper.actionqueue WHERE gameID=?")) {
									$deleteStmt->bind_param("i", $gameID);
									$updated = $deleteStmt->execute();
									if ($updated === false) {
										error_log("Error occurred during action queue clean up. " . $deleteStmt->errno . ": " . $deleteStmt->error);
										$deleteStmt->close();
									} else {
										$deleteStmt->close();

										//Check if game is done or not.
										$gameCompleted = true;
										//If all players are dead, it is for sure done.
										if ($checkLivingPlayers = $conn->prepare("SELECT playerID FROM multisweeper.playerstatus WHERE gameID=? AND status=1")) {
											$checkLivingPlayers->bind_param("i", $gameID);
											$checkLivingPlayers->execute();
											if ($checkLivingPlayers->num_rows != 0) {
												$gameCompleted = false;

												//Otherwise, if all unrevealed spaces are mines, it is done.
												for ($x = 0; ($x < count($visibility)) && !$gameCompleted; $x++) {
													for ($y = 0; ($y < count($visibility[$x])) && !$gameCompleted; $y++) {
														if ($visibility[$x][$y] == 0) {
															if ($minefield[$x][$y] !== "M") {
																$gameCompleted = false;
															}
														}
													}
												}
											}
											$checkLivingPlayers->close();
										} else {
											error_log("Unable to prepare living player status statement after resolving action queue. " . $conn->errno . ": " . $conn->error);
										}

										//If game is done, all unrevealed tiles become flagged instead.
										if ($gameCompleted) {
											for ($x = 0; ($x < count($visibility)) && !$gameCompleted; $x++) {
												for ($y = 0; ($y < count($visibility[$x])) && !$gameCompleted; $y++) {
													if ($visibility[$x][$y] == 0) {
														$visibility[$x][$y] = 1;
													}
												}
											}
										}

										//Update map and visibility values for the game by saving to database.
										if ($updateStmt = $conn->prepare("UPDATE multisweeper.games SET map=?, visibility=?, status=? WHERE gameID=?")) {
											$statusStr = "OPEN";
											if ($gameCompleted) {
												$statusStr = "DONE"
											}
											$updateStmt->bind_param("sssi", translateMinefieldToMySQL($minefield), translateMinefieldToMySQL($visibility), $statusStr, $gameID);
											$updated = $updateStmt->execute();
											if ($updated === false) {
												error_log("Error occurred during map update. " . $updateStmt->errno . ": " . $updateStmt->error);
											} 
											$updateStmt->close();
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
			error_log("Unable to resolve map statement for resolving action queue. " . $conn->errno . ": " . $conn->error);
		}
	} else {
		error_log("Unable to prepare map statement for resolving action queue. " . $conn->errno . ": " . $conn->error);
	}
}

?>
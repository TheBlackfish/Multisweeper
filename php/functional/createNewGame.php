<?php

#This file creates a new game, uploads it to the MySQL database, and then adds all players currently signed up for it to the playerStatus table.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/translateData.php');

#createNewGame($width, $height, $numMines)
#Takes the various parameters of the minefield width, height, and number of mines and creates a new game while adding it to the database and adding any players in the current sign-up queue to the status table.
#@param $width (Integer) The width of the minefield.
#@param $height (Integer) The height of the minefield.
#@param $numMines (Integer) The number of mines to place on the minefield.
function createNewGame($width, $height, $numMines) {
	global $sqlhost, $sqlusername, $sqlpassword;

	#Initialize the connection to the MySQL database.
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	#Deletes all variables for upcoming game times.
	if ($deleteTimeStmt = $conn->prepare("DELETE FROM multisweeper.globalvars WHERE key='nextGameTime'")) {
		$deleteTimeStmt->execute();
		$deleteTimeStmt->close();
	} else {
		error_log("Unable to prepare next game time deletion statement, only cosmetic in effects. " . $conn->errno . ": " . $conn->error);
	}

	#Creates a double array with all zeroes matching the width and height of the minefield.
	$minefield = array_fill(0, $width, array_fill(0, $height, 0));

	#Places mines randomly in the minefield array.
	while ($numMines > 0) {
		$xKey = array_rand($minefield);
		$yKey = array_rand($minefield[$xKey]);
		if ($minefield[$xKey][$yKey] == 0) {
			$minefield[$xKey][$yKey] = "M";
			$numMines--;
		}
	}

	#Updates the minefield array to have each space without a mine have the number of adjacent mines to it instead of 0.
	$minefield = _updateMinefieldNumbers($minefield);

	#Translates the minefield to a form that can be stored in the database.
	$result = translateMinefieldToMySQL($minefield);

	#Creates a generic visibility array of all 'unrevealed' tiles.
	$visibility = str_pad("", strlen($result), "0");

	#Attempt to upload the newly created game into the MySQL database.
	if ($insertStmt = $conn->prepare("INSERT INTO multisweeper.games (map, visibility, height, width, status) VALUES (?,?,?,?,'OPEN')")) {
		$insertStmt->bind_param("ssii", $result, $visibility, $height, $width);
		$inserted = $insertStmt->execute();
	
		if ($inserted) {
			$insertStmt->close();

			#Retrieve the unique ID of the game just uploaded to the MySQL database.
			if ($idStmt = $conn->prepare("SELECT gameID FROM multisweeper.games WHERE map=? AND status='OPEN' LIMIT 1")) {
				$idStmt->bind_param("s", $result);
				$idStmt->execute();
				$idStmt->bind_result($gameID);
				$idStmt->fetch();
				$idStmt->close();

				if ($gameID !== null) {

					#Retrieve all players currently in the sign-up queue and create statuses for them.
					if ($playerStmt = $conn->prepare("SELECT playerID FROM multisweeper.upcomingsignup")) {
						$playerIDs = array();
						$playerStmt->execute();
						$playerStmt->bind_result($curID);
						while ($playerStmt->fetch()) {
							array_push($playerIDs, $curID);
						}
						$playerStmt->close();

						if (count($playerIDs) === 0) {
							error_log("No players for new game.");
						} else {

							#Upload all created statuses to the database.
							if ($statusStmt = $conn->prepare("INSERT INTO multisweeper.playerstatus (gameID, playerID, awaitingAction) VALUES (?, ?, 1)")) {
								for ($i=0; $i < count($playerIDs); $i++) { 
									$statusStmt->bind_param("ii", $gameID, $playerIDs[$i]);
									$statusStmt->execute();
								}
								$statusStmt->close();

								#Delete everyone from the sign-up queue.
								if ($deleteStmt = $conn->prepare("TRUNCATE multisweeper.upcomingsignup")) {
									$deleteStmt->execute();
									$deleteStmt->close();

									#Successfully created the new game.
									error_log("New game successfully created, ID=" . $gameID);
								} else {
									error_log("Unable to prepare delete statement. " . $conn->errno . ": " . $conn->error);
								}
							} else {
								error_log("Unable to prepare sign-up finalize statement. " . $conn->errno . ": " . $conn->error);
							}
						}
					} else {
						error_log("Unable to prepare sign-up statement. " . $conn->errno . ": " . $conn->error);
					}
				} else {
					error_log("Unexpected results from ID statement. " . $idStmt->errno . ": " . $idStmt->error);
				}
			} else {
				error_log("Unable to prepare ID statement. " . $conn->errno . ": " . $conn->error);
			}
		} else {
			error_log("Unable to insert game during creation. " . $insertStmt->errno . ": " . $insertStmt->error);
		}
	} else {
		error_log("Unable to prepare game insertation statement. " . $conn->errno . ": " . $conn->error);
	}
}

#_updateMinefieldNumbers($minefield)
#Takes a double array with 0's and M's and marks each value with the number of adjacent M's if it is not an M.
#@param $minefield (Double Array) A double array acting as a minefield to be adjusted.
#@return The minefield updated to correctly reflect adjacencies.
function _updateMinefieldNumbers($minefield) {
	global $adjacencies;

	$width = count($minefield);
	$height = count($minefield[0]);

	for ($x = 0; $x < $width; $x++) {
		for ($y = 0; $y < $height; $y++) {
			if ($minefield[$x][$y] !== "M") {
				$numToInsert = 0;

				foreach ($adjacencies as $adj) {
					$shouldCalc = true;

					if (($x + $adj[0] < 0) or ($x + $adj[0] >= $width)) {
						$shouldCalc = false;
					}
					if (($y + $adj[1] < 0) or ($y + $adj[1] >= $height)) {
						$shouldCalc = false;
					}

					if ($shouldCalc) {
						$val = $minefield[$x + $adj[0]][$y + $adj[1]];
						if ($val === "M") {
							$numToInsert++;
						}
					}
				}
				$minefield[$x][$y] = $numToInsert;
			}
		}
	}
	
	return $minefield;
}

?>
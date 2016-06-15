<?php

#This file creates a new game, uploads it to the MySQL database, and then adds all players currently signed up for it to the playerStatus table.

require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/constants/mineGameConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/functional/minefieldPopulater.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/functional/translateData.php');

#createNewDefaultGame()
#Returns a newly created game with the generic starting stats.
#@return Whether or not a new game was created.
function createNewDefaultGame() {
	global $minefieldWidth,	$minefieldHeight, $startingMines;
	return createNewGame($minefieldWidth, $minefieldHeight, $startingMines);
}

#createNewGame($width, $height, $numMines)
#Takes the various parameters of the minefield width, height, and number of mines and creates a new game while adding it to the database and adding any players in the current sign-up queue to the status table. Then returns a boolean representing if a game was created..
#@param $width (Integer) The width of the minefield.
#@param $height (Integer) The height of the minefield.
#@param $numMines (Integer) The number of mines to place on the minefield.
#@return Whether or not a new game was created.
function createNewGame($width, $height, $numMines) {
	global $sqlhost, $sqlusername, $sqlpassword;
	global $numTraps;

	$gameCreated = false;

	#Initialize the connection to the MySQL database.
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("createNewGame.php - Connection failed: " . $conn->connect_error);
	}

	#Deletes all variables for upcoming game times.
	if ($deleteTimeStmt = $conn->prepare("DELETE FROM sweepelite.globalvars WHERE k='nextGameTime'")) {
		$deleteTimeStmt->execute();
		$deleteTimeStmt->close();
	} else {
		error_log("createNewGame.php - Unable to prepare next game time deletion statement, only cosmetic in effects. " . $conn->errno . ": " . $conn->error);
	}

	#Retrieve all players currently in the sign-up queue and create statuses for them.
	if ($playerStmt = $conn->prepare("SELECT playerID FROM sweepelite.upcomingsignup")) {
		$playerIDs = array();
		$playerStmt->execute();
		$playerStmt->bind_result($curID);
		while ($playerStmt->fetch()) {
			array_push($playerIDs, $curID);
		}
		$playerStmt->close();

		if (count($playerIDs) === 0) {
			error_log("No players for new game, going to wait until one connects.");
		}

		#Creates a double array with all zeroes matching the width and height of the minefield, along with random mines inserted into it.
		$minefield = createMinefieldArea($width, $height, $numMines);

		#Updates the minefield array to have each space without a mine have the number of adjacent mines to it instead of 0.
		$minefield = updateMinefieldNumbers($minefield);

		#Translates the minefield to a form that can be stored in the database.
		$result = translateMinefieldToMySQL($minefield);

		#Creates a generic visibility array of all 'unrevealed' tiles.
		$visibility = str_pad("", strlen($result), "0");

		#Attempt to upload the newly created game into the MySQL database.
		if ($insertStmt = $conn->prepare("INSERT INTO sweepelite.games (map, visibility, height, width, status) VALUES (?,?,?,?,'OPEN')")) {
			$insertStmt->bind_param("ssii", $result, $visibility, $height, $width);
			$inserted = $insertStmt->execute();
		
			if ($inserted) {
				$insertStmt->close();

				#Retrieve the unique ID of the game just uploaded to the MySQL database.
				if ($idStmt = $conn->prepare("SELECT gameID FROM sweepelite.games WHERE map=? AND status='OPEN' LIMIT 1")) {
					$idStmt->bind_param("s", $result);
					$idStmt->execute();
					$idStmt->bind_result($gameID);
					$idStmt->fetch();
					$idStmt->close();

					if ($gameID !== null) {
						#Upload all created statuses to the database.
						if ($statusStmt = $conn->prepare("INSERT INTO sweepelite.playerstatus (gameID, playerID, trapType, awaitingAction) VALUES (?, ?, ?, 1)")) {
							for ($i=0; $i < count($playerIDs); $i++) { 
								$trapID = ($gameID + $playerIDs[$i]) % $numTraps;
								$statusStmt->bind_param("iii", $gameID, $playerIDs[$i], $trapID);
								$statusStmt->execute();
							}
							$statusStmt->close();

							#Delete everyone from the sign-up queue.
							if ($deleteStmt = $conn->prepare("TRUNCATE sweepelite.upcomingsignup")) {
								#$deleteStmt->execute();
								$deleteStmt->close();

								$gameCreated = true;

								#Successfully created the new game.
								error_log("createNewGame.php - New game successfully created, ID=" . $gameID);

								return $gameID;
							} else {
								error_log("createNewGame.php - Unable to prepare delete statement. " . $conn->errno . ": " . $conn->error);
							}
						} else {
							error_log("createNewGame.php - Unable to prepare sign-up finalize statement. " . $conn->errno . ": " . $conn->error);
						}					
					} else {
						error_log("createNewGame.php - Unexpected results from ID statement. " . $idStmt->errno . ": " . $idStmt->error);
					}
				} else {
					error_log("createNewGame.php - Unable to prepare ID statement. " . $conn->errno . ": " . $conn->error);
				}
			} else {
				error_log("createNewGame.php - Unable to insert game during creation. " . $insertStmt->errno . ": " . $insertStmt->error);
			}
		} else {
			error_log("createNewGame.php - Unable to prepare game insertation statement. " . $conn->errno . ": " . $conn->error);
		}
	} else {
		error_log("createNewGame.php - Unable to prepare sign-up statement. " . $conn->errno . ": " . $conn->error);
	}	

	if (!$gameCreated) {
		error_log("createNewGame.php - Unable to create new game, returning -1.");
	}

	return false;
}

?>
<?php

#This file controls all player interactions by storing player information in associative arrays.
#The overall player array contains several player arrays. The indices for these player arrays is their respective player IDs.
#Each player array stores the following data:
	#status - The player's status. 0 = DEAD, 1 = ALIVE, 2 = AFK.
	#trapType - The index of the trap the player is currently using.
	#trapCooldown - The amount of digs the player needs to perform to get their trap back.
	#hasActed - Whether or not the player has acted this action resolution.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/medalController.php');

#getPlayersForGame($gameID)
#This function compiles all player information relating to the game provided into the standard player information array.
#@param $gameID - The ID of the game to retrieve information for.
#@return The associative array containing all player information.
function getPlayersForGame($gameID) {
	global $sqlhost, $sqlusername, $sqlpassword;

	#Initialize the connection to the MySQL database.
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("playerController.php - Connection failed: " . $conn->connect_error);
	}

	$ret = array();

	if ($playerStmt = $conn->prepare("SELECT s.status, p.playerID, s.afkCount, s.trapType, s.trapCooldown, s.digNumber, s.correctFlags FROM multisweeper.players as p INNER JOIN multisweeper.playerstatus as s ON p.playerID=s.playerID WHERE s.gameID=?")) {
		$playerStmt->bind_param("i", $gameID);
		$playerStmt->execute();
		$playerStmt->bind_result($status, $playerID, $afkCount, $trapType, $trapCooldown, $digNumber, $correctFlags);
		while ($playerStmt->fetch()) {
			$newPlayer = array(
				'status'		=>	$status,
				'afkCount'		=>	$afkCount,
				'trapType'		=>	$trapType,	
				'trapCooldown'	=>	$trapCooldown,
				'hasActed'		=>	0,
				'digNumber'		=>	$digNumber,
				'dugTiles'		=>	array(),
				'correctFlags'	=>	$correctFlags
			);
			$ret[$playerID] = $newPlayer;
		}
		$playerStmt->close();

		return $ret;
	} else {
		error_log("playerController.php - Unable to prepare player status selection. " . $conn->errno . ": " . $conn->error);
	}

	return array();
}

#setPlayerValue($allPlayers, $playerID, $key, $value)
#Changes the associative array for players by altering the k-v pair specified for the player specified.
#@param $allPlayers - The associative array with all players to alter.
#@param $playerID - The ID of the player to alter.
#@param $key - The value to change.
#@param $value - What to change the value to.
#@return The updated associative array.
function setPlayerValue($allPlayers, $playerID, $key, $value) {
	if (array_key_exists($playerID, $allPlayers)) {
		$cur = $allPlayers[$playerID];
		if (array_key_exists($key, $cur)) {
			$cur[$key] = $value;
			$allPlayers[$playerID] = $cur;
		} else {
			error_log("playerController.php - Attempting to set value with key that is not correct.");
		}
	} else {
		error_log("playerController.php - Attempting to set value on playerID that does not exist.");
	}
	return $allPlayers;
}

function setAllPlayersValue($allPlayers, $key, $value) {
	foreach ($allPlayers as $playerID => $player) {
		if (array_key_exists($key, $player)) {
			$player[$key] = $value;
			$allPlayers[$playerID] = $player;
		} else {
			error_log("playerController.php - Attempting to set value with key that is not correct.");
		}
	}

	return $allPlayers;
}

function alterPlayerValue($allPlayers, $playerID, $key, $additive) {
	if (array_key_exists($playerID, $allPlayers)) {
		$cur = $allPlayers[$playerID];
		if (array_key_exists($key, $cur)) {
			$cur[$key] += $additive;
			$allPlayers[$playerID] = $cur;
		} else {
			error_log("playerController.php - Attempting to alter value with key that is not correct.");
		}
	} else {
		error_log("playerController.php - Attempting to alter value on playerID that does not exist.");
	}
	return $allPlayers;
}

#getPlayerValue($allPlayers, $playerID, $key)
#Retrieves the value of the key for the player specified.
#@param $allPlayers - The associative array with all players.
#@param $playerID - The ID of the player to check.
#@param $key - The value to retrieve.
#@return The player's value in the original form.
function getPlayerValue($allPlayers, $playerID, $key) {
	if (array_key_exists($playerID, $allPlayers)) {
		$cur = $allPlayers[$playerID];
		if (array_key_exists($key, $cur)) {
			return $cur[$key];
		} else {
			error_log("playerController.php - Attempting to get value with key that is not correct.");
		}
	} else {
		error_log("playerController.php - Attempting to get value on playerID that does not exist.");
	}
	return null;
}

#countPlayersWithValue($allPlayers, $key, $value)
#Counts how many players have the specified value for the key provided.
#@param $allPlayers - The associative array to count through.
#@param $key - The key to check against.
#@param $value - The value to check for.
#@return The number of players with the specified value for the specified key.
function countPlayersWithValue($allPlayers, $key, $value) {
	$count = 0;
	foreach ($allPlayers as $playerID => $player) {
		if (array_key_exists($key, $player)) {
			if ($player[$key] === $value) {
				$count = $count + 1;
			}
		}
	}
	return $count;
}

#savePlayersForGame($data, $gameID)
#Saves the player information provided to the MySQL database.
#@param $data - The associative array to save to the database.
#@param $gameID - The ID of the game to save for.
function savePlayersForGame($data, $gameID) {
	global $sqlhost, $sqlusername, $sqlpassword;

	#Initialize the connection to the MySQL database.
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("playerController.php - Connection failed: " . $conn->connect_error);
	}

	if ($saveStmt = $conn->prepare("UPDATE multisweeper.playerStatus SET status=?, afkCount=?, trapCooldown=?, digNumber=?, correctFlags=? WHERE gameID=? AND playerID=?")) {
		foreach ($data as $playerID => $player) {
			$status = 0;

			if ($player['status'] !== 0) {
				$status = 1;
				if ($player['hasActed'] === 1) {
					$afkCount = 0;
				} else {
					$afkCount = $player['afkCount'] + 1;
					if ($afkCount > 3) {
						$status = 2;
					}
				}
			}

			$finalDigNumber = $player['digNumber'] + count($player['dugTiles']);
			$saveStmt->bind_param("iiiiiii", $status, $afkCount, $player['trapCooldown'], $finalDigNumber, $player['correctFlags'], $gameID, $playerID);
			if ($saveStmt->execute()) {
				$data = setPlayerValue($data, $playerID, "status", $status);
				$data = setPlayerValue($data, $playerID, "afkCount", $afkCount);
				$data = setPlayerValue($data, $playerID, "digNumber", $finalDigNumber);
			}
		}
		$saveStmt->close();
	} else {
		error_log("playerController.php - Unable to prepare save statement.");
	}

	return $data;
}

function forcePlayerAFK($gameID, $playerID) {
	global $sqlhost, $sqlusername, $sqlpassword;

	#Initialize the connection to the MySQL database.
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("playerController.php - Connection failed: " . $conn->connect_error);
	}

	if ($saveStmt = $conn->prepare("UPDATE multisweeper.playerStatus SET status=2, afkCount=3 WHERE gameID=? AND playerID=?")) {
		$saveStmt->bind_param("ii", $gameID, $playerID);
		$saveStmt->execute();
		$saveStmt->close();
	} else {
		error_log("playerController.php - Unable to prepare AFK statement.");
		return false;
	}

	return true;
}

function savePlayerScores($data) {
	global $sqlhost, $sqlusername, $sqlpassword;

	#Initialize the connection to the MySQL database.
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("playerController.php - Connection failed: " . $conn->connect_error);
	}

	if ($scoreStmt=$conn->prepare("UPDATE multisweeper.players SET score=score+? WHERE playerID=?")) {
		foreach ($data as $playerID => $player) {
			$playerScore = 0;
			$playerMedals = calculateMedalAttributesForPlayer($player['digNumber']);
			$playerScore += 4 * $playerMedals['digMedal'];
			$playerScore += $player['correctFlags'];
			$scoreStmt->bind_param("ii", $playerScore, $playerID);
			$scoreStmt->execute();
		}
		$scoreStmt->close();
	}
}

?>
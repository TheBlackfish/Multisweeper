<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

function getPlayersForGame($gameID) {
	global $sqlhost, $sqlusername, $sqlpassword;

	#Initialize the connection to the MySQL database.
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("playerController.php - Connection failed: " . $conn->connect_error);
	}

	$ret = array();

	if ($playerStmt = $conn->prepare("SELECT s.status, p.playerID, s.trapType, s.trapCooldown FROM multisweeper.players as p INNER JOIN multisweeper.playerstatus as s ON p.playerID=s.playerID WHERE s.gameID=?")) {
		$playerStmt->bind_param("i", $gameID);
		$playerStmt->execute();
		$playerStmt->bind_result($status, $playerID, $trapType, $trapCooldown);
		while ($playerStmt->fetch()) {
			$newPlayer = array(
				'status'		=>	$status,
				'trapType'		=>	$trapType,	
				'trapCooldown'	=>	$trapCooldown,
				'hasActed'		=>	0
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

function alterPlayerValue($allPlayers, $playerID, $key, $value) {
	if (array_key_exists($playerID, $allPlayers)) {
		$cur = $allPlayers[$playerID];
		if (array_key_exists($key, $cur)) {
			$cur[$key] = $value;
			$allPlayers[$playerID] = $cur;
		} else {
			error_log("playerController.php - Attempting to alter value with key that is not correct.");
		}
	} else {
		error_log("playerController.php - Attempting to alter value on playerID that does not exist.");
	}
	return $allPlayers;
}

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

function savePlayersForGame($data, $gameID) {
	global $sqlhost, $sqlusername, $sqlpassword;

	#Initialize the connection to the MySQL database.
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("playerController.php - Connection failed: " . $conn->connect_error);
	}

	if ($saveStmt = $conn->prepare("UPDATE multisweeper.playerStatus SET status=?, trapCooldown=? WHERE gameID=? AND playerID=?")) {
		foreach ($data as $playerID => $player) {
			$status = 0;
			if ($player['status'] !== 0) {
				if ($player['hasActed'] === 1) {
					$status = 1;
				} else {
					$status = 2;
				}
			}

			$saveStmt->bind_param("iiii", $status, $player['trapCooldown'], $gameID, $playerID);
			$saveStmt->execute();
		}
		$saveStmt->close();
	} else {
		error_log("playerController.php - Unable to prepare save statement.");
		return false;
	}

	return true;
}

?>
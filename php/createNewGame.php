<?php

//Takes the various parameters of the minefield width, height, and number of mines.
//Does not return anything, but does alter the MySQL database
function createNewGame($width, $height, $numMines) {
	require_once('../../../database.php');
	require_once('mineGameConstants.php');
	require_once('translateData.php');

	//Initialize connections
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	//Initialize various arrays for the base 
	$minefield = array_fill(0, $width, array_fill(0, $height, 0));

	//Place mines randomly in the arrays
	while ($numMines > 0) {
		$xKey = array_rand($minefield);
		$yKey = array_rand($minefield[$xKey]);
		if ($minefield[$xKey][$yKey] == 0) {
			$minefield[$xKey][$yKey] = "M";
			$numMines--;
		}
	}

	//Calculate numbers for each index in the array
	$minefield = _updateMinefieldNumbers($minefield);

	//Translate to form MySQL can store it
	$result = translateMinefieldToMySQL($minefield);
	$visibility = str_pad("", strlen($result), "0");

	//Upload to MySQL
	if ($insertStmt = $conn->prepare("INSERT INTO multisweeper.games (map, visibility, height, width, status) VALUES (?, ?, ?, ?, ?)")) {
		$insertStmt->bind_param("ssiis", $result, $visibilty, $height, $width, "OPEN");
		$inserted = $insertStmt->execute();
		$insertStmt->close();

		if ($inserted) {

			//Get game ID
			if ($idStmt = $conn->prepare("SELECT gameID FROM multisweeper.games WHERE map=? AND status='OPEN' LIMIT 1")) {
				$idStmt->bind_param("s", $result);
				$idStmt->execute();
				$idStmt->bind_result($gameID);
				$idStmt->fetch();
				$idStmt->close();

				if ($gameID !== null) {

					//Create player statuses for all players currently signed up
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
							if ($statusStmt = $conn->prepare("INSERT INTO multisweeper.playerstatus (gameID, playerID, awaitingAction) VALUES (?, ?, ?)")) {
								for ($i=0; $i < count($playerIDs); $i++) { 
									$statusStmt->bind_param("iii", $gameID, $playerIDs[$i], 1);
									$statusStmt->execute();
								}
								$statusStmt->close();

								if ($deleteStmt = $conn->prepare("TRUNCATE multisweeper.upcomingsignup")) {
									$deleteStmt->execute();
									$deleteStmt->close();

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
					error_log("Unexpected results from ID statement. " . $conn->errno . ": " . $conn->error);
				}
			} else {
				error_log("Unable to prepare ID statement. " . $conn->errno . ": " . $conn->error);
			}
		} else {
			error_log("Unable to insert game during creation. " . $conn->errno . ": " . $conn->error);
		}
	} else {
		error_log("Unable to prepare game insertation statement. " . $conn->errno . ": " . $conn->error);
	}
}

//Goes through each space in the 2-dimensional array provided.
//In each space, the value becomes the number of adjacent "M" values if the space did not have a value of "M" already.
function _updateMinefieldNumbers($minefield) {
	require_once('mineGameConstants.php');

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
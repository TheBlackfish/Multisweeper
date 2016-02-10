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

	//Debugging!
	echo "Minefield pre-update<br>";
	for ($y = 0; $y < $height; $y++) {
		$debugRow = "";
		for ($x = 0; $x < $width; $x++) {
			$debugRow .= " " . $minefield[$x][$y];
		}
		echo $debugRow;
		echo "<br>";
	}

	//Calculate numbers for each index in the array
	$minefield = _updateMinefieldNumbers($minefield);

	//Debugging!
	echo "Minefield post-update, pre-translate<br>";
	for ($y = 0; $y < $height; $y++) {
		$debugRow = "";
		for ($x = 0; $x < $width; $x++) {
			$debugRow .= " " . $minefield[$x][$y];
		}
		echo $debugRow;
		echo "<br>";
	}

	//Translate to form MySQL can store it
	$result = translateMinefieldToMySQL($minefield);
	$visibility = str_pad("", strlen($result), "0");

	//Upload to MySQL
	$query = "INSERT INTO multisweeper.games (map, visibility, height, width, status) VALUES (";
	$query = $query . '"' . $result . '"' . ",";
	$query = $query . '"' . $visibility . '"' . ",";
	$query = $query . $height . ",";
	$query = $query . $width . ",";
	$query = $query . '"' . "OPEN" . '"' . ");";

	echo "Insert:<br>";
	echo $query . "<br>";

	if ($conn->query($query) === FALSE) {
		error_log("Error: " . $query . "<br>" . $conn->error);
		die("Unable to continue with game creation, exiting.");
	}

	//Get game ID
	$gameID = -1;
	$query = "SELECT gameID FROM multisweeper.games WHERE map = '" . $result . "' AND status = 'OPEN' LIMIT 1;";
	
	echo "Select:<br>";
	echo $query . "<br>";

	$idResults = $conn->query($query);
	if ($idResults->num_rows > 0) {
		while ($row = mysqli_fetch_row($idResults)) {
			$gameID = $row[0];
		}
	} else {
		error_log("Error: Unable to get ID for new game after uploading. Exiting.");
		die("Unable to continue with game creation, exiting.");
	}

	if ($gameID == -1) {
		error_log("Error: Game was not created by time we needed it. Exiting.");
		die("Unable to continue with game creation, exiting.");
	}

	//Create player statuses for all players currently signed up
	$query = "SELECT playerID FROM multisweeper.upcomingsignup;";
	$wResults = $conn->query($query);
	if ($wResults->num_rows > 0) {
		$playerQuery = "INSERT INTO multisweeper.playerstatus (gameID, playerID, awaitingAction) VALUES ";
		while ($row = mysqli_fetch_row($wResults)) {
			$playerQuery = $playerQuery . "('" . $gameID . "',";
			$playerQuery = $playerQuery . "'" . $row[0] . "',1),";
		}
		$playerQuery = rtrim($playerQuery, ',');
		$playerQuery .= ";";

		echo "Insert multiple statii:<br>";
		echo $playerQuery . "<br>";

		if ($conn->query($playerQuery) === FALSE) {
			error_log("Error: " . $playerQuery . "<br>" . $conn->error);
			die("Unable to continue with game creation, exiting.");
		}
	} else {
		error_log("Error: Did not find any players for game. Exiting.");
		die("Unable to continue with game creation, exiting.");
	}

	error_log("New game successfully created, ID=" . $gameID);
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
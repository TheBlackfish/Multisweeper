<?php

require_once('../../../../database.php');
require_once('mineGameConstants.php');
require_once('translateData.php');

//Takes the various parameters of the minefield width, height, and number of mines.
//Does not return anything, but does alter the MySQL database
function createNewGame($width, $height, $numMines) {
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

	$visibility = str_pad("", str_len($result), "0");

	//Upload to MySQL
	$query = "INSERT INTO multisweeper.games (map, visibility, height, width, status) VALUES (";
	$query = $query . '"' . $results . '"' . ",";
	$query = $query . '"' . $visibility . '"' . ",";
	$query = $query . $height . ",";
	$query = $query . $width . ",";
	$query = $query . '"' . "OPEN" . '"' . ");";

	if ($conn->query($query) === FALSE) {
		error_log("Error: " . $query . "<br>" . $conn->error);
		die("Unable to continue with game creation, exiting.");
	}

	//Create player statuses for all players currently signed up
	$waiting
}

//Goes through each space in the 2-dimensional array provided.
//In each space, the value becomes the number of adjacent "M" values if the space did not have a value of "M" already.
function _updateMinefieldNumbers($minefield) {
	$width = count($minefield);
	$height = count($minefield[0]);

	$adjacencies = array(
		array(0, -1),
		array(1, -1),
		array(1, 0),
		array(1, 1),
		array(0, 1),
		array(-1, 1),
		array(-1, 0),
		array(-1, 1)
	);

	for ($x = 0; $x < $width; $x++) {
		for ($y = 0; $y < $height; $y++) {
			if ($minefield[$x][$y] != "M") {
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
						if ($val == "M") {
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
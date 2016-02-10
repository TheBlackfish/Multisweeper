<?php

require_once('../../../database.php');
require_once('translateData.php');
require_once('mineGameConstants.php');

function getMinefieldWithVisibility($minefield, $visibility) {
	if (count($minefield) !== count($visibility)) {
		error_log("Error: Minefield size did not match visibility matrix size. Exiting.");
		die("Fatal error, exiting.");
	}

	$result = array();

	for ($x = 0; $x < count($minefield); $x++) {
		if (count($minefield[$x]) !== count($visibility[$x])) {
			error_log("Error: Minefield size did not match visibility matrix size. Exiting.");
			die("Fatal error, exiting.");
		}

		$temp = array();

		for ($y = 0; $y < count($minefield[$x]); $y++) {
			$val = "U";
			switch ($visibility[$x][$y]) {
				case 0:
					$val = "U";
					break;
				case 1:
					$val = "F";
					break;
				case 2:
					$val = $minefield[$x][$y];
					break;
			}
			array_push($temp, $val);
		}

		array_push($result, $temp);
	}

	return $result;
}

function checkIfSpaceIsUnrevealed($gameID, $xCoord, $yCoord) {
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$query = "SELECT visibility FROM multisweeper.games WHERE gameID = '" . $gameID . ";";
	$result = $conn->query($query);

	while ($row = mysqli_fetch_row($result)) {
		$field = translateMinefieldToPHP($row[0], $minefieldWidth, $minefieldHeight);
		return ($field[$xCoord][$yCoord] !== 2);
	}
}

?>
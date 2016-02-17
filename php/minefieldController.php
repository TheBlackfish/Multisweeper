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

	if ($query = $conn->prepare("SELECT visibility FROM multisweeper.games WHERE gameID=?")) {
		$query->execute();
		$query->bind_result($vis);
		$query->fetch();
		$query->close();

		$field = translateMinefieldToPHP($row[0], $minefieldWidth, $minefieldHeight);
		return ($field[$xCoord][$yCoord] !== 2);
	} else {
		error_log("Unable to prepare visibility statement. " . $conn->errno . ": " . $conn->error);
		return false;
	}
}

?>
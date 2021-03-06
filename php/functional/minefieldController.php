<?php

#This file contains various helper function to help with minefield control.

require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/constants/mineGameConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/functional/translateData.php');

#getMinefieldWithVisibility($gameID, $minefield, $visibility)
#Takes a minefield and visibility maps (both as double arrays), and returns a properly formatted minefield with visibility applied, as well as player actions.
#@param $gameID (Integer) The game ID that this operation is for.
#@param $minefield (Double Array) The properly formatted double array representing the minefield with all tiles revealed.
#@param $visibility (Double Array) The properly formatted double array representing the visibility of the minefield, including flags.
#@return The properly formatted double array with both visibility and player actions applied.
function getMinefieldWithVisibility($gameID, $minefield, $visibility, $wrecks) {
	if (count($minefield) !== count($visibility)) {
		error_log("minefieldController.php - Minefield size did not match visibility matrix size.");
		die("Fatal error, exiting.");
	}

	$result = array();

	for ($x = 0; $x < count($minefield); $x++) {
		if (count($minefield[$x]) !== count($visibility[$x])) {
			error_log("minefieldController.php - Minefield size did not match visibility matrix size.");
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

	foreach ($wrecks as $wreckKey => $wreckVal) {
		if (count($wreckVal) === 2) {
			$result[$wreckVal[0]][$wreckVal[1]] = "W";
		}
	}

	return $result;
}

#getPlayerActionsForGame($gameID)
#Returns an array of all player action coordinates.
#@param $gameID (Interger) The ID of the game to pull for.
#@return The properly formatted double array with coordinates for all players listed.
function getPlayerActionsForGame($gameID) {
	global $sqlhost, $sqlusername, $sqlpassword;
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$result = array();

	if ($query = $conn->prepare("SELECT xCoord, yCoord FROM sweepelite.actionqueue WHERE gameID=?")) {
		$query->bind_param("i", $gameID);
		$query->execute();
		$query->bind_result($xCoord, $yCoord);
		while ($query->fetch()) {
			array_push($result, array($xCoord, $yCoord));
		}
	} else {
		error_log("minefieldController.php - Unable to get player actions, returning with none.");
	}
	return $result;
}

?>
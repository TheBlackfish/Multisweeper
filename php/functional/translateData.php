<?php
	
#This file contains various helper functions to assist with the translation of data for different formats.

#translateMinefieldToPHP($data, $height, $width)
#Takes a string representing a minefield and turns it into a double array.
#@param $data (String) The minefield in string form.
#@param $height (Integer) The height of the final minefield.
#@param $width (Integer) The width of the final minefield.
#@return The double array representing the minefield.
function translateMinefieldToPHP($data, $height, $width) {
	if (strlen($data) != ($height * $width)) {
		throw new Exception("translateData.php - MySQL data provided does not match size given!", 1);
	}
	$chunks = str_split($data, $height);
	$result = array();
	for ($x = 0; $x < count($chunks); $x++) {
		$currentChunk = $chunks[$x];
		$tempArray = str_split($currentChunk, 1);
		array_push($result, $tempArray);
	}
	return $result;
}

#translateMinefieldToMySQL($data)
#Takes a double array representing a minefield and turns it into a string.
#@param $data (Double Array) The minefield in double array form.
#@return The string representing the minefield.
function translateMinefieldToMySQL($data) {
	$width = count($data);
	$height = count($data[0]);
	$result = "";
	for ($x = 0; $x < $width; $x++) {
		for ($y = 0; $y < $height; $y++) {
			$result = $result . $data[$x][$y];
		}
	}
	return $result;
}

#translateTanksToPHP($data)
#Takes a string representing the coordinates of tanks and returns a double array representing the coordinates of tanks.
#@param $data (String) The string representing the coordinates of tanks in a game.
#@return The double array containing all of the tank coordinates in a game.
function translateTanksToPHP($data) {
	$tanks = array();
	if ($data !== null) {
		if (strlen($data) > 0) {
			$temptanks = explode("/", $data);
			foreach ($temptanks as $k => $v) {
				$tankPos = explode(",", $v);
				if (count($tankPos) !== 2) {
					error_log("translateData.php - unexpected number of numbers while translating tanks from MySQL to PHP!");
				} else {
					$newTank = array(intval($tankPos[0]), intval($tankPos[1]));
					array_push($tanks, $newTank);
				} 
			}
		}
	}
	return $tanks;
}

#translateMinefieldToMySQL($data)
#Takes a double array representing tank coordinates and returns a string containing all of those coordinates.
#@param $data (Double Array) The coordinates of all the tanks in double array form.
#@return The string representing the tank coordinates.
function translateTanksToMySQL($data) {
	$tempStrs = array();
	foreach ($data as $k => $v) {
		if (count($v) !== 2) {
			error_log("translateData.php - unexpected number of numbers while translating tanks from PHP to MySQL!");
		} else {
			array_push($tempStrs, $v[0] . "," . $v[1]);
		}
	}
	if (count($tempStrs) > 0) {
		return implode("/", $tempStrs);
	}
	return "";
} 

?>
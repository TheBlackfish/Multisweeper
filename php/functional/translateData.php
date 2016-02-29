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
		throw new Exception("translateMinefieldToPHP - Data provided does not match size given!", 1);
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

?>
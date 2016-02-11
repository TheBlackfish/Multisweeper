<?php
	
//Translates a string of VARCHARs to the multidimensional arrays needed, using the provided height and width variables.
function translateMinefieldToPHP($data, $height, $width) {
	if (strlen($data) != ($height * $width)) {
		throw new Exception("translateMinefieldToPHP - Data provided does not match size given!", 1);
	}

	$chunks = str_split($data, $width);

	$result = array();

	for ($x = 0; $x < count($chunks); $x++) {
		$currentChunk = $chunks[$x];
		#error_log("translateMinefieldToPHP - processing chunk #" . $x . ", value: " . $currentChunk);
		$tempArray = str_split($currentChunk, 1);
		error_log("translateMinefieldToPHP - tempArray value = " . implode(",", $tempArray));
		array_push($result, $tempArray);
	}

	return $result;
}

//Translates a multidimensional array to the appropriate form for MySQL.
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
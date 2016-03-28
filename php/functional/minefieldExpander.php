<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/minefieldPopulater.php'); 

#expandMinefield($minefield, $visibility, $widthToAdd, $minesToAdd)
#Takes the given parameters and expands the minefield given to have its original width plus the width to add.
#@param $minefield - The double array representing a minefield.
#@param $visibility - The double array representing the visibility of the minefield.
#@param $widthToAdd - The width to add onto the minefield.
#@param $minesToAdd - The number of mines to place in the newly expanded area. If 0 or less, this becomes the total area of the new expansion divided by 4, rounded down.
#@return An associative array with two values - "minefield" and "visibility".
function expandMinefield($minefield, $visibility, $widthToAdd, $minesToAdd) {
	$height = count($minefield[0]);

	if ($minesToAdd <= 0) {
		$minesToAdd = floor($width * $height * 0.25);
	}

	$areaToAdd = createMinefieldArea($widthToAdd, $height, $minesToAdd);

	foreach ($areaToAdd as $key => $value) {
		array_push($minefield, $value);
		array_push($visibility, array_fill(0, $height, 0));
	}

	$minefield = updateMinefieldNumbers($minefield);

	$ret = new array(
		"minefield"		=>	$minefield,
		"visibility"	=>	$visibility
	);
}

?>
<?php

#This file contains all of the functionality relating to wreckages.
#All wreckages are stored in a double array as coordinates.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');

#addWreck($map, $allWrecks, $sourceCoordinates)
#Adds a wreckage to the map randomly one space away from the source coordinates provided.
#@param $map - The map of the minefield.
#@param $allWrecks - All of the wreckages thus far in double array form.
#@param $sourceCoordinates - The coordinates to make a wreckage from in array form.
#@return The updated wreckage array.
function addWreck($map, $allWrecks, $sourceCoordinates) {
	global $adjacencies;

	$candidates = array();
	$maxX = count($map);
	$maxY = count($map[0]);

	foreach ($adjacencies as $key => $value) {
		$targetX = $sourceCoordinates[0] + $value[0];
		$targetY = $sourceCoordinates[1] + $value[1];

		$valid = true;
		if (($targetX < 0) || ($targetX >= $maxX)) {
			$valid = false;
		} else if (($targetY < 0) || ($targetY >= $maxY)) {
			$valid = false;
		}

		if ($valid) {
			foreach ($allWrecks as $wkey => $wval) {
				if ($wval[0] === $targetX) {
					if ($wval[1] === $targetY) {
						$valid = false;
					}
				}
			}

			if ($valid) {
				array_push($candidates, array($targetX, $targetY));
			}
		}
	}

	$actual = $candidates[array_rand($candidates)];
	array_push($allWrecks, $actual);
	return removeDuplicateWrecks($allWrecks);
}

#addWreckOverrideDrift($allWrecks, $sourceCoordinates)
#Adds a wreckage to the map, ignoring any drift.
#@param $allWrecks - The array containing all current wreckages.
#@param $sourceCoordinates - The coordinates to add a wreckage to, ignoring drift.
#@return The updated wreckage array.
function addWreckOverrideDrift($allWrecks, $sourceCoordinates) {
	array_push($allWrecks, $sourceCoordinates);
	return removeDuplicateWrecks($allWrecks);
}

#removeDuplicateWrecks($allWrecks)
#Helper function to remove duplicate wreckages.
#@param $allWrecks - The array containing all of the wreckages to clean.
#@return The updated wreckage array.
function removeDuplicateWrecks($allWrecks) {
	$copy = array();

	while (count($allWrecks) > 0) {
		$toMove = array_shift($allWrecks);

		foreach ($allWrecks as $wreckKey => $wreckVal) {
			if ($toMove[0] === $wreckVal[0]) {
				if ($toMove[1] === $wreckVal[1]) {
					unset($allWrecks[$wreckKey]);
				}
			}
		}

		array_push($copy, $toMove);
	}

	return $copy;
}

?>
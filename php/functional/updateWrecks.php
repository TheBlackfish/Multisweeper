<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');

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

function addWreckOverrideDrift($allWrecks, $sourceCoordinates) {
	array_push($allWrecks, $sourceCoordinates);
	return removeDuplicateWrecks($allWrecks);
}

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
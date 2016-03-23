<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/minefieldPopulater.php'); 

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
<?php

function createMinefieldArea($width, $height, $mines) {
	$ret = array_fill(0, $width, array_fill(0, $height, 0));

	while ($mines > 0) {
		$xKey = array_rand($ret);
		$yKey = array_rand($ret[$xKey]);
		if ($ret[$xKey][$yKey] == 0) {
			$ret[$xKey][$yKey] = "M";
			$mines--;
		}
	}

	return $ret;
}

#_updateMinefieldNumbers($minefield)
#Takes a double array with 0's and M's and marks each value with the number of adjacent M's if it is not an M.
#@param $minefield (Double Array) A double array acting as a minefield to be adjusted.
#@return The minefield updated to correctly reflect adjacencies.
function updateMinefieldNumbers($minefield) {
	global $adjacencies;

	$width = count($minefield);
	$height = count($minefield[0]);

	for ($x = 0; $x < $width; $x++) {
		for ($y = 0; $y < $height; $y++) {
			if ($minefield[$x][$y] !== "M") {
				$numToInsert = 0;

				foreach ($adjacencies as $adj) {
					$shouldCalc = true;

					if (($x + $adj[0] < 0) or ($x + $adj[0] >= $width)) {
						$shouldCalc = false;
					}
					if (($y + $adj[1] < 0) or ($y + $adj[1] >= $height)) {
						$shouldCalc = false;
					}

					if ($shouldCalc) {
						$val = $minefield[$x + $adj[0]][$y + $adj[1]];
						if ($val === "M") {
							$numToInsert++;
						}
					}
				}
				$minefield[$x][$y] = $numToInsert;
			}
		}
	}
	
	return $minefield;
}

?>
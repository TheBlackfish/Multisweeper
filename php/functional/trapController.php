<?php

#This file controls all of the traps, including activation and detonation.
#Traps are stored in a double array. Each trap entry is an array with the following structure - [Type of trap, X coordinate, Y coordinate].
#Currently, the traps are as follows-
	#0 - Proximity Mine
		#Activates when a tank moves within range.
		#Destroys all tanks and reveals all tiles within that same range.
	#1 - Radio Nest
		#Activates constantly.
		#Flags all mines in the near-by area.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/updateWrecks.php');

#addTrap($traps, $type, $x, $y)
#Updates the trap array provided with the new trap.
#@param $traps - The array containing all of the traps.
#@param $type - The type of trap to add to the minefield.
#@param $x - The x-coordinate of the trap to add.
#@param $y - The y-coordinate of the trap to add.
#@return The updated trap array.
function addTrap($traps, $type, $x, $y) {
	array_push($traps, array(
		$type,
		$x,
		$y
	));
	return $traps;
}

#getCooldownForTrapType($type)
#Returns the number of dig actions needed to get a trap off of cooldown. Default = 3.
function getCooldownForTrapType($type) {
	if ($type === 0) { #Proximity Mine
		return 8;
	} else if ($type === 1) { #Radio Nest
		return 20;
	}
	return 3;
}

#resolveTraps($map, $visibility, $tanks, $enemyTanks, $traps, $wrecks)
#Goes through all the traps and figures out which ones activate. Each one that activates then resolves appropriately before being removed from the traps array.
#@param $map - The double array representing the minefield.
#@param $visibility - The double array representing visibility on the minefield.
#@param $tanks - The double array representing all friendly tanks.
#@param $enemyTanks - The double array representing all enemy tanks.
#@param $traps - The double array representing all traps.
#@param $wrecks - The double array representing all wreckages.
#@return The associative array with updated variables for everything provided to the function.
function resolveTraps($map, $visibility, $tanks, $enemyTanks, $traps, $wrecks) {
	$maxX = count($map);
	$maxY = count($map[0]);
	$proximityRange = 3;
	$radioRange = 5;

	#Go through each trap and "activate" certain types if certain criteria are met.
	$activatedTrapKeys = array();

	foreach ($traps as $trapKey => $trapVal) {
		if (intval($trapVal[0]) === 0) {	#Proximity Mine
			$targets = getAllCoordinatesWithinRange($trapVal[1], $trapVal[2], $proximityRange, $maxX, $maxY);

			$activated = false;
			foreach ($targets as $k => $v) {
				if (!$activated) {
					foreach ($tanks as $tankKey => $tankVal) {
						if (!$activated) {
							if ($v[0] === $tankVal[0]) {
								if ($v[1] === $tankVal[1]) {
									$activated = true;
								}
							}
						}
					}

					if (!$activated) {
						foreach ($enemyTanks as $tankKey => $tankVal) {
							if (!$activated) {
								if ($v[0] === $tankVal[0]) {
									if ($v[1] === $tankVal[1]) {
										$activated = true;
									}
								}
							}
						}
					}
				}
			}

			if ($activated) {
				array_push($activatedTrapKeys, $trapKey);
			}
		} else if (intval($trapVal[0]) === 1) {	#Radio Nest
			array_push($activatedTrapKeys, $trapKey);
		}
	}

	#For each activated trap
	foreach ($activatedTrapKeys as $eh => $key) {
		$curTrap = $traps[$key];

		if (intval($curTrap[0]) === 0) { #Proximity Mine
			$targets = getAllCoordinatesWithinRange($trapVal[1], $trapVal[2], $proximityRange, $maxX, $maxY);
			foreach ($targets as $targetKey => $targetVal) {
				
				#Remove any wrecks
				foreach ($wrecks as $wreckKey => $wreckVal) {
					if ($targetVal[0] === $wreckVal[0]) {
						if ($targetVal[1] === $wreckVal[1]) {
							unset($wrecks[$wreckKey]);
						}
					}
				}

				#Remove any tanks and place a wreck
				foreach ($tanks as $tankKey => $tankVal) {
					if ($targetVal[0] === $tankVal[0]) {
						if ($targetVal[1] === $tankVal[1]) {
							unset($tanks[$tankKey]);
							$wrecks = addWreckOverrideDrift($wrecks, $tankVal);
						}
					}
				}

				#Remove any enemy tanks and place a wreck
				foreach ($enemyTanks as $tankKey => $tankVal) {
					if ($targetVal[0] === $tankVal[0]) {
						if ($targetVal[1] === $tankVal[1]) {
							unset($enemyTanks[$tankKey]);
							$wrecks = addWreckOverrideDrift($wrecks, $tankVal);
						}
					}
				}

				$visibility[$targetVal[0]][$targetVal[1]] = 2;
			}
		} else if (intval($curTrap[0]) === 1) {	#Radio Nest
			#Get all nearby tiles.
			$tilesInRange = getAllCoordinatesWithinRange($trapVal[1], $trapVal[2], $radioRange, $maxX, $maxY);
			
			#Get all unflagged mines in the targets.
			$targets = array();

			foreach($tilesInRange as $meh => $tile) {
				if ($map[$tile[0]][$tile[1]] === "M") {
					if ($visibility[$tile[0]][$tile[1]] !== 1) {
						array_push($targets, $tile);
					}
				}
			}

			#If one or more
			if (count($targets) > 0) {
				#Change a random mine's visibility to flagged.
				$finalTarget = $targets[array_rand($targets)];
				$visibility[$finalTarget[0]][$finalTarget[1]] = 1;

				#Remove the nest from the activated traps if no tanks on the same space.
				$remove = true;

				foreach ($tanks as $tankKey => $tankVal) {
					if ($tankVal[0] === $curTrap[1]) {
						if ($tankVal[1] === $curTrap[2]) {
							$remove = false;
						}
					}
				}

				foreach ($enemyTanks as $tankKey => $tankVal) {
					if ($tankVal[0] === $curTrap[1]) {
						if ($tankVal[1] === $curTrap[2]) {
							$remove = false;
						}
					}
				}

				if ($remove) {
					unset($activatedTrapKeys[$eh]);
				}
			}	
		}
	}

	$traps = array_diff_key($traps, array_flip($activatedTrapKeys));

	$ret = array(
		'map'			=>	$map,
		'visibility'	=>	$visibility,
		'friendlyTanks'	=>	$tanks,
		'enemyTanks'	=>	$enemyTanks,
		'wrecks'		=>	$wrecks,
		'traps'			=>	$traps
	);

	return $ret;	
}

#getAllCoordinatesWithinRange($x, $y, $range, $maxX, $maxY)
#Returns an array containing all coordinates on a grid within the specified range of the provided center point.
#@param $x - The x-coordinate of the center point.
#@param $y - The y-coordinate of the center point.
#@param $range - The range in integer form.
#@param $maxX - The maximum x-coordinate available.
#@param $maxY - The maximum y-coordinate available.
#@return All of the candidate coordinates in a double array.
function getAllCoordinatesWithinRange($x, $y, $range, $maxX, $maxY) {
	global $adjacencies;

	$targets = array(array($x, $y));

	$possibilities = array();

	foreach ($adjacencies as $k => $adj) {
		array_push($possibilities, array($x + $adj[0], $y + $adj[1]));
	}

	while (count($possibilities) > 0) {
		$cur = array_shift($possibilities);

		$valid = false;
		if ($cur[0] >= 0 && $cur[0] < $maxX) {
			if ($cur[1] >= 0 && $cur[1] < $maxY) {
				$valid = true;
			}
		}

		if ($valid) {
			$distance = sqrt(pow($x - $cur[0],2) + pow($y - $cur[1],2));
			if ($distance <= $range) {
				array_push($targets, $cur);

				foreach ($adjacencies as $k => $adj) {
					$next = array($cur[0] + $adj[0], $cur[1] + $adj[1]);
					
					$add = true;
					foreach ($targets as $k2 => $targetVal) {
						if ($next[0] === $targetVal[0]) {
							if ($next[1] === $targetVal[1]) {
								$add = false;
							}
						}
					}

					if ($add) {
						foreach ($possibilities as $k3 => $posVal) {
							if ($next[0] === $posVal[0]) {
								if ($next[1] === $posVal[1]) {
									$add = false;
								}
							}
						}

						if ($add) {
							array_push($possibilities, $next);
						}
					}
				}
			}
		}
	}

	return $targets;
}

?>
<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/updateWrecks.php');

function addTrap($traps, $type, $x, $y) {
	array_push($traps, array(
		$type,
		$x,
		$y
	));
	return $traps;
}

function getCooldownForTrapType($type) {
	if ($type === 0) { #Proximity Mine
		return 8;
	}
	return 3;
}

function resolveTraps($map, $visibility, $tanks, $enemyTanks, $traps, $wrecks) {
	$maxX = count($map);
	$maxY = count($map[0]);
	$proximityRange = 3;

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
				error_log("Proximity activated!");
			}
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

			unset($traps[$key]);
		}
	}

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
		if ($cur[0] >= 0 || $cur[0] < $maxX) {
			if ($cur[1] >= 0 || $cur[1] < $maxY) {
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
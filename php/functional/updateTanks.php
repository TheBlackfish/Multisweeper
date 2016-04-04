<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/updateWrecks.php');

#addFriendlyTank($map, $visibility)
#Adds a friendly tank to the leftmost column to the map provided. This addition will never go onto a visible mine or flags, and will try 3 times to not place on an unrevealed mine. If not possible, the tank will be placed on a random row instead, regardless of mines or flags.
#@param $map (Double Array) The map of the minefield to place a tank on.
#@param $visibility (Double Array) The visibility map of the minefield to place a tank on.
#@return An array with two values: 'newTankPosition', which if not null, is a coordinate for a new tank; and 'newVisibility', which if not null, is the updated visibility map
function addFriendlyTank($map, $visibility) {
	$ret = array(
		'newTankPosition'	=>	null,
		'newVisibility'		=>	null
	);

	#Find out all possible spaces on the leftmost column.
	$candidates = array();

	for ($i=0; $i < count($map[0]); $i++) { 
		array_push($candidates, $i);
	}

	#Eliminate all visible mines and flags from the possibilities.
	foreach ($candidates as $k => $v) {
		$remove = false;

		if ($visibility[0][$v] === 1) {
			$remove = true;
		}

		if ($map[0][$v] === "M") {
			if ($visibility[0][$v] === 2) {
				$remove = true;
			}
		}

		if ($remove) {
			unset($candidates[$k]);
		}
	}

	if (count($candidates) > 0) {
		$destination = null;
		$tries = 3;

		#While no place found and tries are available
		while (($destination === null) && ($tries > 0) && (count($candidates) > 0)) {
			#Choose a random spot
			$destination = array_rand($candidates);

			#If spot is unrevealed mine
			if ($map[0][$destination] === "M") {
				#Remove row from choices
				if (($key = array_search($destination, $candidates)) !== false) {
					unset($candidates[$key]);
				}

				#Remove row from place found 
				$destination = null;
			}
				
			#Tries--
			$tries = $tries - 1;
		}
		
			
		#If no chosen spot
		if ($destination === null) {
			#Choose random position for destination
			$destination = rand(0, count($map[0]));
		}

		#If chosen spot is mine
		if ($map[0][$destination] === "M") {
			#Return visibility change
			$visibility[0][$destination] = 2;
		#Else
		} else {
			#Return new tank position
			$ret["newTankPosition"] = array(0, $destination);
		}			
	} else {
		#Choose a random spot and throw the tank there.
		$destination = rand(0, count($map[0]));

		#If chosen spot is mine
		if ($map[0][$destination] === "M") {
			#Roll odds. 67% chance to reveal mine.
			if (rand(0,2) !== 0) {
				#Reveal tile and do not place tank
				$visibility[0][$destination] = 2;
			} else {
				#Return new tank position
				$ret['newTankPosition'] = array(0, $destination);
			}
		#Else
		} else {
			#Return new tank position
			$ret['newTankPosition'] = array(0, $destination);
		}		
	}

	return $ret;
}

#addEnemyTank($map, $visibility)
#Adds a enemy tank to the rightmost column to the map provided. This addition will never go onto a visible mine.
#@param $map (Double Array) The map of the minefield to place a tank on.
#@param $visibility (Double Array) The visibility map of the minefield to place a tank on.
#@return An array with two values: 'newTankPosition', which if not null, is a coordinate for a new tank; and 'newVisibility', which if not null, is the updated visibility map
function addEnemyTank($map, $visibility) {
	$ret = array(
		'newTankPosition'	=>	null,
		'newVisibility'		=>	null
	);

	$end = count($map) - 1;

	#Find out all possible spaces on the leftmost column.
	$candidates = array();

	for ($i=0; $i < count($map[$end]); $i++) { 
		array_push($candidates, $i);
	}

	#Eliminate all visible mines and flags from the possibilities.
	foreach ($candidates as $k => $v) {
		$remove = false;

		if ($visibility[$end][$v] === 1) {
			$remove = true;
		} else if (($visibility[$end][$v] === 2) && ($map[$end][$v] === "M")) {
			$remove = true;
		}

		if ($remove) {
			unset($candidates[$k]);
		}
	}

	if (count($candidates) > 0) {
		$ret['newTankPosition'] = array($end, $candidates[rand(0, count($candidates) - 1)]);		
	} else {
		$target = array($end, rand(0, count($map[$end])));
		if ($visibility[$target[0]][$target[1]] === 1) {
			if ($map[$target[0]][$target[1]] === "M") {
				$visibility[$target[0]][$target[1]] = 2;
				$target = null;
			} else {
				$visibility[$target[0]][$target[1]] = 0;
			}
		}

		if ($target !== null) {
			$ret['newTankPosition'] = $target;
		}
		$ret['newVisibility'] = $visibility;
	}

	return $ret;
}

#updateTanks($map, $visibility, $friendlyTankPositions, $enemyTankPositions, $wrecks)
#This function updates all tanks, both friendly and enemy. Before and after updating, collisions are checked and wrecked.
#@param $map (Double Array) The map for tanks to navigate.
#@param $visibility (Double Array) The visibility of the minefield for the tanks to navigate.
#@param $friendlyTankPositions (Double Array) The array containing all of the current friendly tank coordinates.
#@param $enemyTanksPositions (Double Array) The array containing all of the current enemy tank coordinates.
#@param $wrecks (Double Array) The array containing all of the current wreckages.
#@return The associative array containing updated values for all parameters provided.
function updateTanks($map, $visibility, $friendlyTankPositions, $enemyTankPositions, $wrecks) {
	#First eliminate any friendly and enemy tanks right next to each other in the same row.
	foreach ($friendlyTankPositions as $friendlyKey => $friendlyVal) {
		$removed = false;
		foreach ($enemyTankPositions as $enemyKey => $enemyVal) {
			if (!$removed) {
				if ($friendlyVal[1] === $enemyVal[1]) {
					if (abs($friendlyVal[0] - $enemyVal[0]) <= 1) {
						unset($friendlyTankPositions[$friendlyKey]);
						$wrecks = addWreck($map, $wrecks, $friendlyVal);
						unset($enemyTankPositions[$enemyKey]);
						$wrecks = addWreck($map, $wrecks, $enemyVal);
						$removed = true;
						error_log("Removed tanks!");
					}
				}
			}
		}
	}

	#Update friendly tanks
	$friendlyResults = updateFriendlyTanks($map, $visibility, $friendlyTankPositions, $wrecks);

	#Update enemy tanks
	$enemyResults = updateEnemyTanks($map, $friendlyResults['updatedVisibility'], $enemyTankPositions, $friendlyResults['updatedWrecks']);

	$friendlyUpdated = $friendlyResults['updatedTanks'];
	$enemyUpdated = $enemyResults['updatedTanks'];
	$wrecksUpdated = $enemyResults['updatedWrecks'];
	$visUpdated = $enemyResults['updatedVisibility'];

	#Elminate any friendly & enemy tanks that share the same space.
	foreach ($friendlyUpdated as $friendlyKey => $friendlyVal) {
		$removed = false;
		foreach ($enemyUpdated as $enemyKey => $enemyVal) {
			if (!$removed) {
				if ($friendlyVal[1] === $enemyVal[1]) {
					if ($friendlyVal[0] === $enemyVal[0]) {
						unset($friendlyUpdated[$friendlyKey]);
						$wrecksUpdated = addWreck($map, $wrecksUpdated, $friendlyVal);
						unset($enemyUpdated[$enemyKey]);
						$wrecksUpdated = addWreck($map, $wrecksUpdated, $enemyVal);
						$removed = true;
					}
				}	
			}
		}

		if (!$removed) {
			foreach ($wrecksUpdated as $wreckKey => $wreckVal) {
				if ($friendlyVal[0] === $wreckVal[0]) {
					if ($friendlyVal[1] === $wreckVal[1]) {
						unset($wrecksUpdated[$wreckKey]);
					}
				}
			}
		}
	}

	foreach ($enemyUpdated as $enemyKey => $enemyVal) {
		foreach ($wrecksUpdated as $wreckKey => $wreckVal) {
			if ($enemyVal[0] === $wreckVal[0]) {
				if ($enemyVal[1] === $wreckVal[1]) {
					unset($wrecksUpdated[$wreckKey]);
				}
			}
		}
	}

	#Combine results and return
	$ret = array(
		'updatedFriendlyTanks'	=>	$friendlyUpdated,
		'updatedEnemyTanks'		=>	$enemyUpdated,
		'updatedWrecks'			=>	$wrecksUpdated,
		'updatedVisibility'		=>	$visUpdated
	);

	return $ret;
}

#updateFriendlyTanks($map, $visibility, $friendlyTankPositions, $wrecks)
#This function updates all friendly tanks, avoiding visible mines and flags. Any wreckages moved over are removed.
#@param $map (Double Array) The map for tanks to navigate.
#@param $visibility (Double Array) The visibility of the minefield for the tanks to navigate.
#@param $friendlyTankPositions (Double Array) The array containing all of the current friendly tank coordinates.
#@param $wrecks (Double Array) The array containing all of the current wreckages.
#@return The associative array containing updated values for all parameters provided.
function updateFriendlyTanks($map, $visibility, $friendlyTankPositions, $wrecks) {
	global $friendlyTankMoves;

	$maxX = count($map);
	$maxY = count($map[0]);

	$updatedTankPositions = array();

	#For each tank
	foreach ($friendlyTankPositions as $key => $tank) {
		$pathFound = false;
		$allPaths = array();
		$tempTankMoves = $friendlyTankMoves[rand(0,1)];

		#Add the initial space they are in to the array of paths.
		$path = array(
			'path' => array($tank),
			'heur' => 0);
		array_push($allPaths, $path);

		#While path to end not found
		while (!$pathFound && (count($allPaths) > 0)) {
			#Remove first path from array
			$curPathArray = array_shift($allPaths);
			$curPath = $curPathArray['path'];
			$curHeur = $curPathArray['heur'];
			$numAdded = 0;

			#If path goes past edge
			if ((end($curPath)[0] >= $maxX) || (count($curPath) >= 6)) {
				#Update tank position to move along the path chosen
				reset($curPath);
				$toPush = next($curPath);
				#Only add to updated tank positions if the path is within the grid.
				if ($toPush[0] < $maxX) {
					array_push($updatedTankPositions, $toPush);
				}
				$pathFound = true;
			} else {
				#For each vertical variation
				foreach ($tempTankMoves as $key => $move) {
					#If next movement with vertical variation is a legal move
					$nextX = end($curPath)[0] + $move[0];
					$nextY = end($curPath)[1] + $move[1];

					$shouldAdd = true;
					if ($nextX < 0) {
						#Tank somehow goes off map in opposite direction.
						$shouldAdd = false;
					} elseif ($nextX >= $maxX) {
						$shouldAdd = true;
					} elseif (($nextY < 0) || ($nextY >= $maxY)) {
						#Tank goes off map vertically.
						$shouldAdd = false;
					} elseif ($visibility[$nextX][$nextY] == 1) {
						#Tank would move onto a flag.
						$shouldAdd = false;
					} elseif (($visibility[$nextX][$nextY] == 2) && ($map[$nextX][$nextY] === "M")) {
						#Tank would move onto a visible mine.
						$shouldAdd = false;
					} else {
						#Tank would move onto a position occupied by another tank after moving.
						foreach ($updatedTankPositions as $key => $otherTank) {
							if (($otherTank[0] === $nextX) && ($otherTank[1] === $nextY)) {
								$shouldAdd = false;
							}
						}
					}

					if ($shouldAdd === true) {
						$copyPath = $curPath;
						$numAdded = $numAdded + 1;

						#Add square to path
						$newPath = array($nextX, $nextY);
						array_push($copyPath, $newPath);

						$val = 0;
						#If the tile is on the grid x-wise
						if (($nextX < $maxX) && ($nextY < $maxY)) {
							#If the next tile is unrevealed, add to the heuristic value.
							#Otherwise, keep adding forward movements to the path until the next tile added would be an unrevealed tile.
							if ($visibility[$nextX][$nextY] == 0) {
								$val = 50;
							} else {
								$skipX = $nextX + 1;
								if ($skipX < $maxX) {
									while (($skipX < $maxX) && ($visibility[$skipX][$nextY] === 2) && ($map[$skipX][$nextY] !== "M")) {
										$skipAhead = array($skipX, $nextY);
										array_push($copyPath, $skipAhead);
										$skipX = $skipX + 1;
									}
								}
							}
						} else {
							#Decrease the heuristic value, as we have a path that goes off the grid.
							$val = -5;
						}
						
						$pathObjToAdd = array(
							'path' => $copyPath,
							'heur' => $val + $curHeur
						);

						#Insert path into array while sorting for heuristic value
						array_push($allPaths, $pathObjToAdd);
					}
				}

				if ($numAdded >= 6) {
					usort($allPaths, "_sortTankPaths");
					$numAdded = 0;
				}
			}				
		}

		if (!$pathFound && (count($allPaths) === 0)) {
			array_push($updatedTankPositions, array(
				$tank[0] + 1,
				$tank[1]
			));
		}
	}

	#For each new position
	foreach ($updatedTankPositions as $key => $value) {
		#Validate that the updated position is valid.
		$validated = false;
		if (count($value) === 2) {
			if (($value[0] >= 0) && ($value[0] < $maxX)) {
				if (($value[1] >= 0) && ($value[1] < $maxY)) {
					$validated = true;
				}
			}
		}

		if ($validated) {
			#Check value of tile
			#If mine
			if ($map[$value[0]][$value[1]] === "M") {
				#Roll odds. 75% chance to reveal mine.
				if (rand(0,3) !== 0) {
					#Reveal tile and remove tank
					$visibility[$value[0]][$value[1]] = 2;
					unset($updatedTankPositions[$key]);
					$wrecks = addWreck($map, $wrecks, $value);
				}
			}
		} else {
			unset($updatedTankPositions[$key]);
		}
	}

	$ret = array(
		'updatedVisibility' => $visibility,
		'updatedTanks'		=> $updatedTankPositions,
		'updatedWrecks'		=> $wrecks
	);	

	return $ret;
}

#updateEnemyTanks($map, $visibility, $enemyTankPositions, $wrecks)
#This function updates all enemy tanks, avoiding visible mines and flags. Any wreckages moved over are removed.
#@param $map (Double Array) The map for tanks to navigate.
#@param $visibility (Double Array) The visibility of the minefield for the tanks to navigate.
#@param $enemyTankPositions (Double Array) The array containing all of the current friendly tank coordinates.
#@param $wrecks (Double Array) The array containing all of the current wreckages.
#@return The associative array containing updated values for all parameters provided.
function updateEnemyTanks($map, $visibility, $enemyTankPositions, $wrecks) {
	global $enemyTankMoves;

	$maxX = count($map);
	$maxY = count($map[0]);

	$updatedTankPositions = array();

	#For each tank
	foreach ($enemyTankPositions as $key => $tank) {
		$pathFound = false;
		$allPaths = array();
		$tempTankMoves = $enemyTankMoves[rand(0,1)];

		#Add the initial space they are in to the array of paths.
		$path = array(
			'path' => array($tank),
			'heur' => 0);
		array_push($allPaths, $path);

		#While path to end not found
		while (!$pathFound && (count($allPaths) > 0)) {
			#Remove first path from array
			$curPathArray = array_shift($allPaths);
			$curPath = $curPathArray['path'];
			$curHeur = $curPathArray['heur'];
			$numAdded = 0;

			#If path goes past edge
			if ((end($curPath)[0] <= 0) || (count($curPath) >= 6)) {
				#Update tank position to move along the path chosen
				reset($curPath);
				$toPush = next($curPath);
				#Only add to updated tank positions if the path is within the grid.
				if ($toPush[0] >= 0) {
					array_push($updatedTankPositions, $toPush);
				}
				$pathFound = true;
			} else {
				#For each vertical variation
				foreach ($tempTankMoves as $key => $move) {
					#If next movement with vertical variation is a legal move
					$nextX = end($curPath)[0] + $move[0];
					$nextY = end($curPath)[1] + $move[1];

					$shouldAdd = true;
					if ($nextX >= $maxX) {
						#Tank somehow goes off map in opposite direction.
						$shouldAdd = false;
					} elseif ($nextX <= 0) {
						$shouldAdd = true;
					} elseif (($nextY < 0) || ($nextY >= $maxY)) {
						#Tank goes off map vertically.
						$shouldAdd = false;
					} elseif (($visibility[$nextX][$nextY] == 2) && ($map[$nextX][$nextY] === "M")) {
						#Tank would move onto a visible mine.
						$shouldAdd = false;
					} else {
						#Tank would move onto a position occupied by another tank after moving.
						foreach ($updatedTankPositions as $key => $otherTank) {
							if (($otherTank[0] === $nextX) && ($otherTank[1] === $nextY)) {
								$shouldAdd = false;
							}
						}
					}

					if ($shouldAdd === true) {
						$copyPath = $curPath;
						$numAdded = $numAdded + 1;

						#Add square to path
						$newPath = array($nextX, $nextY);
						array_push($copyPath, $newPath);

						$val = 0;
						#If the tile is on the grid x-wise
						if (($nextX < $maxX) && ($nextY < $maxY)) {
							#If the next tile is unrevealed, add to the heuristic value.
							#Otherwise, keep adding forward movements to the path until the next tile added would be an unrevealed tile.
							if ($visibility[$nextX][$nextY] == 0) {
								$val = 50;
							} else {
								$skipX = $nextX + 1;
								if ($skipX < $maxX) {
									while (($skipX < $maxX) && ($visibility[$skipX][$nextY] === 2) && ($map[$skipX][$nextY] !== "M")) {
										$skipAhead = array($skipX, $nextY);
										array_push($copyPath, $skipAhead);
										$skipX = $skipX + 1;
									}
								}
							}
						} else {
							#Decrease the heuristic value, as we have a path that goes off the grid.
							$val = -5;
						}
						
						$pathObjToAdd = array(
							'path' => $copyPath,
							'heur' => $val + $curHeur
						);

						#Insert path into array while sorting for heuristic value
						array_push($allPaths, $pathObjToAdd);
					}
				}

				if ($numAdded >= 6) {
					usort($allPaths, "_sortTankPaths");
					$numAdded = 0;
				}
			}				
		}

		if (!$pathFound && (count($allPaths) === 0)) {
			array_push($updatedTankPositions, array(
				$tank[0] - 1,
				$tank[1]
			));
		}
	}

	#For each new position
	foreach ($updatedTankPositions as $key => $value) {
		#Validate that the updated position is valid.
		$validated = false;
		if (count($value) === 2) {
			if (($value[0] >= 0) && ($value[0] < $maxX)) {
				if (($value[1] >= 0) && ($value[1] < $maxY)) {
					$validated = true;
				}
			}
		}

		if ($validated) {
			#Check value of tile
			#If flagged
			if ($visibility[$value[0]][$value[1]] >= 1) {
				#Check if mine
				if ($map[$value[0]][$value[1]] === "M") {
					$visibility[$value[0]][$value[1]] = 2;
					unset($updatedTankPositions[$key]);
					$wrecks = addWreck($map, $wrecks, $value);
				} else {
					if ($visibility[$value[0]][$value[1]] === 1) {
						$visibility[$value[0]][$value[1]] = 0;
					}
				}
			} 
		} else {
			unset($updatedTankPositions[$key]);
		}
	}

	$ret = array(
		'updatedVisibility' => $visibility,
		'updatedTanks'		=> $updatedTankPositions,
		'updatedWrecks'		=> $wrecks
	);		

	return $ret;	
}

#_sortTankPaths($a, $b)
#A helper function for sorting heuristic objects for tank navigation. Sorts the two objects given by their heuristic value.
#@param $a (Array) An array that must have the key 'heur' set.
#@param $b (Array) An array that must have the key 'heur' set.
#@return The comparison value of the two arrays.
function _sortTankPaths($a, $b) {
	$valA = $a['heur'];
	$valB = $b['heur'];

	if ($valA === $valB) {
		return 0;
	}

	return ($valA < $valB) ? -1 : 1;
}

?>
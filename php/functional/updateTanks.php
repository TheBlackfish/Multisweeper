<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');

#addTank($map, $visibility)
#Adds a tank to the leftmost column to the map provided. This addition will never go onto a visible mine or flags, and will try 3 times to not place on an unrevealed mine. If not possible, the tank will be placed on a random row instead, regardless of mines or flags.
#@param $map (Double Array) The map of the minefield to place a tank on.
#@param $visibility (Double Array) The visibility map of the minefield to place a tank on.
#@return An array with two values: 'newTankPosition', which if not null, is a coordinate for a new tank; and 'newVisibility', which if not null, is the updated visibility map
function addTank($map, $visibility) {
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
			#Return visibility change
			$visibility[0][$destination] = 2;
		#Else
		} else {
			#Return new tank position
			$ret['newTankPosition'] = array(0, $destination);
		}		
	}

	return $ret;
}

#updateTanks($map, $visibility, $tankPositions)
#For each tank provided, this function finds the best path for that tank through the map and visibility. Tanks will move forward one column and either one up, straight forward, or one down. When path-finding, tanks will prefer revealed non-mine tiles over unrevealed tiles. Tanks will never move onto flags, revealed mines, or other tanks unless they do not have a choice.
#@param $map (Double Array) The map for tanks to navigate.
#@param $visibility (Double Array) The visibility of the minefield for the tanks to navigate.
#@param $tankPositions (Double Array) The array containing all of the current tank coordinates.
#@return The double array containing all of the tank coordinates in a game.
function updateTanks($map, $visibility, $tankPositions) {
	global $tankMoves;

	$updatedTankPositions = array();

	#For each tank
	foreach ($tankPositions as $key => $tank) {
		$pathFound = false;
		$allPaths = array();

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

			#If path goes past edge
			if (end($curPath)[0] >= count($map)) {
				#Update tank position to move along the path chosen
				reset($curPath);
				array_push($updatedTankPositions, next($curPath));
				$pathFound = true;
			} else {
				#For each vertical variation
				foreach ($tankMoves as $key => $move) {
					#If next movement with vertical variation is a legal move
					$nextX = $curPath[0] + $move[0];
					$nextY = $curPath[1] + $move[1];

					$shouldAdd = true;
					if ($nextX < 0) {
						#Tank somehow goes off map in opposite direction.
						$shouldAdd = false;
					} else if (($nextY < 0) || ($nextY >= count($map[$nextX]))) {
						#Tank goes off map vertically.
						$shouldAdd = false;
					} else if ($visibility[$nextX][$nextY] === 1) {
						#Tank would move onto a flag.
						$shouldAdd = false;
					} else if (($visibility[$nextX][$nextY] === 2) && ($map[$nextX][$nextY] === "M")) {
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
					
					if ($shouldAdd) {
						$copyPath = $curPath;

						#Add square to path
						$newPath = array($nextX, $nextY);
						array_push($copyPath, $newPath);

						#Add increased value due to type of terrain change to that path
						$val = 1;
						if ($visibility[$nextX][$nextY] === 0) {
							$val = 50;
						}
						
						$pathObjToAdd = array(
							'path' => $newPath,
							'heur' => $val + $curHeur
						);

						#Insert path into array while sorting for heuristic value
						array_push($allPaths, $pathObjToAdd);
						usort($allPaths, "_sortTankPaths");
					}
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
		#Check value of tile
		#If mine
		if ($map[$value[0]][$value[1]] === "M") {
			#Reveal tile and remove tank
			$visibility[$value[0]][$value[1]] = 2;
			unset($updatedTankPositions[$key]);
		}
	}

	$ret = array(
		'updatedVisibility' => $visibility,
		'updatedTanks'		=> $updatedTankPositions
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
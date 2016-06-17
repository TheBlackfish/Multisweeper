<?php

#This file contains all logic towards medal calculations for players.

#calculateMedalAttributesForPlayer($digNumber)
#This function returns an array of medal levels related to the parameters given that are supposed to represent a single player's achievements in a game thus far.
#@param digNumber - (Int) The number of tiles dug by a player.
#@return An associative array with the medal levels for the player stats given. The medal names are as follows:
	#digMedal - The Diggers' Platoon medal.
function calculateMedalAttributesForPlayer($digNumber, $flagNumber) {
	$medalMinimums = array(
		array(0, 10, 25, 55, 115, 235, 475),
		array(0, 5, 10, 15, 20, 25, 30)
	);

	$digMedal = 0;

	for ($i=0; $i < count($medalMinimums[0]); $i++) { 
		if ($digNumber >= $medalMinimums[0][$i]) {
			$digMedal = $i;
		}
	}

	$flagMedal = 0;
	for ($i=0; $i < count($medalMinimums[1]); $i++) { 
		if ($flagNumber >= $medalMinimums[1][$i]) {
			$flagMedal = $i;
		}
	}

	return array(
		'digMedal'	=> $digMedal,
		'flagMedal'	=> $flagMedal
	);
}

?>
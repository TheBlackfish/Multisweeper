<?php

function calculateMedalAttributesForPlayer($digNumber) {
	$medalMinimums = array(
		array(0, 5, 10, 20,	35,	55,	80)
	);

	$digMedal = 0;

	for ($i=0; $i < count($medalMinimums[0]); $i++) { 
		if ($digNumber >= $medalMinimums[0][$i]) {
			$digMedal = $i;
		}
	}

	return array(
		'digMedal' => $digMedal
	);
}

?>
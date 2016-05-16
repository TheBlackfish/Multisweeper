<?php

function calculateMedalAttributesForPlayer($digNumber) {
	$medalMinimums = array(
		array(0, 10, 25, 55, 115, 235, 475)
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
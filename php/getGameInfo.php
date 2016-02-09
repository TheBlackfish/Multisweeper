<?php

require('../../../database.php');
require('minefieldController.php');
require('translateData.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');
	
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	
	$doc = new DOMDocument('1.0');
	$doc->formatOutput = true;

	$query = "SELECT map, visibility, height, width FROM multisweeper.games WHERE status = 'OPEN' ORDER BY gameID DESC LIMIT 1;";
	$result = $conn->query($query);

	while ($row = mysqli_fetch_row($result)) {
		$finalMap = getMinefieldWithVisibility($row[0], $row[1]);

		$newrow = $doc->createElement('minefield')

		$nodeA = $doc->createElement('map');
		$nodeA = $newrow->appendChild($nodeA);
		$nodeAText = $doc->createTextNode($finalMap);
		$nodeAText = $nodeA->appendChild($nodeAText);

		$nodeB = $doc->createElement('height');
		$nodeB = $newrow->appendChild($nodeB);
		$nodeBText = $doc->createTextNode($row[2]);
		$nodeBText = $nodeB->appendChild($nodeBText);

		$nodeC = $doc->createElement('width');
		$nodeC = $newrow->appendChild($nodeC);
		$nodeCText = $doc->createTextNode($row[3]);
		$nodeCText = $nodeC->appendChild($nodeCText);
	}
	
	$r = $doc->saveXML();
	echo $r;
}
?>
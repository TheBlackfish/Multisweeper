<?php

require_once('../../../database.php');
require_once('minefieldController.php');
require_once('translateData.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');
	
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	
	$doc = new DOMDocument('1.0');
	$doc->formatOutput = true;

	if ($query = $conn->prepare("SELECT map, visibility, height, width, gameID FROM multisweeper.games WHERE status='OPEN' ORDER BY gameID DESC LIMIT 1")) {
		$query->execute();
		$query->bind_result($map, $vis, $height, $width, $gameID);
		$query->fetch();
		$query->close();

		$finalMap = translateMinefieldToMySQL(getMinefieldWithVisibility(translateMinefieldToPHP($map, $height, $width), translateMinefieldToPHP($vis, $height, $width)));

		$newrow = $doc->createElement('minefield');
		$newrow = $doc->appendChild($newrow);

		$nodeID = $doc->createElement('id');
		$nodeID = $newrow->appendChild($nodeID);
		$nodeIDText = $doc->createTextNode($gameID);
		$nodeIDText = $nodeID->appendChild($nodeIDText);

		$nodeA = $doc->createElement('map');
		$nodeA = $newrow->appendChild($nodeA);
		$nodeAText = $doc->createTextNode($finalMap);
		$nodeAText = $nodeA->appendChild($nodeAText);

		$nodeB = $doc->createElement('height');
		$nodeB = $newrow->appendChild($nodeB);
		$nodeBText = $doc->createTextNode($height);
		$nodeBText = $nodeB->appendChild($nodeBText);

		$nodeC = $doc->createElement('width');
		$nodeC = $newrow->appendChild($nodeC);
		$nodeCText = $doc->createTextNode($width);
		$nodeCText = $nodeC->appendChild($nodeCText);
	} else {
		error_log("Unable to prepare map gathering statement. " . $conn->errno . ": " . $conn->error);
		$error = $doc->createElement('error');
		$error = $doc->appendChild($error);
		$errorText = $doc->createTextNode("Internal error occurred, please try again later.");
		$errorText = $error->appendChild($errorText);
	}
	
	$r = $doc->saveXML();
	echo $r;
}
?>
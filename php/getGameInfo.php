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

		$nodeID = $doc->createElement('id', $gameID);
		$nodeID = $newrow->appendChild($nodeID);

		$nodeA = $doc->createElement('map', $finalMap);
		$nodeA = $newrow->appendChild($nodeA);

		$nodeB = $doc->createElement('height', $height);
		$nodeB = $newrow->appendChild($nodeB);

		$nodeC = $doc->createElement('width', $width);
		$nodeC = $newrow->appendChild($nodeC);

		if ($playerQuery = $conn->prepare("SELECT username FROM multisweeper.players WHERE playerID IN (SELECT playerID FROM multisweeper.playerstatus WHERE gameID=?)")) {
			$playerQuery->bind_param("i", $gameID);
			$playerQuery->execute();
			$playerQuery->bind_result($user);

			$playerRow = $doc->createElement('players');
			$playerRow = $newrow->appendChild($playerRow);

			while ($playerQuery->fetch()) {
				$playerInfo = $doc->createElement('player', $user);
				$playerInfo = $playerRow->appendChild($playerInfo);
			}

			$playerQuery->close();
		} else {
			error_log("Unable to prepare player gathering statement. " . $conn->errno . ": " . $conn->error);
			$error = $doc->createElement('error', "Internal error occurred, please try again later.");
			$error = $doc->appendChild($error);
		}
	} else {
		error_log("Unable to prepare map gathering statement. " . $conn->errno . ": " . $conn->error);
		$error = $doc->createElement('error', "Internal error occurred, please try again later.");
		$error = $doc->appendChild($error);
	}
	
	$r = $doc->saveXML();
	echo $r;
}
?>
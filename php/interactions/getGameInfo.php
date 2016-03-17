<?php

#This file returns the most recent game's information to the user.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/initializeMySQL.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/minefieldController.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/translateData.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');
	
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("getGameInfo.php - Connection failed: " . $conn->connect_error);
	}
	
	$doc = new DOMDocument('1.0');
	$doc->formatOutput = true;

	#Select all information about the game from the game's status columns in the MySQL database and parse it into XML form. 
	if ($query = $conn->prepare("SELECT map, visibility, tanks, height, width, gameID, status FROM multisweeper.games ORDER BY gameID DESC LIMIT 1")) {
		$query->execute();
		$query->bind_result($map, $vis, $tanks, $height, $width, $gameID, $status);
		$query->fetch();
		$query->close();

		$finalMap = translateMinefieldToMySQL(getMinefieldWithVisibility($gameID, translateMinefieldToPHP($map, $height, $width), translateMinefieldToPHP($vis, $height, $width)));

		$finalTanks = translateTanksToPHP($tanks);

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

		$nodeD = $doc->createElement('status', $status);
		$nodeD = $newrow->appendChild($nodeD);

		if ($tanks !== null) {
			$nodeE = $doc->createElement('tanks');
			$nodeE = $newrow->appendChild($nodeE);

			foreach ($finalTanks as $k => $v) {
				if (count($v) === 2) {
					$nodeT = $doc->createElement('tank', $v[0] . "," . $v[1]);
					$nodeT = $nodeE->appendChild($nodeT);
				}
			}
		}

		#Add all players in the game and their statuses to the XML.
		if ($playerQuery = $conn->prepare("SELECT p.username, s.status FROM multisweeper.players as p INNER JOIN multisweeper.playerstatus as s ON p.playerID=s.playerID WHERE s.gameID=?
")) {
			$playerQuery->bind_param("i", $gameID);
			$playerQuery->execute();
			$playerQuery->bind_result($user, $status);

			$playerRow = $doc->createElement('players');
			$playerRow = $newrow->appendChild($playerRow);

			while ($playerQuery->fetch()) {
				$playerInfo = $doc->createElement('player', $user);
				$playerInfo = $playerRow->appendChild($playerInfo);
				$playerInfo->setAttribute('status', $status);
			}

			$playerQuery->close();

			if ($gameTimeStmt = $conn->prepare("SELECT v FROM multisweeper.globalvars WHERE k='nextGameTime'")) {
				$gameTimeStmt->execute();
				$gameTimeStmt->bind_result($time);
				while ($gameTimeStmt->fetch()) {
					$gt = $doc->createElement('nextGameTime', "The next game will start at " . $time . ".");
					$gt = $newrow->appendChild($gt);
				}
			} else {
				error_log("getGameInfo.php - Unable to prepare next game time statement. " . $conn->errno . ": " . $conn->error);
			}
		} else {
			error_log("getGameInfo.php - Unable to prepare player gathering statement. " . $conn->errno . ": " . $conn->error);
			$error = $doc->createElement('error', "Internal error occurred, please try again later.");
			$error = $doc->appendChild($error);
		}
	} else {
		error_log("getGameInfo.php - Unable to prepare map gathering statement. " . $conn->errno . ": " . $conn->error);
		$error = $doc->createElement('error', "Internal error occurred, please try again later.");
		$error = $doc->appendChild($error);
	}
	
	$r = $doc->saveXML();
	error_log($r);
	echo $r;
}
?>
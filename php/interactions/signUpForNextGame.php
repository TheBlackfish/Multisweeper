<?php

#This file takes a player's log-in information and registers the player for the next game.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');

	$xml = simplexml_load_file('php://input');

	$result = new DOMDocument('1.0');
	$result->formatOutput = true;
	$resultBase = $result->createElement('register');
	$resultBase = $result->appendChild($resultBase);

	#Check if registration credentials are valid.
	if (($xml->username == null) or ($xml->password == null)) {
		error_log("signUpForNextGame.php - Sign up rejected");
		$error = $result->createElement('error', "Please fill out both fields and try again.");
		$error = $resultBase->appendChild($error);
	} else {
		$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
		if ($conn->connect_error) {
			die("signUpForNextGame.php - Connection failed: " . $conn->connect_error);
		}

		#Get the internal ID for the player being registered.
		if ($stmt = $conn->prepare("SELECT playerID FROM multisweeper.players WHERE username=? AND password=?")) {
			$playerID = null;

			$stmt->bind_param("ss", $xml->username, $xml->password);
			$stmt->execute();
			$stmt->bind_result($playerID);
			$stmt->fetch();
			$stmt->close();

			if ($playerID !== null) {

				#Validate that the player is currently not signed up.
				if ($checkStmt = $conn->prepare("SELECT playerID from multisweeper.upcomingsignup WHERE playerID=?")) {
					$doubleCheck = null;

					$checkStmt->bind_param("i", $playerID);
					$checkStmt->execute();
					$checkStmt->bind_result($doubleCheck);
					$checkStmt->fetch();
					$checkStmt->close();

					if ($doubleCheck !== null) {
						$error = $result->createElement('success', "You are already signed up for the next game. Please wait for deployment.");
						$error = $resultBase->appendChild($error);
					} else {

						#Register the player for the next game.
						if ($insertStmt = $conn->prepare("INSERT INTO multisweeper.upcomingsignup (playerID) VALUES (?)")) {
							$insertStmt->bind_param("i", $playerID);
							$insertStmt->execute();
							$insertStmt->close();

							$error = $result->createElement('success', "You are signed up for the next game. Please wait for deployment.");
							$error = $resultBase->appendChild($error);
						} else {
							error_log("signUpForNextGame.php - Unable to prepare insert statement.");
							$error = $result->createElement('error', "An internal error has occurred. Please try again later.");
							$error = $resultBase->appendChild($error);
						}
					}
				} else {
					error_log("signUpForNextGame.php - Unable to prepare check statement.");
					$error = $result->createElement('error', "An internal error has occurred. Please try again later.");
					$error = $resultBase->appendChild($error);
				}
			} else {
				$error = $result->createElement('error', "Could not find your profile. Please double-check your credentials.");
				$error = $resultBase->appendChild($error);
			}
		} else {
			error_log("signUpForNextGame.php - Unable to prepare validation statement.");
			$error = $result->createElement('error', "An internal error has occurred. Please try again later.");
			$error = $resultBase->appendChild($error);
		}
	}

	$r = $result->SaveXML();
	echo $r;
}

?>
<?php

require_once('../../../database.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');

	$xml = simplexml_load_file('php://input');

	$result = new DOMDocument('1.0');
	$result->formatOutput = true;
	$resultBase = $result->createElement('register');
	$resultBase = $result->appendChild($resultBase);

	if (($xml->username == null) or ($xml->password == null)) {
		error_log("Sign up rejected");
		$error = $result->createElement('error');
		$error = $resultBase->appendChild($error);
		$errorText = $result->createTextNode("Please fill out both fields and try again.");
		$errorText = $error->appendChild($errorText);
	} else {
		$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}

		if ($stmt = $conn->prepare("SELECT playerID FROM multisweeper.players WHERE username=? AND password=?")) {
			$playerID = null;

			$stmt->bind_param("ss", $xml->username, $xml->password);
			$stmt->execute();
			$stmt->bind_result($playerID);
			$stmt->fetch();
			$stmt->close();

			if ($playerID !== null) {
				if ($checkStmt = $conn->prepare("SELECT playerID from multisweeper.upcomingsignup WHERE playerID=?")) {
					$doubleCheck = null;

					$checkStmt->bind_param("i", $playerID);
					$checkStmt->execute();
					$checkStmt->bind_result($doubleCheck);
					$checkStmt->fetch();
					$checkStmt->close();

					if ($doubleCheck !== null) {
						$error = $result->createElement('success');
						$error = $resultBase->appendChild($error);
						$errorText = $result->createTextNode("You are already signed up for the next game. Please wait for deployment.");
						$errorText = $error->appendChild($errorText);
					} else {
						if ($insertStmt = $conn->prepare("INSERT INTO multisweeper.upcomingsignup (playerID) VALUES (?)")) {
							$insertStmt->bind_param("i", $playerID);
							$insertStmt->execute();
							$insertStmt->close();

							$error = $result->createElement('success');
							$error = $resultBase->appendChild($error);
							$errorText = $result->createTextNode("You are signed up for the next game. Please wait for deployment.");
							$errorText = $error->appendChild($errorText);
						} else {
							error_log("Unable to prepare insert statement.");
							$error = $result->createElement('error');
							$error = $resultBase->appendChild($error);
							$errorText = $result->createTextNode("An internal error has occurred. Please try again later.");
							$errorText = $error->appendChild($errorText);
						}
					}
				} else {
					error_log("Unable to prepare check statement.");
					$error = $result->createElement('error');
					$error = $resultBase->appendChild($error);
					$errorText = $result->createTextNode("An internal error has occurred. Please try again later.");
					$errorText = $error->appendChild($errorText);
				}
			} else {
				$error = $result->createElement('error');
				$error = $resultBase->appendChild($error);
				$errorText = $result->createTextNode("Could not find your profile. Please double-check your credentials.");
				$errorText = $error->appendChild($errorText);
			}
		} else {
			error_log("Unable to prepare validation statement.");
			$error = $result->createElement('error');
			$error = $resultBase->appendChild($error);
			$errorText = $result->createTextNode("An internal error has occurred. Please try again later.");
			$errorText = $error->appendChild($errorText);
		}
	}

	$r = $result->SaveXML();
	echo $r;
}

?>
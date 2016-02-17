<?php

require_once('../../../database.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');

	$xml = simplexml_load_file('php://input');

	$result = new DOMDocument('1.0');
	$result->formatOutput = true;
	$resultBase = $result->createElement('login');
	$resultBase = $result->appendChild($resultBase);

	if (($xml->username == null) or ($xml->password == null)) {
		error_log("Registration rejected");
		$error = $result->createElement('error');
		$error = $resultBase->appendChild($error);
		$errorText = $result->createTextNode("Please fill out both fields and try again.");
		$errorText = $error->appendChild($errorText);
	} else {
		$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}

		//Check if username already taken
		if ($checkStmt = $conn->prepare("SELECT COUNT(*) FROM multisweeper.players WHERE username=?")) {
			$checkStmt->bind_param("s", $xml->username);
			$checkStmt->execute();
			$checkStmt->bind_result($count);
			$checkStmt->close();

			if ($count == 0) {
				//Register player
				if ($registerStmt = $conn->prepare("INSERT INTO multisweeper.players (username, password) VALUES (?,?)")) {
					$registerStmt->bind_param("ss", $xml->username, $xml->password);
					$registerStmt->execute();

					if ($registerStmt->affected_rows > 0) {
						$error = $result->createElement('success');
						$error = $resultBase->appendChild($error);
						$errorText = $result->createTextNode("Successfully registered!");
						$errorText = $error->appendChild($errorText);
					} else {
						error_log("Unable to register player.");
						$error = $result->createElement('error');
						$error = $resultBase->appendChild($error);
						$errorText = $result->createTextNode("An internal error has occurred. Please try again later.");
						$errorText = $error->appendChild($errorText);
					}
				}
			} else {
				$error = $result->createElement('error');
				$error = $resultBase->appendChild($error);
				$errorText = $result->createTextNode("Username already taken, try another username.");
				$errorText = $error->appendChild($errorText);
			}
		} else {
			error_log("Unable to prepare statement for checking registration.");
			$error = $result->createElement('error');
			$error = $resultBase->appendChild($error);
			$errorText = $result->createTextNode("An internal error has occurred. Please try again later.");
			$errorText = $error->appendChild($errorText);
		}
	}

	$r = $result->SaveXML();
	error_log($r);
	echo $r;
}

?>
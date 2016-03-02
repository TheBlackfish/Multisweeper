<?php

#This file takes the registration information for a new player passed to it and attempts to create that player in the MySQL database.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');

	$xml = simplexml_load_file('php://input');

	$result = new DOMDocument('1.0');
	$result->formatOutput = true;
	$resultBase = $result->createElement('login');
	$resultBase = $result->appendChild($resultBase);

	#Check if registration credentials are valid.
	if (($xml->username == null) or ($xml->password == null)) {
		error_log("Registration rejected");
		$error = $result->createElement('error', "Please fill out both fields and try again.");
		$error = $resultBase->appendChild($error);
	} else {

		#Clean up registration credentials.
		$tempUsername = preg_replace("/[^A-Za-z0-9]/", '', $xml->username);
		$tempPassword = preg_replace("/[^A-Za-z0-9]/", '', $xml->password);

		#Validate that username and password are legal.
		if (strlen($xml->username) == 0) {
			$error = $result->createElement('error', "Username cannot be blank!");
			$error = $resultBase->appendChild($error);
		} else if (strlen($xml->password) == 0) {
			$error = $result->createElement('error', "Username cannot be blank!");
			$error = $resultBase->appendChild($error);
		} else if ($tempUsername !== $xml->username) {
			$error = $result->createElement('error', "Username contains illegal characters, please only use A-Z and 0-9.");
			$error = $resultBase->appendChild($error);
		} else if ($tempPassword !== $xml->password) {
			$error = $result->createElement('error', "Password contains illegal characters, please only use A-Z and 0-9.");
			$error = $resultBase->appendChild($error);
		} else {
			$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
			if ($conn->connect_error) {
				die("Connection failed: " . $conn->connect_error);
			}

			#Check if username already taken
			if ($checkStmt = $conn->prepare("SELECT COUNT(*) FROM multisweeper.players WHERE username=?")) {
				$checkStmt->bind_param("s", $xml->username);
				$checkStmt->execute();
				$checkStmt->bind_result($count);
				$checkStmt->close();

				if ($count == 0) {
					
					#Register the player in the MySQL database.
					if ($registerStmt = $conn->prepare("INSERT INTO multisweeper.players (username, password) VALUES (?,?)")) {
						$registerStmt->bind_param("ss", $xml->username, $xml->password);
						$registerStmt->execute();

						if ($registerStmt->affected_rows > 0) {
							$error = $result->createElement('success', "Successfully registered!");
							$error = $resultBase->appendChild($error);
						} else {
							error_log("Unable to register player.");
							$error = $result->createElement('error', "An internal error has occurred. Please try again later.");
							$error = $resultBase->appendChild($error);
						}
					}
				} else {
					$error = $result->createElement('error', "Username already taken, try another username.");
					$error = $resultBase->appendChild($error);
				}
			} else {
				error_log("Unable to prepare statement for checking registration.");
				$error = $result->createElement('error', "An internal error has occurred. Please try again later.");
				$error = $resultBase->appendChild($error);
			}
		}
	}

	$r = $result->SaveXML();
	echo $r;
}

?>
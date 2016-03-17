<?php

#This file checks the log-in information of a player and returns an XML form explaining if it was successful or not.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');

	$xml = simplexml_load_file('php://input');

	$result = new DOMDocument('1.0');
	$result->formatOutput = true;
	$resultBase = $result->createElement('login');
	$resultBase = $result->appendChild($resultBase);

	#Check if log-in credentials are valid.
	if (($xml->username == null) or ($xml->password == null)) {
		error_log("loginPlayer.php - Login rejected");
		$error = $result->createElement('error', "Please fill out both fields and try again.");
		$error = $resultBase->appendChild($error);
	} else {
		$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
		if ($conn->connect_error) {
			die("loginPlayer.php - Connection failed: " . $conn->connect_error);
		}

		#Check if user exists.
		if ($stmt = $conn->prepare("SELECT playerID FROM multisweeper.players WHERE username=? AND password=?")) {
			$output = null;

			$stmt->bind_param("ss", $xml->username, $xml->password);
			$stmt->execute();
			$stmt->bind_result($id);
			while ($stmt->fetch()) {
				$output = $id;
			}
			$stmt->close();

			if ($output != null) {
				$correct = $result->createElement('id', $output);
				$correct = $resultBase->appendChild($correct);

				$name = $result->createElement('username', $xml->username);
				$name = $resultBase->appendChild($name);
			} else {
				#Find out why login was rejected.
				if ($verify = $conn->prepare("SELECT COUNT(*) FROM multisweeper.players where username=?")) {
					$verify->bind_param("s", $xml->username);
					$verify->execute();
					$verify->bind_result($count);
					while ($verify->fetch()) {
						if ($count > 0) {
							$error = $result->createElement('error', "Incorrect password.");
							$error = $resultBase->appendChild($error);
						} else {
							$error = $result->createElement('error', "That username does not exist. That means it's available to register!");
							$error = $resultBase->appendChild($error);
						}
					}
				}
			}
		} else {
			error_log("loginPlayer.php - Unable to prepare statement for logging in.");
			$error = $result->createElement('error', "An internal error has occurred. Please try again later.");
			$error = $resultBase->appendChild($error);
		}
	}

	$r = $result->SaveXML();
	echo $r;
}

?>
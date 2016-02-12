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
		error_log("Login rejected");
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
			$output = null;

			$stmt->bind_param("ss", $xml->username, $xml->password);
			$stmt->execute();
			$stmt->bind_result($id);
			while ($stmt->fetch()) {
				$output = $id;
			}

			if ($output != null) {
				$correct = $result->createElement('id');
				$correct = $resultBase->appendChild($correct);
				$correctText = $result->createTextNode($output);
				$correctText = $correct->appendChild($correctText);

				$name = $result->createElement('username');
				$name = $resultBase->appendChild($name);
				$nameText = $result->createTextNode($xml->username);
				$nameText = $correct->appendChild($nameText);
			} else {
				if ($verify = $conn->prepare("SELECT COUNT(*) FROM multisweeper.players where username=?")) {
					$verify->bind_param("s", $xml->username);
					$verify->execute();
					$verify->bind_result($count);
					while ($verify->fetch()) {
						if ($count > 0) {
							$error = $result->createElement('error');
							$error = $resultBase->appendChild($error);
							$errorText = $result->createTextNode("Incorrect password.");
							$errorText = $error->appendChild($errorText);
						} else {
							$error = $result->createElement('error');
							$error = $resultBase->appendChild($error);
							$errorText = $result->createTextNode("That username does not exist. That means it's available to register!");
							$errorText = $error->appendChild($errorText);
						}
					}
				}
			}
		} else {
			error_log("Unable to prepare statement for logging in.");
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
<?php

require_once('../../../database.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');

	$xml = simplexml_load_file('php://input');

	if (($xml->username == null) or ($xml->password == null)) {
		error_log("Login rejected");
		//Spit out incomplete error
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
				//Echo back the valid XML response.
			} else {
				//Verify user exists.
			}
		}
	}
}

?>
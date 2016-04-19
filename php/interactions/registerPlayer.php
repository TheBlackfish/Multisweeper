<?php

#This file takes the registration information for a new player passed to it and attempts to create that player in the MySQL database.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/security.php');

function registerPlayer($xml) {
	global $sqlhost, $sqlusername, $sqlpassword;

	#Check if registration credentials are valid.
	if (($xml->username == null) or ($xml->password == null)) {
		error_log("registerPlayer.php - Registration rejected");
		return false;
	} else {
		#Clean up registration credentials.
		$tempUsername = preg_replace("/[^A-Za-z0-9]/", '', $xml->username);
		$tempPassword = preg_replace("/[^A-Za-z0-9]/", '', $xml->password);

		#Validate that username and password are legal.
		if (strlen($xml->username) == 0) {
			return false;
		} else if (strlen($xml->password) == 0) {
			return false;
		} else if ($tempUsername !== (string) $xml->username) {
			return false;
		} else if ($tempPassword !== (string) $xml->password) {
			return false;
		} else {
			$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
			if ($conn->connect_error) {
				error_log("registerPlayer.php - Connection failed: " . $conn->connect_error);
				return false;
			}

			#Check if username already taken
			if ($checkStmt = $conn->prepare("SELECT COUNT(*) FROM multisweeper.players WHERE username=?")) {
				$checkStmt->bind_param("s", $xml->username);
				$checkStmt->execute();
				$checkStmt->bind_result($count);
				$checkStmt->close();

				if ($count == 0) {
					#Register the player in the MySQL database.
					if ($registerStmt = $conn->prepare("INSERT INTO multisweeper.players (username, password, salt) VALUES (?,?,?)")) {

						$salt = sec_getNewSalt();
						$saltedPW = sec_getHashedValue($xml->password, $salt);

						error_log("Generated salt = " . $salt);
						error_log("Generated hash = " . $saltedPW);

						$registerStmt->bind_param("sss", $xml->username, $saltedPW, $salt);
						$registerStmt->execute();

						if ($registerStmt->affected_rows > 0) {
							return true;
						} else {
							error_log("registerPlayer.php - Unable to register player.");
						}
					}
				}
			} else {
				error_log("registerPlayer.php - Unable to prepare statement for checking registration.");
			}
		}
	}

	return false;
}

?>
<?php

#This file takes an input containing chat message information and uploads it to the server.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

#submitGameChat($playerID, $xml)
#Takes the chat message described in the XML provided and uploads it to the database.
#@param playerID (int) The ID of the player submitting the chat message.
#@param xml (XML) The XML describing the chat message being submitted.
#@return The XML describing the success or failure of the chat submission.
function submitGameChat($playerID, $xml) {
	global $sqlhost, $sqlusername, $sqlpassword;

	$result = null;

	if ($playerID > -1) {
		if ($xml->msg !== null) {
			$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
			if ($conn->connect_error) {
				die("submitGameChat.php - Connection failed: " . $conn->connect_error);
			}

			if ($chatQuery = $conn->prepare("INSERT INTO multisweeper.chatmessages (playerID, message, time) VALUES (?, ?, NOW())")) {
				$chatQuery->bind_param("is", $playerID, $xml->msg);
				$chatQuery->execute();
				$chatQuery->close();

				$result = "<chat>Success</chat>";
			}
		}
	}

	if ($result === null) {
		$result = "<chat>Failure</chat>";
	}

	return $result;
}

?>
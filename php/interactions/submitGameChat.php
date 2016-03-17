<?php

#This file takes an input containing chat message information and uploads it to the server.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');

	$xml = simplexml_load_file('php://input');

	#Check that all needed information exists in the chat submission.
	if (($xml->userID != null) and ($xml->msg != null)) {
		$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
		if ($conn->connect_error) {
			die("submitGameChat.php - Connection failed: " . $conn->connect_error);
		}

		if ($chatQuery = $conn->prepare("INSERT INTO multisweeper.chatmessages (playerID, message, time) VALUES (?, ?, NOW())")) {
			$chatQuery->bind_param("is", $xml->userID, $xml->msg);
			$chatQuery->execute();
			$chatQuery->close();
		}
	} else {
		error_log("submitGameChat.php - Invalid chat submission: " . $xml);
	}

	ob_start();
	require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/interactions/getGameChat.php');
	$result = ob_get_clean();

	echo $result;
}

?>
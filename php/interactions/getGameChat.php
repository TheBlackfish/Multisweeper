<?php

#This file returns the most recent chat messages to the client.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');
	
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	
	$doc = new DOMDocument('1.0');
	$doc->formatOutput = true;
	$chatlog = $doc->createElement('chatlog');
	$chatlog = $doc->appendChild($chatlog);

	#Select all information about the game from the game's status columns in the MySQL database and parse it into XML form. 
	if ($query = $conn->prepare("SELECT b.username, a.message, a.forCurrentGame FROM multisweeper.chatmessages as a INNER JOIN multisweeper.players as b ON a.playerID=b.playerID ORDER BY a.time DESC LIMIT 50")) {
		$query->execute();
		$query->bind_result($username, $message, $isCurrent);
		while ($query->fetch()) {
			$chat = $doc->createElement('chat');
			$chat = $chatlog->appendChild($chat);
			$chat->setAttribute('current', $isCurrent);

			$chatUser = $doc->createElement('user', $username);
			$charUser = $chat->appendChild($chatUser);

			$chatMsg = $doc->createElement('msg', $message);
			$chatMsg = $chat->appendChild($chatMsg);
		}
	} else {
		error_log("Unable to prepare chat gathering statement. " . $conn->errno . ": " . $conn->error);
		$error = $doc->createElement('error', "Internal error occurred, please try again later.");
		$error = $doc->appendChild($error);
	}
	
	$r = $doc->saveXML();
	echo $r;
}
?>
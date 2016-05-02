<?php

#This file takes a player's log-in information and registers the player for the next game.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/databaseConstants.php');

#signUpForNextGame($playerID)
#Inserts the player specified into the next game.
#@param $playerID - The ID of the player to sign up.
#@return Whether or not the operation was successful.
function signUpForNextGame($playerID) {
	global $sqlhost, $sqlusername, $sqlpassword;
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		error_log("signUpForNextGame.php - Connection failed: " . $conn->connect_error);
		return false;
	}

	if ($insertStmt = $conn->prepare("INSERT IGNORE INTO multisweeper.upcomingsignup (playerID) VALUES (?)")) {
		$insertStmt->bind_param("i", $playerID);
		$insertStmt->execute();
		$insertStmt->close();
		return true;
	}

	return false;
}

?>
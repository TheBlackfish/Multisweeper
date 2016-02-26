<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/multisweeper/php/constants/localServerConstants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/multisweeper/php/constants/databaseConstants.php");

function createResolveActionsTask($gameID) {
	global $scriptsDirectory, $phpFilepath, $phpSchedulerLogPath;

	//Set up details
	$taskDetails = $phpFilepath . " -f " . $scriptsDirectory . "resolveActionsScript.php " . $gameID;

	//Set up exec time
	$exactTime = time() + 15 * 60;
	$execTime = date("H:i", $exactTime);

	$cmd = "schtasks.exe /CREATE /RU SYSTEM /SC ONCE /TN \"MultisweeperResolveActions-{$gameID}\" /TR \"{$taskDetails}\" /ST {$execTime} /F > \"{$phpSchedulerLogPath}\"";

	error_log($cmd);

	exec($cmd);
}

function deleteResolveActionsTask($gameID) {
	$cmd = "schtasks.exe /DELETE /TN \"MultisweeperResolveActions-{$gameID}\" /F";

	exec($cmd);
}

function createGameCreationTask() {
	global $scriptsDirectory, $phpFilepath, $phpSchedulerLogPath, $sqlhost,	$sqlusername, $sqlpassword;

	//Set up details
	$taskDetails = $phpFilepath . " -f " . $scriptsDirectory . "gameCreationScript.php";

	//Set up exec time
	$exactTime = time() + 30 * 60;
	$execTime = date("H:i", $exactTime);

	$cmd = "schtasks.exe /CREATE /RU SYSTEM /SC ONCE /TN \"MultisweeperCreateGame\" /TR \"{$taskDetails}\" /ST {$execTime} /F > \"{$phpSchedulerLogPath}\"";

	exec($cmd);

	//Add time to globalvars
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	if ($timeStmt = $conn->prepare("INSERT INTO multisweeper.globalvars (key, value) VALUES ('nextGameTime', ?)")) {
		$timeStmt->bind_param("s", $execTime);
		if ($timeStmt->execute()) {
			error_log("Successfully executed next game time statement.");
		} else {
			error_log("Unable to set next game time in database. " . $timeStmt->errno . ": " . $timeStmt->error);
		}
	}
}

?>
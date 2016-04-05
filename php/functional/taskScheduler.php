<?php

#This file contains various functions to help with scheduling tasks. At this time, this only supports Windows Task Scheduler.

require_once($_SERVER['DOCUMENT_ROOT'] . "/multisweeper/php/constants/localServerConstants.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/multisweeper/php/constants/databaseConstants.php");

#createResolveActionsTask($gameID)
#Sets up a scheduled task to resolve all actions 10 minutes into the future.
#@param $gameID (Integer) The game ID that this task is for.
function createResolveActionsTask($gameID) {
	global $scriptsDirectory, $phpFilepath, $phpSchedulerLogPath;

	deleteResolveActionsTask($gameID);

	$taskDetails = $phpFilepath . " -f " . $scriptsDirectory . "resolveActionsScript.php " . $gameID;
	$execTime = date("H:i", time() + 5 * 60);
	exec("schtasks.exe /CREATE /RU SYSTEM /SC ONCE /TN \"MultisweeperResolveActions-{$gameID}\" /TR \"{$taskDetails}\" /ST {$execTime} /F > \"{$phpSchedulerLogPath}\"");
}

#createResolveActionsTask($gameID)
#Deletes all scheduled action resolution tasks for the game ID provided.
#@param $gameID (Integer) The game ID that this task is for.
function deleteResolveActionsTask($gameID) {
	exec("schtasks.exe /DELETE /TN \"MultisweeperResolveActions-{$gameID}\" /F");
}

#createGameCreationTask()
#Sets up a scheduled task to create a new game.
function createGameCreationTask() {
	global $scriptsDirectory, $phpFilepath, $phpSchedulerLogPath, $sqlhost,	$sqlusername, $sqlpassword;

	$taskDetails = $phpFilepath . " -f " . $scriptsDirectory . "gameCreationScript.php";
	$exactTime = time() + 5 * 60;
	$execTime = date("H:i", $exactTime);
	exec("schtasks.exe /CREATE /RU SYSTEM /SC ONCE /TN \"MultisweeperCreateGame\" /TR \"{$taskDetails}\" /ST {$execTime} /F > \"{$phpSchedulerLogPath}\"");

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("taskScheduler.php - Connection failed: " . $conn->connect_error);
	}

	if ($timeStmt = $conn->prepare("INSERT INTO multisweeper.globalvars (k, v) VALUES ('nextGameTime', ?)")) {
		$timeStmt->bind_param("s", $execTime);
		if ($timeStmt->execute()) {
			#error_log("taskScheduler.php - Successfully executed next game time statement.");
		} else {
			error_log("taskScheduler.php - Unable to set next game time in database. " . $timeStmt->errno . ": " . $timeStmt->error);
		}
	} else {
		error_log("taskScheduler.php - Unable to prepare next game time statement. " . $conn->errno . ": " . $conn->error);
	}
}

?>
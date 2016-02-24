<?php

require_once("../constants/localServerConstants.php");

function createResolveActionsTask($gameID) {
	global $scriptsDirectory, $phpFilepath, $phpSchedulerLogPath;

	//Set up details
	$taskDetails = $phpFilepath . " -f " . $scriptsDirectory . "resolveActionsScript.php " . $gameID;

	//Set up exec time
	$exactTime = time() + 4 * 60 * 60;
	$execTime = date("H:i", $exactTime);

	$cmd = "schtasks.exe /CREATE /RU SYSTEM /SC ONCE /TN \"MultisweeperResolveActions-{$gameID}\" /TR \"{$taskDetails}\" /ST {$execTime} /F > \"{$phpSchedulerLogPath}\"";

	error_log($cmd);

	exec($cmd);
}

function deleteResolveActionsTask($gameID) {
	$cmd = "schtasks.exe /DELETE /TN \"MultisweeperResolveActions-{$gameID}\" /F";

	error_log($cmd);

	exec($cmd);
}

?>
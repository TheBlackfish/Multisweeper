<?php

#This file resolves all actions for the provided game after setting various global variables needed.

$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(dirname(dirname(__FILE__))));

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/resolveActions.php');

if (count($argv) < 2) {
	die("No ID supplied for auto-resolution, dying.");
} else {
	error_log("Found id=" . $argv[1]);
	resolveAllActions($argv[1]);
}

?>
<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/resolveActions.php');

if (count($argv) == 0) {
	die("No ID supplied for auto-resolution, dying.");
} else {
	resolveAllActions($argv[0]);
}

?>
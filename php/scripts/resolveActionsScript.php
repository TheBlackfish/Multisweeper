<?php

require_once("../functional/resolveActions.php");

if (count($argv) == 0) {
	die("No ID supplied for auto-resolution, dying.");
} else {
	resolveAllActions($argv[0]);
}

?>
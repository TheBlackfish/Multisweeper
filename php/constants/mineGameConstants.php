<?php

	#This file sets various constants for game logic, including the standard width, height, and number of mines.
	#In addition, this file also creates the array used for checking adjancencies.

	$minefieldWidth = 50;
	$minefieldHeight = 30;
	$startingMines = 200;

	$numTraps = 2;
	
	$adjacencies = array(
		array(0, -1),
		array(1, -1),
		array(1, 0),
		array(1, 1),
		array(0, 1),
		array(-1, 1),
		array(-1, 0),
		array(-1, -1)
	);

	$friendlyTankMoves = array(
		array(
			array(1, 0),
			array(1, 1),
			array(1, -1)
		),
		array(
			array(1, 0),
			array(1, -1),
			array(1, 1)
		)
	);

	$enemyTankMoves = array(
		array(
			array(-1, 0),
			array(-1, 1),
			array(-1, -1)
		),
		array(
			array(-1, 0),
			array(-1, -1),
			array(-1, 1)
		)
	);
?>
<?php

#This file creates a new game after setting various global variables needed.

$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(dirname(dirname(__FILE__))));

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/createNewGame.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');

createNewGame($minefieldWidth, $minefieldHeight, $startingMines);

?>
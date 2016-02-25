<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/createNewGame.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/constants/mineGameConstants.php');

createNewGame($minefieldWidth, $minefieldHeight, $startingMines);

?>
<?php

require_once(dirname(dirname(__FILE__) . '/multisweeper/php/functional/createNewGame.php');
require_once(dirname(dirname(__FILE__) . '/multisweeper/php/constants/mineGameConstants.php');

createNewGame($minefieldWidth, $minefieldHeight, $startingMines);

?>
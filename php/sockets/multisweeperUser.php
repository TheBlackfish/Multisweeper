<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/phpwebsocket/users.php');

class MultisweeperUser extends WebSocketUser {

  public $playerID = -1;

}
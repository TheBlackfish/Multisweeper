<?php

#This file contains all functionality related to the direct users of the Multisweeper server.

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/phpwebsocket/users.php');

#MultisweeperUser
#The class that extends WebSocketUser and contains useful functionality for what we want to do.
class MultisweeperUser extends WebSocketUser {

  #playerID (int)
  #The ID of the player associated with this user.
  public $playerID = -1;

}
<?php

#This file contains all functionality related to the direct users of the sweepelite server.

require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/phpwebsocket/users.php');

#sweepeliteUser
#The class that extends WebSocketUser and contains useful functionality for what we want to do.
class sweepeliteUser extends WebSocketUser {

  #playerID (int)
  #The ID of the player associated with this user.
  public $playerID = -1;

}
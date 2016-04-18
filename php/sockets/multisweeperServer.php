#!/usr/bin/env php
<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(dirname(dirname(__FILE__))));

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/createNewGame.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/resolveActions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/interactions/getChatUpdateTime.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/interactions/getGameChat.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/interactions/getGameInfo.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/interactions/getGameUpdateTime.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/interactions/getLatestGameID.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/interactions/logInPlayer.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/interactions/queryResolutions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/interactions/registerPlayer.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/interactions/submitAction.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/interactions/submitGameChat.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/phpwebsocket/websockets.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/sockets/multisweeperUser.php');

class multisweeperServer extends WebSocketServer {
  //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.

  protected $userClass = 'MultisweeperUser';
  protected $gameID = null;
  
  protected $shouldBroadcastFullUpdate = true;
  protected $fullUpdateBacklog = array();

  protected $gameUpdateTimestamp = -1;
  protected $chatUpdateTimestamp = -1;
  protected $broadcastTimestamp = -1;
  protected $resolveActionsTimestamp = -1;

  protected $broadcastInterval = 3;
  protected $autoresolutionInterval = 120;
  protected $postActionResolutionInterval = 10;
  
  protected function process ($user, $message) {
    #Turn the message into XML to parse.
    $parsedMsg = simplexml_load_string($message);
    if ($parsedMsg !== false) {

      $response = "<response>";

      if (isset($user->playerID)) {
        #If there is a register node
        $registered = true;
        if (isset($parsedMsg->registration)) {
          #Call registerPlayer.php
          $registered = registerPlayer($parsedMsg->registration);
        }

        if ($registered) {
          #If there is a login node
          if (isset($parsedMsg->login)) {
            #Call logInPlayer.php to set player info for this user.
            $user->playerID = logInPlayer($parsedMsg->login);
          }
        }
      }
      
      if ($user->playerID !== -1) {

        $response .= "<login>1</login>";

        if (isset($this->gameID)) {
          #If there is an action node
          if (isset($parsedMsg->action)) {
            #Call submitAction.php
            $response .= submitAction($user->playerID, $this->gameID, $parsedMsg->action);
            #Check if we should set up an auto-resolution task or not.
            $timeToAdd = queryResolutions($this->gameID);
            if ($timeToAdd !== -1) {
              $this->resolveActionsTimestamp = time() + $timeToAdd;
            }
          }
        }

        #If there is a chat node
        if (isset($parsedMsg->chat)) {
          #Call submitGameChat.php
          $response .= submitGameChat($user->playerID, $parsedMsg->chat);
        }
      } else {
        $response .= "<loginError>You need to log in or register first.</loginError>";
      }
      
      #Return the compilation of successes/errors to user.
      $response .= "</response>";

      $this->send($user, $response);
    }
  }
  
  protected function connected ($user) {
    //Send a full update to the user.
    if ($this->gameID !== null) {
      $update = "<update>";
      $update .= getGameInfo($this->gameID, 0, true);
      $update .= getGameChat(null, true);
      $update .= "</update>";

      $this->send($user, $update);
    } else {
      array_push($this->fullUpdateBacklog, $user);
      $this->shouldBroadcastFullUpdate = true;
    }
  }

  protected function closed ($user) {
    // Do nothing. No additional clean-up necessary, but this function must be inherited.
  }

  protected function tick() {
    // Override this for any process that should happen periodically.  Will happen at least once
    // per second, but possibly more often.
    #Because of that, we only run this every X seconds.
    if (count($this->users) > 0) {
      $currentTime = time();
      $diff = $currentTime - $this->broadcastTimestamp;

      if ($diff > $this->broadcastInterval) {
        if (($currentTime > $this->resolveActionsTimestamp) && ($this->gameID !== null)) {
          #Resolve actions instead of broadcasting.
          resolveAllActions($this->gameID);
          $this->resolveActionsTimestamp = time() + $this->autoresolutionInterval;
        } else {
          if (($this->shouldBroadcastFullUpdate) && (count($this->fullUpdateBacklog) > 0)) {
            #Broadcast full updates to anyone who is in the backlog of updates.
            $update = "<update>";
            $update .= getGameInfo($this->gameID, 0, true);
            $update .= getGameChat(0, true);
            $update .= "</update>";

            foreach ($this->fullUpdateBacklog as $user) {
              $this->send($user, $update);
            }

            $this->fullUpdateBacklog = array();
            $this->shouldBroadcastFullUpdate = false;
          } else {
            #Broadcast partial updates if necessary.
            $update = "<update>";
            $shouldUpdate = false;

            #If gameID is null
            if ($this->gameID === null) {
              #Create a new game since we do not have one currently, then add all players to the full update backlog.
              $newID = createNewDefaultGame();
              if ($newID !== false) {
                $this->gameID = $newID;
                $this->gameUpdateTimestamp = getGameUpdateTime($newID);
                $this->shouldBroadcastFullUpdate = true;
                $this->resolveActionsTimestamp = time() + $this->autoresolutionInterval;
                foreach($this->users as $user) {
                  array_push($this->fullUpdateBacklog, $user);
                }
              }
            } else {
              #Set our ID to the latest game
              $this->gameID = getLatestGameID();
              $shouldUpdate = false;

              if ($this->gameID !== null) {
                #Get update time for game.
                $updated = getGameUpdateTime($this->gameID);
                #If update time is newer
                if ($updated > $this->gameUpdateTimestamp) {
                  #Update our stuff accordingly.
                  $update .= getGameInfo($this->gameID, $this->gameUpdateTimestamp, false);
                  $this->gameUpdateTimestamp = $updated;
                  $shouldUpdate = true;
                }
              }
              
              #Check for chat updates.
              $chatUpdated = getChatUpdateTime();
              if ($chatUpdated > $this->chatUpdateTimestamp) {
                $update .= getGameChat($this->chatUpdateTimestamp, false);
                $this->chatUpdateTimestamp = $chatUpdated;
                $shouldUpdate = true;
              }

              $update .= "</update>";

              #If there have been updates
              if ($shouldUpdate && (count($this->users) > 0)) {
                error_log($update);
                #For each user
                foreach ($this->users as $user) {
                  $this->send($user, $update);
                } 
              }
            }
          }
        }
        $this->broadcastTimestamp = time();
      }
    }
  }
}

$echo = new multisweeperServer("0.0.0.0","13002");

try {
  $echo->run();
}
catch (Exception $e) {
  $echo->stdout($e->getMessage());
  error_log($e->getMessage());
}

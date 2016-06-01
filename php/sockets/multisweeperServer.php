#!/usr/bin/env php
<?php

#This file contains the functionality for the multisweeperServer and should be called from the command line in order to start an instance of the server.

$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(dirname(dirname(__FILE__))));

require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/createNewGame.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/initializeMySQL.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/multisweeper/php/functional/playerController.php');
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

#multisweeperServer
#This class handles all Multisweeper logic, including handling incoming connections (implemented by WebSocketServer) and game logic.
class multisweeperServer extends WebSocketServer {
  //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.

  public $shouldDebug = true;

  #userClass (String)
  #The string denoting what class the user object should be.
  protected $userClass = 'MultisweeperUser';

  #gameID (int)
  #The int denoting the ID of the current game being played.
  protected $gameID = null;
  
  #shouldBroadcastFullUpdate (bool)
  #The control variable denoting if the server should ignore timestamps when updating.
  protected $shouldBroadcastFullUpdate = true;

  #fullUpdateBacklog (array)
  #The array containing all users who need full updates.
  protected $fullUpdateBacklog = array();

  #gameUpdateTimestamp (int)
  #The Unix timestamp of when the last known updates to games happen. This is used to compare against what the games think their most recent update was so as to know when to broadcast changes to all players.  
  protected $gameUpdateTimestamp = 0;

  #gameCreationTimestamp (int)
  #The Unix timestamp of when to create a new game.
  protected $gameCreationTimestamp = -1;

  #chatUpdateTimestamp (int)
  #The Unix timestamp of when the last known updates to the chat happened. This is used to compare against what the chat thinks their most recent update was so as to know when to broadcast changes to all players. 
  protected $chatUpdateTimestamp = 0;

  #broadcastTimestamp (int)
  #The Unix timestamp of when the last broadcast occurred.   
  protected $broadcastTimestamp = 0;

  #resolveActionsTimestamp (int)
  #The Unix timestamp of when the next action resolution should occur.
  protected $resolveActionsTimestamp = 0;

  #broadcastInterval (int)
  #The amount of seconds between broadcasts.
  protected $broadcastInterval = 1;

  #autoresolutionInterval (int)
  #The default amount of time between action resolutions.
  protected $autoresolutionInterval = 120;

  protected $gameCreationInterval = 120;
  
  #process($user, $message)
  #Takes an XML from the parameters and parses out what to do with that XML. There are certain required nodes that lead to different functionalities:
    #login
      #If the user the message came from does not have a player ID associated with it, this will attempt to log in that player and set the player ID appropriately.
    #registration
      #If present, the server will attempt to create a new player based on the credentials provided before logging them in.
    #action
      #If present and the user is logged in, the server will attempt to submit the actions described from the XML.
    #chat
      #If present and the user is logged in, the server will add the chat message described to the database.
  #@param user (MultisweeperUser) The user submitting the message to the server.
  #@param message (XML) The XML describing the 
  protected function process ($user, $message) {
    #Turn the message into XML to parse.
    $this->debugLog("Server Process - Processing this: " . $message);
    $parsedMsg = simplexml_load_string($message);
    if ($parsedMsg !== false) {

      $response = "<response>";

      if (isset($user->playerID) && ($user->playerID === -1)) {
        $this->debugLog("Server Process - Attempting to log player in");
        #If there is a register node
        $registered = true;
        if (isset($parsedMsg->registration)) {
          #Call registerPlayer.php
          $registered = registerPlayer($parsedMsg->registration);
        }

        if ($registered) {
          #If there is a login node
          if (isset($parsedMsg->login)) {
            $this->debugLog("Server Process - Logging player in");
            #Call logInPlayer.php to set player info for this user.
            $user->playerID = logInPlayer($parsedMsg->login);

            #Count how many users are logged into this playerID.
            $count = 0;
            foreach ($this->users as $compUser) {
              if ($compUser->playerID === $user->playerID) {
                $count++;
              }
            }

            if ($count > 1) {
              $user->playerID = -1;
              $response .= "<loginError>That player is already logged in.</loginError>";
            }
          }
        }
      }
      
      if ($user->playerID !== -1) {
        $this->debugLog("Server Process - Player is logged in");
        $this->debugLog("Server Processing Diagnostics");
        $this->debugLog("this->gameCreationTimestamp=".$this->gameCreationTimestamp);
        $this->debugLog("this->gameID=".$this->gameID);
        $this->debugLog("XML has action = ".isset($parsedMsg->action));
        $this->debugLog("XML has chat = ".isset($parsedMsg->chat));


        $response .= "<login>1</login>";

        if ((isset($this->gameID)) && ($this->gameCreationTimestamp === -1)) {
          #If there is an action node
          if (isset($parsedMsg->action)) {
            $this->debugLog("Server Process - Adding found action to queue");
            #Call submitAction.php
            $response .= submitAction($user->playerID, $this->gameID, $parsedMsg->action);
            $this->debugLog("Server Process - Action addition response " . $response);
            #Check if we should set up an auto-resolution task or not.
            $timeToAdd = queryResolutions($this->gameID);
            if ($timeToAdd !== -1) {
              $this->resolveActionsTimestamp = time() + $timeToAdd;
            }
          }
        }

        #If there is a chat node
        if (isset($parsedMsg->chat)) {
          $this->debugLog("Server Process - Adding found chat message to chat");
          #Call submitGameChat.php
          $response .= submitGameChat($user->playerID, $parsedMsg->chat);
        }
      } else {
        $response .= "<loginError>You need to log in or register first.</loginError>";
      }
      
      #Return the compilation of successes/errors to user.
      $response .= "</response>";

      $this->send($user, $response);
    } else {
      $this->debugLog("Server Process - Invalid XML sent to server");
    }
  }
  
  #connected($user)
  #Pushes the user into the full update backlog for later updates.
  #@param user (MultisweeperUser) The user connecting to the server.
  protected function connected ($user) {
    //Send a full update to the user.
    array_push($this->fullUpdateBacklog, $user);
    $this->shouldBroadcastFullUpdate = true;
    $this->debugLog("Adding user to full update backlog");
  }

  #closed($user)
  #Forces the player to go into AFK.
  #@param user (MultisweeperUser) The user disconnecting from the server.
  protected function closed ($user) {
    if ($this->gameID !== null) {
      if ($user->playerID !== -1) {
        forcePlayerAFK($this->gameID, $user->playerID);
      }
    }
  }

  #tick()
  #Handles all game logic related to maintaining the multisweeper game and broadcasting to all users. Every tick it compares the current time to the last broadcast timestamp and if the difference is greater than the set threshold, the server will then do one of the following actions based on the current situation:
    #If the game should resolve its actions based on time going past the resolution threshold, the server will resolve all actions for its current game.
    #Or else if there are players in the backlog who require a full update on the current game, the server will compile that information and broadcast it to the appropriate players.
    #Or else if the previous game is null or complete and after the alotted time, we create a new game and push all users to the full update backlog.
    #Or else if there have been updates to the current game or chat since the last update to either, we broadcast a partial update to all users.
  protected function tick() {
    // Override this for any process that should happen periodically.  Will happen at least once
    // per second, but possibly more often.
    #Because of that, we only run this every X seconds.
    if (count($this->users) > 0) {
      $currentTime = time();
      $diff = $currentTime - $this->broadcastTimestamp;

      #Broadcast to all users our loading status.
      $loadingIcon = 0;
      if (($this->resolveActionsTimestamp - $currentTime) <= 5) {
        $loadingIcon = 1;
      }
      $statusMsg = "<loading>" . $loadingIcon . "</loading>";
      foreach ($this->users as $user) {
        if ($user->handshake) {
          $this->send($user, $statusMsg);
        }
      }

      if ($diff > $this->broadcastInterval) {
        #Diagnostics
        $this->debugLog("Diagnosing tick");
        $this->debugLog("currentTime=".$currentTime);
        $this->debugLog("this->chatUpdateTimestamp=".$this->chatUpdateTimestamp);
        $this->debugLog("this->fullUpdateBacklog=".count($this->fullUpdateBacklog)." users");
        $this->debugLog("this->gameCreationTimestamp=".$this->gameCreationTimestamp);
        $this->debugLog("this->gameID=".$this->gameID);
        $this->debugLog("this->gameUpdateTimestamp=".$this->gameUpdateTimestamp);
        $this->debugLog("this->resolveActionsTimestamp=".$this->resolveActionsTimestamp);
        $this->debugLog("this->shouldBroadcastFullUpdate=".$this->shouldBroadcastFullUpdate);

        if (($currentTime > $this->resolveActionsTimestamp) && ($this->gameID !== null) && ($this->resolveActionsTimestamp !== -1)) {
          $this->debugLog("Server Tick - Resolving actions");
          #Resolve actions instead of broadcasting.
          if (resolveAllActions($this->gameID)) {
            $this->resolveActionsTimestamp = time() + $this->autoresolutionInterval;
          } else {
            $this->gameCreationTimestamp = time() + $this->gameCreationInterval;
            $this->resolveActionsTimestamp = -1;
          }
        } else {
          if (($this->shouldBroadcastFullUpdate) && (count($this->fullUpdateBacklog) > 0)) {
            $this->debugLog("Server Tick - Broadcasting full game to everyone in backlog");
            #Broadcast full updates to anyone who is in the backlog of updates.
            $update = "<update>";
            $update .= getGameInfo($this->gameID, 0, true);
            $update .= getGameChat(0, true);
            $update .= "</update>";
            $this->debugLog($update);
            $tempBackup = array();

            foreach ($this->fullUpdateBacklog as $user) {
              if ($user->handshake) {
                $this->send($user, $update);
              } else {
                array_push($tempBackup, $user);
              }
            }

            $this->fullUpdateBacklog = $tempBackup;
            if (count($this->fullUpdateBacklog) === 0) {
              $this->shouldBroadcastFullUpdate = false;
            }
          } else {
            $this->debugLog("Server Tick - Partial updates and other things");
            #Broadcast partial updates if necessary.
            $update = "<update>";
            $shouldUpdate = false;

            if (($this->gameCreationTimestamp !== -1) && ($currentTime > $this->gameCreationTimestamp)) {
              $this->debugLog("Server Tick - We think we should create a new game");
              $this->gameID = null;
            }

            #If gameID is null
            if ($this->gameID === null) {
              $this->debugLog("Server Tick - Creating new game");
              #Create a new game since we do not have one currently, then add all players to the full update backlog.
              $this->createGame();
            } else {
              $this->debugLog("Server Tick - checking for partial updates");
              #Set our ID to the latest game
              $this->gameID = getLatestGameID();
              $shouldUpdate = false;

              if ($this->gameID !== null) {
                #Get update time for game.
                $updated = getGameUpdateTime($this->gameID);
                $this->debugLog("Server Tick - Game thinks it was last updated at " . $updated);
                #If update time is newer
                if ($updated > $this->gameUpdateTimestamp) {
                  $this->debugLog("Server Tick - Getting partial information from game");
                  #Update our stuff accordingly.
                  $update .= getGameInfo($this->gameID, $this->gameUpdateTimestamp, false);
                  $this->gameUpdateTimestamp = $updated;
                  $shouldUpdate = true;
                }
              }
              
              #Check for chat updates.
              $chatUpdated = getChatUpdateTime();
              $this->debugLog("Server Tick - Chat thinks it was last updated at " . $chatUpdated);
              if ($chatUpdated > $this->chatUpdateTimestamp) {
                $this->debugLog("Server Tick - Getting partial information from chat");
                $update .= getGameChat($this->chatUpdateTimestamp, false);
                $this->chatUpdateTimestamp = $chatUpdated;
                $shouldUpdate = true;
              }

              $update .= "</update>";

              #If there have been updates
              if ($shouldUpdate && (count($this->users) > 0)) {
                #For each user
                foreach ($this->users as $user) {
                  if ($user->handshake) {
                    $this->send($user, $update);
                  }
                } 
              }
            }
          }
        }
        $this->broadcastTimestamp = time();
        $this->debugLog("");
      }
    } else {
      if ($this->resolveActionsTimestamp !== -1) {
        $this->resolveActionsTimestamp = time() + $this->autoresolutionInterval;
      }
    }
  }

  #createGame()
  #Creates a new game and effectively resets the web server to start running that game.
  public function createGame() {
    if ($this->gameID === null) {
      $newID = createNewDefaultGame();
      if ($newID !== false) {
        $this->gameID = $newID;
        $this->gameUpdateTimestamp = getGameUpdateTime($newID);
        $this->shouldBroadcastFullUpdate = true;
        $this->resolveActionsTimestamp = time() + $this->autoresolutionInterval;
        $this->gameCreationTimestamp = -1;
        foreach($this->users as $user) {
          array_push($this->fullUpdateBacklog, $user);
        }
      }
    }
  }

  protected function debugLog($str) {
    if ($this->shouldDebug) {
      error_log($str);
    }
  }
}

#This code here runs the server.

$server = new multisweeperServer("0.0.0.0","13002");

foreach ($argv as $arg) {
  if ($arg === "-debug") {
    $server->shouldDebug = true;
  }
}

try {
  checkMySQL();
  $server->createGame();
  $server->run();
}
catch (Exception $e) {
  $server->stdout($e->getMessage());
  error_log($e->getMessage());
}

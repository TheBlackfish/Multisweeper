<?php

#This file returns the most recent game's information to the user.

require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/constants/databaseConstants.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/functional/initializeMySQL.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/functional/medalController.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/functional/minefieldController.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/functional/translateData.php');

#getGameInfo($gameID, $lastUpdated, $ignoreUpdateTime)
#Retrieves relevant information about the game specified by $gameID since the Unix timestamp provided. This information comes back as a formatted XML.
#@param $gameID (int) The ID of the game to retrieve information for.
#@param $lastUpdated (int) The Unix timestamp to compare against.
#@param $ignoreUpdateTime (bool) If true, the Unix timestamp will be set to 0.
#@return The formatted XML containing all relevant data for the requested game.
function getGameInfo($gameID, $lastUpdated = 0, $ignoreUpdateTime = false) {
	global $sqlhost, $sqlusername, $sqlpassword;

	if ($ignoreUpdateTime) {
		$lastUpdated = 0;
	}

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		error_log("getGameInfo.php - Connection failed: " . $conn->connect_error);
		return "";
	}

	if ($timeStmt = $conn->prepare("SELECT UNIX_TIMESTAMP(lastUpdated), fullUpdate FROM sweepelite.games WHERE gameID = ?")) {
		$updatedTime = null;
		$timeStmt->bind_param("i", $gameID);
		$timeStmt->execute();
		$timeStmt->bind_result($updated, $fullUpdate);
		while ($timeStmt->fetch()) {
			$updatedTime = $updated;
		}
		$timeStmt->close();

		if ($updatedTime !== null) {
			if ($updatedTime > $lastUpdated) {
				$ret = new SimpleXMLElement("<update/>");
				$ret->addAttribute("id", $gameID);

				if ($fullUpdate || $ignoreUpdateTime) {
					#Select all information about the game from the game's status columns in the MySQL database and parse it into XML form. 
					if ($query = $conn->prepare("SELECT map, visibility, friendlyTanks, enemyTanks, wrecks, traps, height, width, status FROM sweepelite.games WHERE gameID = ?")) {
						$query->bind_param("i", $gameID);
						$query->execute();
						$query->bind_result($map, $vis, $friendlyTanks, $enemyTanks, $wrecks, $traps, $height, $width, $status);
						$query->fetch();
						$query->close();

						$finalFriendlies = translateTanksToPHP($friendlyTanks);
						$finalEnemies = translateTanksToPHP($enemyTanks);
						$finalWrecks = translateTanksToPHP($wrecks);
						$finalTraps = translateTrapsToPHP($traps);

						$finalMap = translateMinefieldToMySQL(getMinefieldWithVisibility($gameID, translateMinefieldToPHP($map, $height, $width), translateMinefieldToPHP($vis, $height, $width), $finalWrecks));

						$ret->addChild('map', $finalMap);
						$ret->addChild('height', $height);
						$ret->addChild('width', $width);
						$ret->addChild('status', $status);

						if ($finalFriendlies !== null) {
							$ret->addChild('friendlyTanks');

							foreach ($finalFriendlies as $k => $v) {
								if (count($v) === 2) {
									$ret->friendlyTanks->addChild('tank', $v[0] . "," . $v[1]);
								}
							}
						}

						if ($finalEnemies !== null) {
							$ret->addChild('enemyTanks');

							foreach ($finalEnemies as $k => $v) {
								if (count($v) === 2) {
									$ret->enemyTanks->addChild('tank', $v[0] . "," . $v[1]);
								}
							}
						}

						if ($finalTraps !== null) {
							$ret->addChild('traps');

							foreach ($finalTraps as $k => $v) {
								if (count($v) === 3) {
									$ret->traps->addChild('trap', $v[0] . "," . $v[1] . "," . $v[2]);
								}
							}
						}
						
						if ($hsStmt = $conn->prepare("SELECT MAX(totalScore) FROM sweepelite.players")) {
							$hsStmt->execute();
							$hsStmt->bind_result($hs);
							$hsStmt->fetch();
							$hsStmt->close();

							#Add all players in the game and their statuses to the XML.
							if ($playerQuery = $conn->prepare("SELECT p.username, p.playerID, s.status, s.trapType, s.trapCooldown, s.digNumber, s.flagNumber, p.totalScore FROM sweepelite.players as p INNER JOIN sweepelite.playerstatus as s ON p.playerID=s.playerID WHERE s.gameID=?")) {
								$playerQuery->bind_param("i", $gameID);
								$playerQuery->execute();
								$playerQuery->bind_result($user, $currentID, $status, $trapType, $trapCooldown, $digNumber, $flagNumber, $totalScore);

								$ret->addChild('players');

								while ($playerQuery->fetch()) {
									$playerInfo = $ret->players->addChild('player', $user);
									$playerInfo->addAttribute('status', $status);
									$playerInfo->addAttribute('trapType', $trapType);
									$playerInfo->addAttribute('trapCooldown', $trapCooldown);

									$medals = calculateMedalAttributesForPlayer($digNumber, $flagNumber);
									$playerInfo->addAttribute('digMedal', $medals['digMedal']);
									$playerInfo->addAttribute('flagMedal', $medals['flagMedal']);

									$scoreRank = 5;
									//Calculate score rank
									if ($totalScore >= $hs) {
										$scoreRank = 6;
									} else if ($totalScore === 0) {
										$scoreRank = 0;
									} else if ($totalScore < ($hs * 0.2)) {
										$scoreRank = 1;
									} else if ($totalScore < ($hs * 0.4)) {
										$scoreRank = 2;
									} else if ($totalScore < ($hs * 0.6)) {
										$scoreRank = 3;
									} else if ($totalScore < ($hs * 0.8)) {
										$scoreRank = 4;
									}

									$playerInfo->addAttribute('scoreRank', $scoreRank);
								}

								$playerQuery->close();

								if ($gameTimeStmt = $conn->prepare("SELECT v FROM sweepelite.globalvars WHERE k='nextGameTime'")) {
									$gameTimeStmt->execute();
									$gameTimeStmt->bind_result($time);
									while ($gameTimeStmt->fetch()) {
										$ret->addChild('nextGameTime', $time);
									}
								} else {
									error_log("getGameInfo.php - Unable to prepare next game time statement. " . $conn->errno . ": " . $conn->error);
								}
							} else {
								error_log("getGameInfo.php - Unable to prepare player gathering statement. " . $conn->errno . ": " . $conn->error);
								$ret->addChild('error', "Internal error occurred, please try again later.");
							}
						} else {
							error_log("getGameInfo.php - Unable to prepare high score gathering statement. " . $conn->errno . ": " . $conn->error);
							$ret->addChild('error', "Internal error occurred, please try again later.");
						}						

						if (!$ignoreUpdateTime) {
							if ($updateQuery = $conn->prepare("UPDATE sweepelite.games SET fullUpdate=0 WHERE gameID=?")) {
								$updateQuery->bind_param("i", $gameID);
								$updateQuery->execute();
								$updateQuery->close();
							}
						}
					}
				} 

				#Add other player actions
				$otherPlayers = getPlayerActionsForGame($gameID);
				if (count($otherPlayers) !== 0) {
					$opNode = $ret->addChild('otherPlayers');

					foreach ($otherPlayers as $k => $v) {
						if (count($v) === 2) {
							$opNode->addChild('otherPlayer', $v[0] . "," . $v[1]);
						}
					}
				}

				$finalRet = str_replace('<?xml version="1.0"?>', "", $ret->asXML());
				return $finalRet;
			}
		}
	}

	return "";
}

?>
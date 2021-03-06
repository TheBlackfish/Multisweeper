<?php

#This file returns the most recent chat messages to the client.

require_once($_SERVER['DOCUMENT_ROOT'] . '/sweepelite/php/constants/databaseConstants.php');

#getGameChat($lastUpdateTime, $ignoreUpdateTime)
#Returns the XML containing all of the chat messages entered since the last update time parameter.
#@param $lastUpdateTime (int) The Unix timestamp of the last time we updated the chat.
#@param $ignoreUpdateTime (bool) If true, the Unix timestamp provided will be set to 0 to get all messages ever.
#@return The formatted XML containing all of the chat messages since the provided timestamp.
function getGameChat($lastUpdateTime = 0, $ignoreUpdateTime = false) {
	global $sqlhost, $sqlusername, $sqlpassword;

	$ret = "<chatlog>";

	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		error_log("getGameChat.php - Connection failed: " . $conn->connect_error);
		return "";
	}

	if ($ignoreUpdateTime) {
		$lastUpdateTime = 0;
	}

	$compDate = new DateTime();
	$compDate->setTimestamp($lastUpdateTime);
	$compDate = $compDate->format('Y-m-d H:i:s');

	if ($query = $conn->prepare("SELECT b.username, a.message, a.forCurrentGame FROM sweepelite.chatmessages as a INNER JOIN sweepelite.players as b ON a.playerID = b.playerID WHERE a.time > ? ORDER BY a.time DESC LIMIT 50")) {
		$query->bind_param("s", $compDate);
		$query->execute();
		$query->bind_result($username, $message, $isCurrent);
		while ($query->fetch()) {
			$temp = "<chat current='" . $isCurrent . "'>";
			$temp .= "<user>" . $username . "</user>";
			$temp .= "<msg>" . $message . "</msg>";
			$temp .= "</chat>";
			$ret .= $temp;
		}
		$query->close();
	}

	if (strlen($ret) > strlen("<chatlog>")) {
		$ret .= "</chatlog>";
		return $ret;
	}

	return "";
}

?>
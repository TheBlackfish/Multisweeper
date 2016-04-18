function processMinefieldXML(xml) {
	var ret = [];
	ret["map"] = null;

	var height = -1;
	var width = -1;

	if (xml.getElementsByTagName("height").length > 0) {
		height = parseInt(xml.getElementsByTagName("height")[0].childNodes[0].nodeValue);
	}

	if (xml.getElementsByTagName("width").length > 0) {
		width = parseInt(xml.getElementsByTagName("width")[0].childNodes[0].nodeValue);
	}

	if (xml.getElementsByTagName("map").length > 0) {
		var map = processMinefieldMap(xml.getElementsByTagName("map")[0], height, width);
		if (map !== null) {
			ret["map"] = map;
			ret["height"] = height;
			ret["width"] = width;
		}
	}

	if (ret["map"] !== null) {
		if (xml.getElementsByTagName("status").length > 0) {
			ret["status"] = xml.getElementsByTagName("status")[0].childNodes[0].nodeValue
		}

		if (xml.getElementsByTagName("friendlyTanks").length > 0) {
			var friendlyTanks = processTanks(xml.getElementsByTagName("friendlyTanks")[0].getElementsByTagName("tank"));
			if (friendlyTanks !== null) {
				ret["friendlyTanks"] = friendlyTanks;
			}
		}

		if (xml.getElementsByTagName("enemyTanks").length > 0) {
			var enemyTanks = processTanks(xml.getElementsByTagName("enemyTanks")[0].getElementsByTagName("tank"));
			if (enemyTanks !== null) {
				ret["enemyTanks"] = enemyTanks;
			}
		}

		if (xml.getElementsByTagName("traps").length > 0) {
			var traps = processTraps(xml.getElementsByTagName("traps")[0].getElementsByTagName("trap"));
			if (traps !== null) {
				ret["traps"] = traps;
			}
		}

		if (xml.getElementsByTagName("players").length > 0) {
			var players = processPlayers(xml.getElementsByTagName("players")[0]);
			if (players !== null) {
				ret["players"] = players;
			}
		}
	}

	if (xml.getElementsByTagName("otherPlayers").length > 0) {
		var op = processOtherPlayerActions(xml.getElementsByTagName("otherPlayers")[0].getElementsByTagName("otherPlayer"));
		if (op !== null) {
			ret["otherPlayers"] = op;
		}
	}

	return ret;
}

function processMinefieldMap(mapNode, height, width) {
	var input = mapNode.childNodes[0].nodeValue;
	if (input.length !== (width*height)) {
		console.log("Input is not of the correct size, aborting processMinefieldMap");
	} else {
		var result = new Array();
		while (input.length > 0) {
			var temp = input.substr(0, height);
			input = input.substr(height);
			temp = temp.split("");

			if (temp.length !== height) {
				console.log("Chunking input does not have correct height!");
			}

			for (var i = 0; i < temp.length; i++) {
				if (temp[i] === "U") {
					temp[i] = -1;
				} else if (temp[i] === "M") {
					temp[i] = -2;
				} else if (temp[i] === "F") {
					temp[i] = 9;
				} else if (temp[i] === "W") {
					temp[i] = 10;
				} else {
		 			temp[i] = parseInt(temp[i]);
		 		}
			}

			result.push(temp);
		}
		if (result.length !== width) {
			console.log("Chunking did not lead to correct width of field!");
		}
		return result;
	}
	return null;
}

function processOtherPlayerActions(otherPlayerNodes) {
	var result = new Array();

	for (var i = 0; i < otherPlayerNodes.length; i++) {
		var tempPlayer = otherPlayerNodes[i].childNodes[0].nodeValue.split(",");
		if (tempPlayer.length === 2) {
			for (var j = 0; j < tempPlayer.length; j++) {
				tempPlayer[j] = parseInt(tempPlayer[j]);
			}
			result.push(tempPlayer);
		}
	}

	return result;
}

function processPlayers(playerXML) {
	return playerXML;
}

function processTanks(tankNodes) {
	var result = new Array();

	for (var i = 0; i < tankNodes.length; i++) {
		var tempTank = tankNodes[i].childNodes[0].nodeValue.split(",");
		if (tempTank.length === 2) {
			for (var j = 0; j < tempTank.length; j++) {
				tempTank[j] = parseInt(tempTank[j]);
			}
			result.push(tempTank);
		}
	}

	return result;
}

function processTraps(trapNodes) {
	var result = new Array();

	for (var i = 0; i < trapNodes.length; i++) {
		var tempTrap = trapNodes[i].childNodes[0].nodeValue.split(",");
		if (tempTrap.length === 3) {
			for (var j = 0; j < tempTrap.length; j++) {
				tempTrap[j] = parseInt(tempTrap[j]);
			}
			result.push(tempTrap);
		}
	}

	return result;
}
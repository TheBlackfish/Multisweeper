/*
	DataController.js

	This script file holds various functions relating to client-server controls.
*/

//currentGameID [Integer]
//The ID of the game being shown to the player.
var currentGameID = null;

//handleDataWithPHP(data, phpFileName, responseFunction)
//Using the information provided, sends a POST request to the server to a .php file and resolves the response using the response function provided.
//@param data - The information to send to the server in XML form.
//@param phpFileName - The string that matches the file name of the PHP file to send the request to. This string must be the direct file name of the file, minus any directories or file extensions.
//@param responseFunction - The function to call in response to the information sent back from the server. The function should accept one parameter of the response event.
function handleDataWithPHP(data, phpFileName, responseFunction) {
	var xmlhttp = new XMLHttpRequest();
	if ("withCredentials" in xmlhttp) {
		xmlhttp.open("POST", "./php/interactions/" + phpFileName + ".php", true);	
	} else if (typeof XDomainRequest != "undefined") {
		xmlhttp = new XDomainRequest();
		xmlhttp.open("POST", "./php/interactions/" + phpFileName + ".php", true);	
	} else {
		xmlhttp = null;
		console.log("CORS not supported");	
	}
	xmlhttp.setRequestHeader('Content-Type', 'text/xml');
	xmlhttp.onreadystatechange = function(){
		if (xmlhttp.readyState===4 && xmlhttp.status===200) {
			responseFunction(xmlhttp.responseXML);
		}
	}
	if (data.length === 0) {
		xmlhttp.send();
	} else {
		xmlhttp.send(data);
	}
}

//getMinefieldData()
//Gets all of the information about the current game being played from the server.
function getMinefieldData() {
	var x = -1;
	if (getPlayerID() !== "") {
		x = parseInt(getPlayerID());
	}
	getMinefieldDataWithID(x);
}

//getMinefieldDataWithID(id)
//Gets all of the information about the current game being played from the server.
//@param id - The ID of the current player.
function getMinefieldDataWithID(id) {
	handleDataWithPHP("<xml><playerID>" + id + "</playerID></xml>", "getGameInfo", processMinefieldData);
}

//processMinefieldData(response)
//Takes the response from the server for getting game information and properly distributes it to various script functions.
//@param response - The response from the server for the submission in XML form.
function processMinefieldData(response) {
	var allInfo = response.getElementsByTagName("minefield")[0];
	
	currentGameID = allInfo.getElementsByTagName("id")[0].childNodes[0].nodeValue;

	var map = preprocessMinefieldMap(allInfo.getElementsByTagName("map")[0].childNodes[0].nodeValue);
	var h = parseInt(allInfo.getElementsByTagName("height")[0].childNodes[0].nodeValue);
	var w = parseInt(allInfo.getElementsByTagName("width")[0].childNodes[0].nodeValue);
	var ft = new Array();
	if (allInfo.getElementsByTagName("friendlyTanks").length > 0) {
		ft = preprocessTankCoordinates(allInfo.getElementsByTagName("friendlyTanks")[0]);
	}
	var et = new Array();
	if (allInfo.getElementsByTagName("enemyTanks").length > 0) {
		et = preprocessTankCoordinates(allInfo.getElementsByTagName("enemyTanks")[0]);
	}
	var tr = new Array();
	if (allInfo.getElementsByTagName("traps").length > 0) {
		tr = preprocessTrapCoordinates(allInfo.getElementsByTagName("traps")[0]);
	}
	var o = new Array();
	if (allInfo.getElementsByTagName("otherPlayers").length > 0) {
		o = preprocessOtherPlayerCoordinates(allInfo.getElementsByTagName("otherPlayers")[0]);
	}
	
	initMinefield(map, h, w, ft, et, tr, o);

	if (allInfo.getElementsByTagName("selfAction").length > 0) {
		var self = allInfo.getElementsByTagName("selfAction")[0];
		var coord = self.getElementsByTagName("coordinates")[0].childNodes[0].nodeValue.split(",");
		var actionType = self.getElementsByTagName("actionType")[0].childNodes[0].nodeValue;
		setSelectionCoordinates(parseInt(coord[0]), parseInt(coord[1]), parseInt(actionType));
	} else {
		setSelectionCoordinates(-1, -1, 0);
	}

	var players = allInfo.getElementsByTagName("players")[0];

	populatePlayerListTable(players);

	if (allInfo.getElementsByTagName("playerIsAlive").length > 0) {
		var canLayTraps = parseInt(allInfo.getElementsByTagName("canLayTraps")[0].childNodes[0].nodeValue);
		var isAlive = parseInt(allInfo.getElementsByTagName("playerIsAlive")[0].childNodes[0].nodeValue);
		setInteractionPolicy(isAlive, canLayTraps);
	}

	//var statusMsg = allInfo.getElementsByTagName("status")[0].childNodes[0].nodeValue;

	var gameTime = allInfo.getElementsByTagName("nextGameTime");

	if (gameTime.length > 0) {
		updateUpcomingGameTime(gameTime[0]);
	} else {
		updateUpcomingGameTime(null);
	}
}

//preprocessMinefieldMap(input)
//Takes the minefield data in string form from a server response and changes certain values into forms that the minefield.js file can understand.
//@param input - The minefield to clean up in string form.
//@return The array of values representing the minefield.
function preprocessMinefieldMap(input) {
	var result = input.split("");
	for (var i = 0; i < result.length; i++) {
		if (result[i] === "U") {
			result[i] = -1;
		} else if (result[i] === "M") {
			result[i] = -2;
		} else if (result[i] === "F") {
			result[i] = 9;
		} else if (result[i] === "W") {
			result[i] = 10;
		} else {
 			result[i] = parseInt(result[i]);
 		}
	}
	return result;
}

//preprocessOtherPlayerCoordinates(input)
//Takes the other player coordinates in string form and returns them in array form.
//@param input - The other player coordinates in string form
//@return The array of values representing other player coordinates.
function preprocessOtherPlayerCoordinates(input) {
	var result = new Array();

	var ops = input.getElementsByTagName('otherPlayer');
	for (var i = 0; i < ops.length; i++) {
		var tempPlayer = ops[i].childNodes[0].nodeValue.split(",");
		for (var j = 0; j < tempPlayer.length; j++) {
			tempPlayer[j] = parseInt(tempPlayer[j]);
		}
		result.push(tempPlayer);
	}

	return result;
}

//preprocessTankCoordinates(input)
//Takes the tank coordinates in string form and returns them in array form.
//@param input - The tank coordinates in string form
//@return The array of values representing tank coordinates.
function preprocessTankCoordinates(input) {
	var result = new Array();

	var tanks = input.getElementsByTagName('tank');
	for (var i = 0; i < tanks.length; i++) {
		var tempTank = tanks[i].childNodes[0].nodeValue.split(",");
		for (var j = 0; j < tempTank.length; j++) {
			tempTank[j] = parseInt(tempTank[j]);
		}
		result.push(tempTank);
	}

	return result;
}

function preprocessTrapCoordinates(input) {
	var result = new Array();

	var traps = input.getElementsByTagName('trap');
	for (var i = 0; i < traps.length; i++) {
		var tempTrap = traps[i].childNodes[0].nodeValue.split(",");
		for (var j = 0; j < tempTrap.length; j++) {
			tempTrap[j] = parseInt(tempTrap[j]);
		}
		result.push(tempTrap);
	}

	return result;
}

//getGameID()
//Returns the current game ID for the game being shown.
//@return The ID of the current game.
function getGameID() {
	return currentGameID;
}
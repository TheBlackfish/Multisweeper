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
	handleDataWithPHP("", "getGameInfo", processMinefieldData);
}

//processMinefieldData(response)
//Takes the response from the server for getting game information and properly distributes it to various script functions.
//@param response - The response from the server for the submission in XML form.
function processMinefieldData(response) {
	var allInfo = response.getElementsByTagName("minefield")[0];
	
	currentGameID = allInfo.getElementsByTagName("id")[0].childNodes[0].nodeValue;

	var map = preprocessMinefieldMap(allInfo.getElementsByTagName("map")[0].childNodes[0].nodeValue);
	var h = allInfo.getElementsByTagName("height")[0].childNodes[0].nodeValue;
	var w = allInfo.getElementsByTagName("width")[0].childNodes[0].nodeValue;
	var t = new Array();
	if (allInfo.getElementsByTagName("tanks").length > 0) {
		t = preprocessTankCoordinates(allInfo.getElementsByTagName("tanks")[0]);
	}
	
	initMinefield(map, h, w, t);

	var players = allInfo.getElementsByTagName("players")[0];

	populatePlayerListTable(players);

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
 		} else if (result[i] === "A") {
 			result[i] = 12;
 		}
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
		result.push(tanks[i].childNodes[0].nodeValue.split(","));
	}

	return result;
}

//getGameID()
//Returns the current game ID for the game being shown.
//@return The ID of the current game.
function getGameID() {
	return currentGameID;
}
/*
	PlayerController.js

	This file contains all functionality relating to player information, including logging in and tracking cookies.
*/

//currentPlayerID [Integer]
//The internal ID of the player currently logged in.
var currentPlayerID = null;

//currentUserName [String]
//The name of the currently logged-in player.
var currentUserName = null;

//checkCookie()
//Attempts to find any cookies for this page. If they exist and they contain user information, this function will attempt to log that player in on this computer.
function checkCookie() {
	var foundUsername = null;
	var foundPassword = null;

	var allCookieVals = document.cookie.split("; ");
	for (var i = 0; i < allCookieVals.length; i++) {
		var cur = allCookieVals[i];
		if (cur.lastIndexOf("username") > -1) {
			var curBreaks = cur.split("=");
			foundUsername = curBreaks[1];
		} else if (cur.lastIndexOf("password") > -1) {
			var curBreaks = cur.split("=");
			foundPassword = curBreaks[1];
		}
	}

	if ((foundUsername !== null) && (foundPassword !== null)) {
		document.getElementById("loginUsername").value = foundUsername;
		document.getElementById("loginPassword").value = foundPassword;
		attemptLogIn();
	}
}



//attemptLogIn()
//Gets the entered username and password, then makes a POST request to the server for authentication.
function attemptLogIn() {
	var inputUserName = document.getElementById("loginUsername").value;
	var inputPassword = document.getElementById("loginPassword").value;

	var loginXML = "<login><username>" + inputUserName + "</username><password>" + inputPassword + "</password></login>";

	handleDataWithPHP(loginXML, 'logInPlayer', handleLogIn);
}

//handleLogIn(response)
//Handles player information after logging in.
//@param response - The XML information from the server after attempting to authenticate with the server.
function handleLogIn(response) {
	var playerInfo = response.getElementsByTagName("login")[0];

	if (playerInfo.getElementsByTagName("error").length > 0) {
		var errors = playerInfo.getElementsByTagName("error");
		for (var i = 0; i < errors.length; i++) {
			document.getElementById("logInError").innerHTML += "<br>" + errors[i].childNodes[0].nodeValue;
		}
	} else {
		currentPlayerID = playerInfo.getElementsByTagName("id")[0].childNodes[0].nodeValue;
		currentUserName = playerInfo.getElementsByTagName("username")[0].childNodes[0].nodeValue;

		//Set up login cookie
		var d = new Date();
		d.setTime(d.getTime() + (3*24*60*60*1000));
		var expires = "expires="+d.toUTCString();
		document.cookie = "username=" + currentUserName;
		document.cookie = "password=" + document.getElementById("loginPassword").value;
		document.cookie = expires;

		updatePlayerInfo();
		getMinefieldData();
		initQueryTimer();
		attemptNextGameSignUp();
	}
}

//attemptRegister()
//Gets the entered username and password, then makes a POST request to the server to register that information as a new player.
function attemptRegister() {
	var inputUserName = document.getElementById("loginUsername").value;
	var inputPassword = document.getElementById("loginPassword").value;

	var loginXML = "<login><username>" + inputUserName + "</username><password>" + inputPassword + "</password></login>";

	handleDataWithPHP(loginXML, 'registerPlayer', handleRegister);
}

//handleRegister(response)
//Handles player information after registering that player.
//@param response - The XML information from the server after attempting to register with the server.
function handleRegister(response) {
	var playerInfo = response.getElementsByTagName("login")[0];

	if (playerInfo.getElementsByTagName("error").length > 0) {
		var errors = playerInfo.getElementsByTagName("error");
		for (var i = 0; i < errors.length; i++) {
			document.getElementById("logInError").innerHTML += "<br>" + errors[i].childNodes[0].nodeValue;
		}
	} else {
		attemptLogIn();
	}
}

//attemptNextGameSignUp()
//Gets the entered username and password, then makes a POST request to the server to register that player for the next game.
function attemptNextGameSignUp() {
	var inputUserName = document.getElementById("loginUsername").value;
	var inputPassword = document.getElementById("loginPassword").value;

	var loginXML = "<login><username>" + inputUserName + "</username><password>" + inputPassword + "</password></login>";

	handleDataWithPHP(loginXML, 'signUpForNextGame', handleSignUp);
}

//handleSignUp(response)
//Handles UI information after registering the current player for the next game.
//@param response - The XML information from the server after attempting to sign up for the next game with the server.
function handleSignUp(response) {
	var playerInfo = response.getElementsByTagName("register")[0];

	if (playerInfo.getElementsByTagName("error").length > 0) {
		var errors = playerInfo.getElementsByTagName("error");
		for (var i = 0; i < errors.length; i++) {
			document.getElementById("logInError").innerHTML += "<br>" + errors[i].childNodes[0].nodeValue;
		}
	} else {
		var success = playerInfo.getElementsByTagName("success");
		document.getElementById("nextGameText").innerHTML = success[0].childNodes[0].nodeValue;
		document.getElementById("nextGameButton").className += " hidden";
	}
}

//updatePlayerInfo()
//Updates the UI to reflect the current player information.
function updatePlayerInfo() {
	var inner = "";

	inner = "Welcome, " + currentUserName;

	document.getElementById("userInfo").innerHTML = "<p>" + inner + "</p>";
	document.getElementById("loginPrompt").className += " hidden";
}

//getPlayerID()
//Retrieves the ID of the current player.
//@return The ID of the player, or an empty string if no player is logged in.
function getPlayerID() {
	if (currentPlayerID === null) {
		return "";
	} else {
		return currentPlayerID;
	}
}

//getPlayerName()
//Retrieves the name of the current player.
//@return The name of the player, or an empty string if no player is logged in.
function getPlayerName() {
	if (currentUserName === null) {
		return "";
	} else {
		return currentUserName;
	}
}
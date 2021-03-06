/*
	PlayerController.js

	This file contains all functionality relating to player information, including logging in and tracking cookies.
*/

//currentUserName [String]
//The name of the currently logged-in player.
var currentUserName = null;

//currentPassword [String]
//The password of the currently logged-in player.
var currentPassword = null;

//currentMedals [Array]
//The associative array holding all medal values for the current player.
var currentMedals = [];
currentMedals["dig"] = 0;
currentMedals["flag"] = 0;

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
		attemptLogin();
	}
}

//attemptLogin()
//Sends a blank packet to the websocket server to associate our connection with our login.
function attemptLogin() {
	sendSocketRequest("");
}

//handleLoginResponse(success, error)
//Handles client logic after the server sends back a response after the client sends login information.
//@param success (bool) Whether or not the submission was successful.
//@param error (String) The description of an error, if any, that occurred during login.
function handleLoginResponse(success, error) {
	error = error || 0;

	if (success) {
		if (currentUserName === null) {
			currentUserName = document.getElementById("loginUsername").value;
			currentPassword = document.getElementById("loginPassword").value;
		}

		//Set up login cookie
		var d = new Date();
		d.setTime(d.getTime() + (3*24*60*60*1000));
		var expires = "expires="+d.toUTCString();
		document.cookie = "username=" + currentUserName;
		document.cookie = "password=" + currentPassword;
		document.cookie = expires;

		updatePlayerListForCurrentPlayer();
		playerActionStatus = 0;
		setInteractionPolicy(minefieldStatus === "OPEN", (getPlayerName() !== null) && currentPlayerIsAlive(), currentPlayerCanLayTraps());
		updateOptions();
	} else {
		if (error !== 0) {
			console.log(error);
		}
	}
}

//attemptRegister()
//Gets the entered username and password, then makes a POST request to the server to register that information as a new player.
function attemptRegistration() {
	var inputUserName = document.getElementById("loginUsername").value;
	var inputPassword = document.getElementById("loginPassword").value;

	var registrationXML = "<registration><username>" + inputUserName + "</username><password>" + inputPassword + "</password></registration>";

	sendSocketRequest(registrationXML);
}

//getLoginDetails
//Compiles all known login information into XML to be attached to messages sent to the websocket server.
//@return The XML containing the current player's login information.
function getLoginDetails() {
	var loginName = null, loginPassword = null;
	if (currentUserName === null) {
		loginName = document.getElementById("loginUsername").value;
		loginPassword = document.getElementById("loginPassword").value;
	} else {
		loginName = currentUserName;
		loginPassword = currentPassword;
	}
	loginPassword = document.getElementById("loginPassword").value;

	if (loginName === null || loginPassword === null) {
		return null;
	} else {
		var ret = "<login>";
		ret += "<username>" + loginName + "</username>";
		ret += "<password>" + loginPassword + "</password>";
		ret += "</login>";
		return ret;
	}
}

//setMedals(dig)
//Sets the current levels of medals to display.
//@param dig (int) The level of the dig medal to display.
function setMedals(dig, flag) {
	currentMedals["dig"] = parseInt(dig);
	currentMedals["flag"] = parseInt(flag);
	updateIcons();
}

//getPlayerName()
//Retrieves the name of the current player.
//@return The name of the player, or an empty string if no player is logged in.
function getPlayerName() {
	if (currentUserName === null) {
		return null;
	} else {
		return currentUserName;
	}
}

//currentPlayerCanLayTraps()
//Control function that determines if the current player can play traps.
//@return Whether or not the player can play traps.
function currentPlayerCanLayTraps() {
	var currentPlayerInfo = getRowForCurrentPlayer();
	if (currentPlayerInfo !== null) {
		return currentPlayerInfo.canLayTraps;
	}
	return false;
}

//currentPlayerIsAlive()
//Control function that determines if the current player is alive in the current game or not.
//@return Whether or not the player is dead in the current game.
function currentPlayerIsAlive() {
	var currentPlayerInfo = getRowForCurrentPlayer();
	if (currentPlayerInfo !== null) {
		return currentPlayerInfo.status !== "Dead";
	}
	return false;
}
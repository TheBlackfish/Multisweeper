var currentPlayerID = null;
var currentUserName = null;

//Get username and password, POST to server for authentication.
function attemptLogIn() {
	var inputUserName = document.getElementById("loginUsername").value;
	var inputPassword = document.getElementById("loginPassword").value;

	var loginXML = "<login><username>" + inputUserName + "</username><password>" + inputPassword + "</password></login>";

	handleDataWithPHP(loginXML, 'logInPlayer', handleLogIn);
}

//Handles player information after logging in.
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

		updatePlayerInfo();

		getMinefieldData();

		attemptNextGameSignUp();
	}
}

function attemptRegister() {
	var inputUserName = document.getElementById("loginUsername").value;
	var inputPassword = document.getElementById("loginPassword").value;

	var loginXML = "<login><username>" + inputUserName + "</username><password>" + inputPassword + "</password></login>";

	handleDataWithPHP(loginXML, 'registerPlayer', handleRegister);
}

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

function attemptNextGameSignUp() {
	var inputUserName = document.getElementById("loginUsername").value;
	var inputPassword = document.getElementById("loginPassword").value;

	var loginXML = "<login><username>" + inputUserName + "</username><password>" + inputPassword + "</password></login>";

	handleDataWithPHP(loginXML, 'signUpForNextGame', handleSignUp);
}

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

//Updates the player information box with information after the player logs in.
function updatePlayerInfo() {
	var inner = "";

	inner = "Welcome, " + currentUserName;

	document.getElementById("userInfo").innerHTML = inner;
	document.getElementById("loginPrompt").className += " hidden";
}

function getPlayerID() {
	return currentPlayerID;	
}
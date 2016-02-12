var currentPlayerID = null;
var currentUserName = null;

//Get username and password, POST to server for authentication.
function attemptLogIn() {
	var inputUserName = document.getElementById("loginUsername").value;
	var inputPassword = document.getElementById("loginPassword").value;

	var loginXML = "<login><username>" + inputUserName + "</username><password>" + inputPassword + "</password></login>";

	var xmlhttp = new XMLHttpRequest();
	if ("withCredentials" in xmlhttp) {
		xmlhttp.open("POST", "./php/logInPlayer.php", true);	
	} else if (typeof XDomainRequest != "undefined") {
		xmlhttp = new XDomainRequest();
		xmlhttp.open("POST", "./php/logInPlayer.php", true);	
	} else {
		xmlhttp = null;
		console.log("CORS not supported");	
	}
	xmlhttp.setRequestHeader('Content-Type', 'text/xml');
	xmlhttp.onreadystatechange = function(){
		if (xmlhttp.readyState===4 && xmlhttp.status===200) {
			handleLogIn(xmlhttp.responseXML);
		}
	}
	xmlhttp.send(loginXML);
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
	}
}

//Updates the player information box with information after the player logs in.
function updatePlayerInfo() {
	var inner = "";

	inner = "Welcome, " + currentUserName;

	document.getElementById("userInfo").innerHTML = inner;
	document.getElementById("loginPrompt").className += " hidden";
}
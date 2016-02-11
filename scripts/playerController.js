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

function handleLogIn(response) {
	
}
var currentGameID = null;

function getMinefieldData() {
	console.log("Requesting map information...");
	var xmlhttp = new XMLHttpRequest();
	if ("withCredentials" in xmlhttp) {
		xmlhttp.open("POST", "./php/getGameInfo.php", true);	
	} else if (typeof XDomainRequest != "undefined") {
		xmlhttp = new XDomainRequest();
		xmlhttp.open("POST", "./php/getGameInfo.php", true);	
	} else {
		xmlhttp = null;
		console.log("CORS not supported");	
	}
	xmlhttp.setRequestHeader('Content-Type', 'text/xml');
	xmlhttp.onreadystatechange = function(){
		if (xmlhttp.readyState===4 && xmlhttp.status===200) {
			processMinefieldData(xmlhttp.responseXML);
		}
	}
	xmlhttp.send();
}

function processMinefieldData(response) {
	debugger;
	var allInfo = response.getElementsByTagName("minefield");
	
	currentGameID = allInfo.getElementsByTagName("id")[0].childNodes[0].nodeValue;

	var map = preprocessMinefieldMap(allInfo.getElementsByTagName("map")[0].childNodes[0].nodeValue);
	var h = allInfo.getElementsByTagName("height")[0].childNodes[0].nodeValue;
	var w = allInfo.getElementsByTagName("width")[0].childNodes[0].nodeValue;

	initMinefield(map, h, w);
}

function preprocessMinefieldMap(input) {
	var result = input.split("");
	for (var i = 0; i < result.length; i++) {
		if (result[i] === "U") {
			result[i] = -1;
		} else if (result[i] === "M") {
			result[i] = -2;
		} 
	}
}
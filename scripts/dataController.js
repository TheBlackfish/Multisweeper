var currentGameID = null;

function handleDataWithPHP(data, phpFileName, responseFunction) {
	var xmlhttp = new XMLHttpRequest();
	if ("withCredentials" in xmlhttp) {
		xmlhttp.open("POST", "./php/" + phpFileName + ".php", true);	
	} else if (typeof XDomainRequest != "undefined") {
		xmlhttp = new XDomainRequest();
		xmlhttp.open("POST", "./php/" + phpFileName + ".php", true);	
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

function getMinefieldData() {
	handleDataWithPHP("", "getGameInfo", processMinefieldData);
}

function processMinefieldData(response) {
	var allInfo = response.getElementsByTagName("minefield")[0];
	
	currentGameID = allInfo.getElementsByTagName("id")[0].childNodes[0].nodeValue;

	var map = preprocessMinefieldMap(allInfo.getElementsByTagName("map")[0].childNodes[0].nodeValue);
	var h = allInfo.getElementsByTagName("height")[0].childNodes[0].nodeValue;
	var w = allInfo.getElementsByTagName("width")[0].childNodes[0].nodeValue;
	
	initMinefield(map, h, w);

	var players = allInfo.getElementsByTagName("players")[0];

	populatePlayerListTable(players);
}

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

function getGameID() {
	return currentGameID;
}
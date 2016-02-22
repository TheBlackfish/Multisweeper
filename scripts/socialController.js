function populatePlayerListTable(playerXML) {
	var htmlStr = "<tr><td>Player Name</td></tr>";

	var playerNodes = playerXML.getElementsByTagName("player");
	for (var i = 0; i < playerNodes.length; i++) {
		htmlStr += "<tr><td>" + playerNodes[i].childNodes[0].nodeValue + "</td></tr>";
	}

	document.getElementById("playerListTable").innerHTML = htmlStr;

	console.log(htmlStr);
}
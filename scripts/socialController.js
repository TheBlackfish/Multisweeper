function populatePlayerListTable(playerXML) {
	var htmlStr = "<tr><td>Player Name</td></tr>";

	var playerNodes = playerXML.getElementsByTagName("player");
	for (var i = 0; i < playerNodes.length; i++) {
		htmlStr += "<tr><td>" + playerNodes[i].childNodes[0].nodeValue + "</td>";

		var statusStr = "Dead";
		if (playerNodes[i].getAttribute("status") == 1) {
			statusStr = "Alive";
		}

		htmlStr += "<td>" + statusStr + "</td></tr>";
	}

	document.getElementById("playerListTable").innerHTML = htmlStr;
}
function populatePlayerListTable(playerXML) {
	var htmlStr = "<tr><td>Player Name</td></tr>";

	var clientPlayerText = "";
	var livingPlayersText = "";
	var deadPlayersText = "";

	var playerNodes = playerXML.getElementsByTagName("player");
	for (var i = 0; i < playerNodes.length; i++) {
		var tempStr = "<tr><td>" + playerNodes[i].childNodes[0].nodeValue + "</td>";

		var statusStr = "Dead";
		if (playerNodes[i].getAttribute("status") == 1) {
			statusStr = "Alive";
		}

		tempStr += "<td>" + statusStr + "</td></tr>";


		if (playerNodes[i].childNodes[0].nodeValue === getPlayerName()) {
			clientPlayerText += tempStr.replace("<tr><td>", "<tr><td><img src='images/star.png'/>");
		} else {
			if (playerNodes[i].getAttribute("status") == 1) {
				livingPlayersText += tempStr;
			} else {
				deadPlayersText += tempStr;
			}
		}
	}

	document.getElementById("playerListTable").innerHTML = htmlStr + clientPlayerText + livingPlayersText + deadPlayersText;
}
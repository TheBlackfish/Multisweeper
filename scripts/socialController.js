function populatePlayerListTable(playerXML) {
	var htmlStr = "<tr><td>Player Name</td></tr>";

	var clientPlayerText = "";
	var livingPlayersText = "";
	var deadPlayersText = "";
	var afkPlayersText = "";

	var playerNodes = playerXML.getElementsByTagName("player");
	for (var i = 0; i < playerNodes.length; i++) {
		var tempStr = "<tr><td>" + playerNodes[i].childNodes[0].nodeValue + "</td>";

		var statusStr = "Dead";
		if (playerNodes[i].getAttribute("status") == 1) {
			statusStr = "Alive";
		} else if (playerNodes[i].getAttribute("status") == 2) {
			statusStr = "AFK";
		}

		tempStr += "<td>" + statusStr + "</td></tr>";


		if (playerNodes[i].childNodes[0].nodeValue === getPlayerName()) {
			clientPlayerText += tempStr.replace("<tr><td>", "<tr><td><img src='images/star.png'/>");
		} else {
			switch (playerNodes[i].getAttribute("status")) {
				case 0:
					deadPlayersText += tempStr;
					break;
				case 1:
					livingPlayersText += tempStr;
					break;
				case 2:
					afkPlayersText += tempStr;
					break;
				default:
					livingPlayersText += tempStr;
					break;
			}
		}
	}

	document.getElementById("playerListTable").innerHTML = htmlStr + clientPlayerText + livingPlayersText + deadPlayersText;
}
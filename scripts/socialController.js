/*
	SocialController.js

	This script file holds all functionality relating to social interactions between players, including the player list and chat area.
*/

var chatLog = [];

var chatID = 0;

function attemptChatSubmit(evt) {
	if (evt.key == "Enter") {
		if (!evt.shiftKey) {
			evt.preventDefault();

			if (document.getElementById("chatEntry").value !== null) {
				var xml = "<chat><msg>" + document.getElementById("chatEntry").value + "</msg></chat>";
				sendSocketRequest(xml);
			}
		}
	}
}

function handleChatResponse(success) {
	document.getElementById("chatEntry").value = null;
}

function handleChatUpdate(chatlog) {
	nodesToAdd = [];
	chatNodes = chatLog.getElementsByTagName("chat");
	for (var i = 0; i < chatNodes.length; i++) {
		var tempStr = "<div id='chatMsg" + chatID + "'><p>";
		tempStr += chatNodes[i].getElementsByTagName("user")[0].childNodes[0].nodeValue;
		tempStr += "</p><p>";
		tempStr += chatNodes[i].getElementsByTagName("message")[0].childNodes[0].nodeValue;
		tempStr += "</p></div>";
		nodesToAdd.push(tempStr);
		chatID++;
	}

	if (nodesToAdd.length > 0) {
		while (nodesToAdd.length + chatLog.length > 50) {
			var remove = chatLog.pop();
			document.getElementById("chatLog").innerHTML = document.getElementById("chatLog").replace(remove, "");
		}
		for (var i = 0; i < nodesToAdd.length; i++) {
			var cur = nodesToAdd[i];
			chatLog.unshift(cur);
			document.getElementById("chatLog").innerHTML = cur + document.getElementById("chatLog").innerHTML;
		}
	}
}

//populatePlayerListTable(playerXML)
//Formats and presents the table of players currently in the game being played.
//@param playerXML - The list of players in XML form.
function populatePlayerListTable(playerXML) {
	var htmlStr = "<tr><td>Player Name</td><td>Status</td><td>Trap</td></tr>";

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

		tempStr += "<td>" + statusStr + "</td>";

		var trapStr = "None";
		switch (parseInt(playerNodes[i].getAttribute("trapType"))) {
			case 0:
				trapStr = "Proximity Mine";
				break;
			case 1:
				trapStr = "Radio Nest";
				break;
			case 2:
				trapStr = "Ballista";
				break;
		}
		if (parseInt(playerNodes[i].getAttribute("trapCooldown")) == 0) {
			trapStr += " - !";
		} else {
			trapStr += " - " + playerNodes[i].getAttribute("trapCooldown");
		}

		tempStr += "<td>" + trapStr + "</td></tr>";

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

function updatePlayerListForCurrentPlayer() {
	var currentPlayer = getPlayerName();
	if (currentPlayer !== null) {
		var list = document.getElementById("playerListTable").getElementsByTagName("tbody")[0];
		if (list.innerHTML.lastIndexOf(currentPlayer) !== -1) {
			var listNodes = list.getElementsByTagName("tr");
			for (var i = 0; i < listNodes.length; i++) {
				var currentName = listNodes[i].getElementsByTagName("td")[0];
				if (currentPlayer === currentName.innerHTML) {
					currentName.innerHTML = "<img src='images/star.png'/>" + currentName.innerHTML;
					return;
				}
			}
		} 
		
		window.setTimeout(function() {
			updatePlayerListForCurrentPlayer();
		}, 300);
	}
}
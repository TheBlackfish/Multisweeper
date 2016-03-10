/*
	SocialController.js

	This script file holds all functionality relating to social interactions between players, including the player list and chat area.
*/

//attemptChatSubmit(evt)
//Attempts to submit chat data to the server for the chat area.
//@param evt - The event object handling the keyboard input.
function attemptChatSubmit(evt) {
	if (evt.key == "Enter") {
		if (!evt.shiftKey) {
			evt.preventDefault();

			var xml = "<chat><userID>" + getPlayerID() + "</userID><msg>" + document.getElementById("chatEntry").value + "</msg></chat>";
			console.log(xml);
			handleDataWithPHP(xml, "submitGameChat", handleChatUpdate);
		}
	}
}

//attemptChatUpdate()
//Contacts the server to get any new chat messages.
function attemptChatUpdate() {
	handleDataWithPHP("", "getGameChat", handleChatUpdate);
}

//handleChatUpdate(response)
//Formats any chat messages recieved from the server into a format the player can read.
//@param response - The XML chat log from the server.
function handleChatUpdate(response) {
	var htmlStr = "";

	var chatNodes = response.getElementsByTagName("chat");
	if (chatNodes.length > 0) {
		for (var i = 0; i < chatNodes.length; i++) {
			var tempStr = "<div><p>";
			tempStr += chatNodes[i].getElementsByTagName("user")[0].childNodes[0].nodeValue;
			tempStr += "</p><p>";
			tempStr += chatNodes[i].getElementsByTagName("msg")[0].childNodes[0].nodeValue;
			tempStr += "</p></div>";

			htmlStr += tempStr;
		}

		document.getElementById("chatLog").innerHTML = htmlStr;
	}
}

//populatePlayerListTable(playerXML)
//Formats and presents the table of players currently in the game being played.
//@param playerXML - The list of players in XML form.
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

//updateUpcomingGameTime(gameTime)
//Formats and presents the time until the next game on the UI.
//@param gameTime - The information about the next game in XML form.
function updateUpcomingGameTime(gameTime) {
	if (gameTime !== null) {
		if (document.getElementById("gameTimePrompt") !== null) {
			var promptBox = "<div id='gameTimePrompt' class='topBarBox'><p>The next game starts at " + gameTime.getChildNodes[0].nodeValue + "</p></div>";
			document.getElementById("topBar").innerHTML += promptBox;
		} else {
			document.getElementById("gameTimePrompt").innerHTML = "<p>The next game starts at " + gameTime.getChildNodes[0].nodeValue + "</p>";
		}
	} else {
		if (document.getElementById("gameTimePrompt") !== null) {
			document.getElementById("topBar").innerHTML = document.getElementById("topBar").innerHTML.replace(document.getElementById("gameTimePrompt").outerHTML, "");
		}
	}
}
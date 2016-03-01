/*
	ActionController.js

	This script file contains all server-client interactions with the server regarding actions.
*/

//submitAction()
//Gets action information from various files and sends it to the server for processing.
function submitAction() {
	document.getElementById("submitMessage").innerHTML = "Submitting...";

	var selectionTile = getSelectedTile();

	if (selectionTile !== null) {
		var xml = '<submit>';
		xml += '<playerID>' + getPlayerID() + '</playerID>';
		xml += '<gameID>' + getGameID() + '</gameID>';
		xml += '<xCoord>' + selectionTile["x"] + '</xCoord>';
		xml += '<yCoord>' + selectionTile["y"] + '</yCoord>';
		xml += '<actionType>' + selectionTile["action"] + '</actionType>';
		xml += '</submit>';

		handleDataWithPHP(xml, 'submitAction', resolveActionSubmission);
	} else {
		document.getElementById("submitMessage").innerHTML = "Please select a tile to dig above!";
	}
}

//resolveActionSubmission(response)
//Takes the response from the server for submitting an action and properly resolves it.
//@param response - The response from the server for the submission in XML form.
function resolveActionSubmission(response) {
	var text = "Unexpected client error!";
	var allInfo = response.getElementsByTagName("submission")[0];
	var actionDone = allInfo.getElementsByTagName("action");

	if (actionDone.length > 0) {
		text = actionDone[0].nodeValue;
		forceTimerToTime(3);
	} else {
		text = "";
		var errors = allInfo.getElementsByTagName("error");
		for (var i = 0; i < errors.length; i++) {
			if (i != 0) {
				text += "<br>";
			}
			text += errors[i].childNodes[0].nodeValue;
		}
	}

	document.getElementById("submitMessage").innerHTML = text;
}
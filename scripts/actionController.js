/*
	ActionController.js

	This script file contains all server-client interactions with the server regarding actions.
*/

//submitAction()
//Gets action information from various files and sends it to the server for processing.
function submitAction() {
	document.getElementById("submitMessage").innerHTML = "Submitting...";

	var selectionTile = getSelectedActionArray();

	if (selectionTile !== null) {
		var xml = '<action>';
		xml += '<xCoord>' + selectionTile["x"] + '</xCoord>';
		xml += '<yCoord>' + selectionTile["y"] + '</yCoord>';
		xml += '<actionType>' + selectionTile["action"] + '</actionType>';
		xml += '</action>';

		sendSocketRequest(xml);
	} else {
		document.getElementById("submitMessage").innerHTML = "Please select a tile to dig above!";
	}
}

function handleActionResponse(success, xmlNodes) {
	xmlNodes = xmlNodes || 0;

	var text = "Unexpected client error!";

	if (success) {
		actionSubmitted = true;
		text = "";
	} else {
		text = "";
		for (var i = 0; i < xmlNodes.length; i++) {
			text += xmlNodes[i].getChildNodes[0].nodeValue;
			if ((xmlNodes.length - i) > 2) {
				text += "<br>";
			}
		}
	}

	document.getElementById("submitMessage").innerHTML = text;
}
/*
	ActionController.js

	This script file contains all server-client interactions with the server regarding actions.
*/

//submitAction()
//Gets action information from various files and sends it to the server for processing.
function submitAction() {
	var selectionTile = getSelectedActionArray();

	if (selectionTile !== null) {
		var xml = '<action>';
		xml += '<xCoord>' + selectionTile["x"] + '</xCoord>';
		xml += '<yCoord>' + selectionTile["y"] + '</yCoord>';
		xml += '<actionType>' + selectionTile["action"] + '</actionType>';
		xml += '</action>';

		sendSocketRequest(xml);
	}
}

//handleActionResponse(success, xmlNodes)
//Handles client logic after the server sends back a response after the client sends action information.
//@param success (bool) Whether or not the submission was successful.
//@param xmlNode (Array) The array of DOMDocumentNodes containing any status messages from the server.
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
}
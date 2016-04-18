var socket;

function initSocket() {
	var host = "ws://192.168.63.55:13002";
	try {
		socket = new WebSocket(host);
		socket.onmessage = function(msg) {
			handleSocketMessage(msg.data);
		}
	}
	catch (ex) {
		console.log(ex);
	}
}

function sendSocketRequest(request) {
	if (socket.readyState === 1) {
		var xml = "<request>";
		xml += getLoginDetails();
		xml += request;
		xml += "</request>";
		socket.send(xml);
	} else {
		setTimeout(function() {
			sendSocketRequest(request);
		}, 500);
	}
}

function handleSocketMessage(message) {
	var data = null;
	if (window.DOMParser) {
		var parser = new window.DOMParser();
		data = parser.parseFromString(message, "text/xml");
	} else {
		//Warn about unsupported browsers!
	}

	if (data !== null) {
		var typeOfMessage = data.documentElement.tagName;
		if (typeOfMessage === null) {
			console.log("Data is not properly formed XML, returning.");
		} else if (typeOfMessage === "update") {
			//Handle update XML
			var mapUpdate = null, chatUpdate = null;

			if (data.getElementsByTagName("update").length > 0) {
				mapUpdate = data.getElementsByTagName("update")[0];
			}

			if (data.getElementsByTagName("chatLog").length > 0) {
				chatUpdate = data.getElementsByTagName("chatLog")[0];
			}

			if (mapUpdate !== null) {
				handleMinefieldUpdate(mapUpdate);
			}

			if (chatUpdate !== null) {
				handleChatUpdate(chatUpdate);
			}
		} else if (typeOfMessage === "response") {
			//Handle response XML
			var actionResponse = null, chatResponse = null, loginResponse = null;

			if (data.getElementsByTagName("login").length > 0) {
				loginResponse = parseInt(data.getElementsByTagName("login")[0].childNodes[0].nodeValue);
			}

			if (data.getElementsByTagName("action").length > 0) {
				actionResponse = data.getElementsByTagName("action")[0];
			}

			if (data.getElementsByTagName("chat").length > 0) {
				chatResponse = data.getElementsByTagName("chat")[0];
			}

			if (actionResponse === null && chatResponse === null && loginResponse === null) {
				//Search for login error.
				if (data.getElementsByTagName("loginError").length > 0) {
					handleLoginResponse(false, data.getElementsByTagName("loginError"));
				}
			} else {
				//Resolve each individual node.
				if (loginResponse !== null) {
					handleLoginResponse(loginResponse);
				}
				if (actionResponse !== null) {
					if (actionResponse.getElementsByTagName("actionError").length > 0) {
						handleActionResponse(false, actionResponse.getElementsByTagName("actionError"));
					} else {
						handleActionResponse(true, actionResponse.getElementsByTagName("action"));
					}
				}

				if (chatResponse !== null) {
					handleChatResponse(true);
				}
			}
		}
	}
}
/*
	minefieldInteraction.js

	This file controls all functionality involving player interaction with the game area.
*/

//interfaceInitialized [boolean]
//Control variable for if the interactions with the game area have been initialized yet or not.
var interfaceInitialized = false;

//initMinefieldInterface()
//Adds event listeners to the canvas for various mouse interaction.
function initMinefieldInterface() {
	if (!interfaceInitialized) {
		document.getElementById("gameArea").addEventListener('click', function(evt) {
			processSelection(evt);	
		}, false);
		interfaceInitialized = true;
	}
}

//processSelection(e)
//Performs the correct action with a mouse click depending on the tile clicked on.
//@param e - The mouse event for the mouse click.
function processSelection(e) {
	if (getPlayerID() !== "") {
		if (minefieldInitialized && minefieldGraphicsInitialized) {
			var canSelect = false;
			var coord = calculateMousePosition(e.clientX, e.clientY);
			coord = getTileCoordinatesFromRealCoordinates(coord[0], coord[1]);

			var s = getSelectedActionArray();

			if (s !== null) {
				if (s["x"] === coord[0] && s["y"] === coord[1]) {
					canSelect = true;
				}
			}

			var val = getTileValue(coord[0], coord[1]);

			if (val === -1 || val === 9) {
				canSelect = true;
			}

			var t = getTanks();

			for (var i = 0; i < t.length && canSelect; i++) {
				if (t[0] == coord[0] && t[1] == coord[1]) {
					canSelect = false;
				}
			}

			var o = getOtherPlayers();

			for (var i = 0; i < o.length && canSelect; i++) {
				if (o[0] == coord[0] && o[1] == coord[1]) {
					canSelect = false;
				}
			}

			if (canSelect) {
				selectCoordinates(coord[0], coord[1]);
			}
		}
	}
}

//calculateMousePosition(x, y)
//Takes the document mouse position and corrects it in regards to the canvas.
//@param x - The x-coordinate on the window.
//@param y - The y-coordinate on the window.
//@return The corrected coordinate on the canvas in array form.
function calculateMousePosition(x, y) {
	var realX = x - document.getElementById("gameArea").getBoundingClientRect().left;
	var realY = y - document.getElementById("gameArea").getBoundingClientRect().top;
	return [realX, realY];
}

//getTileCoordinatesFromRealCoordinates(x, y)
//Takes pixel-perfect coordinates and translates them to tile coordinates.
//@param x - The x-coordinate to translate.
//@param y - The y-coordinate to translate.
//@return The tile coordinates in array form.
function getTileCoordinatesFromRealCoordinates(x, y) {
	return [Math.floor(x / minefieldTileSize), Math.floor(y / minefieldTileSize)];	
}
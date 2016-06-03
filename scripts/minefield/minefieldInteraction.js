/*
	minefieldInteraction.js

	This file controls all functionality involving player interaction with the game area.
*/

//interfaceInitialized [boolean]
//Control variable for if the interactions with the game area have been initialized yet or not.
var interfaceInitialized = false;

//isMouseDown [boolean]
//Control variable tracking if the mouse button is currently down or not.
var isMouseDown = false;

//originalClickPos [Array<int>]
//This stores the initial point of clickage for dragging purposes.
var originalClickPos = null;

//prevClickPos [Array<int>]
//This stores the mouse position between updates to track overall changes to the mouse position and dragging.
var prevClickPos = null;

//
var deltaToDrag = 5;
var currentDelta = 0;
var isMouseDragging = false;

//initMinefieldInterface()
//Adds event listeners to the canvas for various mouse interaction.
function initMinefieldInterface() {
	if (!interfaceInitialized) {
		document.getElementById("gameArea").addEventListener('mousedown', onCanvasMouseDown, false);
		document.getElementById("gameArea").addEventListener('mousemove', onCanvasMouseMove, false);
		document.getElementById("gameArea").addEventListener('mouseup', onCanvasMouseUp, false);
		interfaceInitialized = true;
	}
}

//onCanvasMouseDown(evt)
//Sets various settings when the canvas is clicked.
//@param evt (MouseEvent) The mouse click event.
function onCanvasMouseDown(evt) {
	isMouseDown = true;
	isMouseDragging = false;

	originalClickPos = calculateMousePosition(evt.clientX, evt.clientY);
	prevClickPos = originalClickPos;
	currentDelta = 0;
}

//onCanvasMouseMove(evt)
//Changes variables based on mouse movement when the mouse is down.
//@param evt (MouseEvent) The mousemove event.
function onCanvasMouseMove(evt) {
	if (isMouseDown) {
		var currentPosition = calculateMousePosition(evt.clientX, evt.clientY);
		if (isMouseDragging) {
			var change = currentPosition[0] - prevClickPos[0];
			adjustHorizontalOffset(change);
		} else {
			var diff = Math.sqrt(Math.pow(currentPosition[0] - prevClickPos[0], 2) + Math.pow(currentPosition[1] - prevClickPos[1], 2));
			currentDelta += diff;
			if (currentDelta > deltaToDrag) {
				isMouseDragging = true;
			}
		}
		prevClickPos = currentPosition;
	}
}

//onCanvasMouseUp(evt)
//Sets various settings when the user unclicks away from the canvas.
//@param evt (MouseEvent) The mouseup event.
function onCanvasMouseUp(evt) {
	isMouseDown = false;

	originalClickPos = null;
	prevClickPos = null;

	if (!isMouseDragging) {
		processSelection(evt);
	}
}

//setInteractionPolicy(playerIsAlive, canLayTraps)
//Forces an interaction policy.
//@param gameIsGoing [bool] If false, no interaction is allowed.
//@param playerIsAlive [bool] If false, no interaction is allowed.
//@param canLayTraps [bool] If false, the player cannot lay traps.
function setInteractionPolicy(gameIsGoing, playerIsAlive, canLayTraps) {
	if (gameIsGoing && playerIsAlive) {
		setActionState(0, true);
		setActionState(1, true);
		setActionState(2, canLayTraps);
	} else {
		for (var i = 0; i <= 2; i++) {
			setActionState(i, false);
		}
	}
}

//processSelection(e)
//Performs the correct action with a mouse click depending on the tile clicked on.
//@param e - The mouse event for the mouse click.
function processSelection(e) {
	if (getPlayerName() !== null) {
		if (minefieldInitialized && minefieldGraphicsInitialized) {
			var canSelect = false;
			var altSelect = false;
			var coord = calculateMousePosition(e.clientX, e.clientY);
			coord = getTileCoordinatesFromRealCoordinates(coord[0], coord[1]);

			if (coord[0] < 0 || coord[1] < 0) {
				return;
			} else if (coord[0] >= minefieldWidth || coord[1] >= minefieldHeight) {
				return;
			}

			var s = getSelectedActionArray();

			if (s !== null) {
				if (s["x"] === coord[0] && s["y"] === coord[1]) {
					canSelect = true;
				}
			}

			var val = getTileValue(coord[0], coord[1]);

			if (val === -1 || val === 9) {
				canSelect = true;
			} else if (val >= 0 && val <= 8) {
				canSelect = true;
				altSelect = true;
			}

			var ft = getFriendlyTanks();

			for (var i = 0; i < ft.length && canSelect; i++) {
				if (ft[i][0] == coord[0] && ft[i][1] == coord[1]) {
					canSelect = false;
				}
			}

			var et = getEnemyTanks();

			for (var i = 0; i < et.length && canSelect; i++) {
				if (et[i][0] == coord[0] && et[i][1] == coord[1]) {
					canSelect = false;
				}
			}

			var tr = getTraps();
			for (var i = 0; i < tr.length && canSelect; i++) {
				if (tr[i][1] == coord[0] && tr[i][2] == coord[1]) {
					canSelect = false;
				}
			}

			var o = getOtherPlayers();

			for (var i = 0; i < o.length && canSelect; i++) {
				if (o[i][0] == coord[0] && o[i][1] == coord[1]) {
					canSelect = false;
				}
			}

			if (canSelect) {
				if (altSelect) {
					selectCoordinatesVisible(coord[0], coord[1]);
				} else {
					selectCoordinates(coord[0], coord[1]);
				}
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
	return [Math.floor((x - minefieldTileHorizontalOffset - minefieldTileFixedHorizontalOffset) / finalTileSize), Math.floor((y - minefieldTileVerticalOffset) / finalTileSize)];	
}
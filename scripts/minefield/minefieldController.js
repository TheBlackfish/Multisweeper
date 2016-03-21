/*
	Minefield.js

	This file controls all functionality relating to the minefield, including tile statuses, visibility, drawing, and direct interactions with the canvas.

	Tile numbers in the minefield translate to the following:
	-2 	Revealed mine
	-1	Unrevealed
	0-8	Number of adjacent mines
	9	Flag placed

	Tanks and other players are in their own arrays.
	Flag & shovel actions are stored in the selected* set of variables. 
*/

var minefield = [];
var otherPlayers = [];
var tanks = [];

var minefieldWidth = 0;
var minefieldHeight = 0;
var minefieldInitialized = false;

var selectedAction = 0; //0 for shovel, 1 for flag.
var selectedCoordinates = null;
var selectedOriginalValue = null;

function initMinefield(input, h, w, t, o) {
	initMinefieldGraphics();
	initMinefieldInterface();
	updateMinefield(input, h, w, t, o);
	minefieldInitialized = true;
}

function updateMinefield(input, h, w, t, o) {
	if (hasSubmittedAction()) {
		clearSelectedCoordinates();
	}
	minefield = importMinefieldFromArray(input, w, h);
	tanks = t;
	otherPlayers = o;
	drawMinefield();
}

function importMinefieldFromArray(input, width, height) {
	if (input.length !== (width*height)) {
		console.log("Input is not of the correct size, aborting importMinefieldFromArray");
	} else {
		var result = new Array();
		while (input.length > 0) {
			var temp = input.splice(0, height);
			if (temp.length !== height) {
				console.log("Chunking input does not have correct height!");
			}
			result.push(temp);
		}
		if (result.length !== width) {
			console.log("Chunking did not lead to correct width of field!");
		}
		return result;
	}
	return null;
}

function selectCoordinates(x, y) {
	//If coordinates are null, set our selection to 0 and the current coordinates. 
	if (selectedCoordinates === null) {
		selectedAction = 0;
		selectedCoordinates = [x,y];
	} else {
		//If the coordinates are the same, switch what our selected action is.
		if (selectedCoordinates[0] === x && selectedCoordinates[1] === y) {
			if (selectedAction === 0) {
				selectedAction = 1;
			} else {
				selectedAction = 0;
			}
		//Else, set our selection to 0 and the current coordinates.
		//Also, reset the previous tile.
		} else {
			temp = selectedCoordinates.concat([]);
			selectedAction = 0;
			selectedCoordinates = [x,y];
			drawTileAtCoordinates(temp[0], temp[1]);
		}	
	}
	drawTileAtCoordinates(x, y);
}

function clearSelectedCoordinates() {
	temp = selectedCoordinates.concat([]);
	selectedAction = 0;
	selectedCoordinates = null;
	drawTileAtCoordinates(temp[0], temp[1]);
}

//getAllTilesWithValue(value)
//Gets coordinates for all tiles with the value specified. Each index in the returned array is a coordinate in array form ([x, y]).
//@param value - The value of the tiles to look for.
//@return The array of all coordinates to be retrieved.
function getAllTilesWithValue(value) {
	var ret = [];
	for (var i = 0; i < minefield.length; i++) {
		for (var j = 0; j < minefield[i].length; j++) {
			if (minefield[i][j]	== value) {
				ret.push([i, j]);	
			}
		}
	}
	return ret;
}

function getMinefield() {
	return minefield;
}

function getOtherPlayers() {
	return otherPlayers;
}

function getSelectedActionArray() {
	if (selectedCoordinates !== null) {
		var ret = [];
		ret["x"] = selectedCoordinates[0];
		ret["y"] = selectedCoordinates[1];
		ret["action"] = selectedAction;
		return ret;
	}

	return null;
}

function getTanks() {
	return tanks;
}

function getTileValue(x, y) {
	return minefield[x][y];
}
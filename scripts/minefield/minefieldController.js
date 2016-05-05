/*
	minefieldController.js

	This file controls all functionality relating to the minefield that should be visible to other classes, including tile selection and retrieval of data.

	Tile numbers in the minefield translate to the following:
	-2 	Revealed mine
	-1	Unrevealed
	0-8	Number of adjacent mines
	9	Flag placed
	10	Wreckage

	Tanks and other players are in their own arrays.
	Flag & shovel actions are stored in the selected* set of variables. 

	Actions are also stored by number. They are as follows:
	0	Shovel tile
	1	Plant flag
	2	Lay trap
*/

//minefield [Double Array]
//The double array containing all of the known values of tiles, with -1 for unrevealed tiles and -2 for mines.
var minefield = [];

var minefieldStatus = "?";

//otherPlayers [Double Array]
//The double array containing all coordinates of other players' actions.
var otherPlayers = [];

//friendlyTanks [Double Array]
//The double array containing all coordinates of tanks.
var friendlyTanks = [];

//enemyTanks [Double Array]
//The double array containing all coordinates of enemy tanks.
var enemyTanks = [];

//traps [Double Array]
//The double array containing all coordinates of traps.
var traps = [];

//minefieldWidth [int]
//The current width of the minefield.
var minefieldWidth = 0;

//minefieldHeight [int]
//The current height of the minefield.
var minefieldHeight = 0;

//minefieldInitialized [Boolean]
//A control variable for if the minefieldController and subsequent children are fully initialized.
var minefieldInitialized = false;

//selectedAction [int]
//The selection variable denoting what action is currently selected.
var selectedAction = 0; //0 for shovel, 1 for flag.

//selectedCoordinates [Array]
//The selection variable denoting what coordinates are currently selected. Null for no selection.
var selectedCoordinates = null;

//allowedActions [Array]
//The array containing which actions are currently allowed due to various factors.
var allowedActions = [0, 1, 2];

//handleMinefieldUpdate(xml)
//Updates all of the appropriate variables and what not using the XML provided.
//@param xml [XML] The update XML.
function handleMinefieldUpdate(xml) {
	if (!minefieldInitialized) {
		initMinefieldGraphics();
		initMinefieldInterface();
	}

	var data = processMinefieldXML(xml);

	if ("map" in data && data["map"] !== null) {
		//Full update
		minefield = data["map"];
		minefieldWidth = data["width"];
		minefieldHeight = data["height"];
		minefieldStatus = data["status"];
		friendlyTanks = data["friendlyTanks"];
		enemyTanks = data["enemyTanks"];
		traps = data["traps"];
		populatePlayerListTable(data["players"]);
		setInteractionPolicy(minefieldStatus === "OPEN", getPlayerName() !== null, currentPlayerCanLayTraps())
		setSelectionCoordinates(-1, -1, 0);
	}
	if ("otherPlayers" in data && data["otherPlayers"] !== null) {
		otherPlayers = data["otherPlayers"];
	}

	drawMinefieldWithResize();
	minefieldInitialized = true;
}

//setSelectionCoordinates(x, y, action)
//Overrides any current selection.
//@param x [int] The x-coordinate of the selection.
//@param y [int] The y-coordinate of the selection.
//@param action [int] The action to have the selection correspond to.
function setSelectionCoordinates(x, y, action) {
	if (x === -1 || y === -1) {
		clearSelectedCoordinates();
	} else {
		selectedAction = action;
		selectedCoordinates = [x,y];
		drawTileAtCoordinates(x, y);
	}
}

//selectCoordinates(x, y)
//Depending on the tile at (x,y), this method highlights it and saves it into the selection variable appropriately.
//@param x [int] - The x-coordinate to select.
//@param y [int] - The y-coordinate to select.
function selectCoordinates(x, y) {
	//If coordinates are null, set our selection to 0 and the current coordinates. 
	if (allowedActions.length > 0) {
		if (selectedCoordinates === null) {
			selectedAction = 0;
			selectedCoordinates = [x,y];
		} else {
			//If the coordinates are the same, switch what our selected action is.
			if (selectedCoordinates[0] === x && selectedCoordinates[1] === y) {
				if (selectedAction >= 2) {
					selectedAction = 0;
				} else {
					selectedAction++;
				}
				while (allowedActions.lastIndexOf(selectedAction) === -1) {
					if (selectedAction >= 2) {
						selectedAction = 0;
					} else {
						selectedAction++;
					}
				}
			//Else, set our selection to 0 and the current coordinates.
			//Also, reset the previous tile.
			} else {
				temp = selectedCoordinates.concat([]);
				selectedAction = 0;
				while (allowedActions.lastIndexOf(selectedAction) === -1) {
					if (selectedAction >= 2) {
						selectedAction = 0;
					} else {
						selectedAction++;
					}
				}
				selectedCoordinates = [x,y];
				drawTileAtCoordinates(temp[0], temp[1]);
			}	
		}
		drawTileAtCoordinates(x, y);
	}
}

//selectCoordinatesVisible(x, y)
//Depending on the tile at (x,y), this method highlights it and saves it into the selection variable appropriately. The difference this method makes is that only pre-approved actions for already visible tiles are allowed.
//@param x [int] - The x-coordinate to select.
//@param y [int] - The y-coordinate to select.
function selectCoordinatesVisible(x, y) {
	if (allowedActions.lastIndexOf(2) > -1) {
		//If coordinates are null, set our selection to 2 and the current coordinates. 
		if (selectedCoordinates === null) {
			selectedAction = 2;
			selectedCoordinates = [x,y];
		} else {
			//If the coordinates are the same, switch what our selected action is.
			if (selectedCoordinates[0] === x && selectedCoordinates[1] === y) {
				selectedAction = 2;
			//Else, set our selection to 0 and the current coordinates.
			//Also, reset the previous tile.
			} else {
				temp = selectedCoordinates.concat([]);
				selectedAction = 2;
				selectedCoordinates = [x,y];
				drawTileAtCoordinates(temp[0], temp[1]);
			}	
		}
		drawTileAtCoordinates(x, y);
	}
}

//clearSelectedCoordinates()
//Removes any selection saved.
function clearSelectedCoordinates() {
	if (selectedCoordinates !== null) {
		temp = selectedCoordinates.concat([]);
		selectedAction = 0;
		selectedCoordinates = null;
		drawTileAtCoordinates(temp[0], temp[1]);
	}
}

//setActionState(action, val)
//Allows or disallows the action provided based on the boolean provided.
//@param action [int] The flag of the action to alter.
//@param val [bool] The state to set the flag to.
function setActionState(action, val) {
	if (val) {
		if (allowedActions.lastIndexOf(action) === -1) {
			allowedActions.push(action);
		}
	} else {
		while (allowedActions.lastIndexOf(action) > -1) {
			allowedActions.splice(allowedActions.lastIndexOf(action), 1);
		}
	}
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

//getMinefield()
//Returns the minefield.
//@return The minefield.
function getMinefield() {
	return minefield;
}

//getOtherPlayers()
//Returns the array of other player actions.
//@return The array of other player actions.
function getOtherPlayers() {
	return otherPlayers;
}

//getSelectedActionArray()
//Returns the selection variables in a convenient associative array.
//@return The current selection in associative array form.
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

//getTanks()
//Returns the tanks.
//@return The tanks.
function getFriendlyTanks() {
	return friendlyTanks;
}

//getEnemyTanks()
//Returns the enemy tanks.
//@return The enemy tanks.
function getEnemyTanks() {
	return enemyTanks;
}

//getTraps()
//Returns the traps.
//@return The traps.
function getTraps() {
	return traps;
}

//getTileValue(x, y)
//Returns the field value for the tile specified.
//@param x [int] - The x-coordinate of the requested tile.
//@param y [int] - The y-coordinate of the requested tile.
//@return The value of the requested tile.
function getTileValue(x, y) {
	return minefield[x][y];
}
/*
	Minefield.js

	This file controls all functionality relating to the minefield, including tile statuses, visibility, drawing, and direct interactions with the canvas.

	Tile numbers in the minefield translate to the following:
	-2 	Revealed mine
	-1	Unrevealed
	0-8	Number of adjacent mines
	9	Flag placed
	10	Shovel selection
	11	Flag selection
	12	Other players' actions
*/

//minefield [Double Array]
//Holds all the tile statuses for drawing the minefield on the screen.
var minefield = [];

//minefieldHeight [Integer]
//The height of the minefield in tiles.
var minefieldHeight = 1;

//minefieldWidth [Integer]
//The width of the minefield in tiles.
var minefieldWidth = 1;

//minefieldContext [Canvas context]
//The drawing context of the minefield canvas on the screen.
var minefieldContext = null;

//minefieldImages [Array]
//An array of images corresponding to all 
var minefieldImages = new Array();

//minefieldImagesLoaded [Integer]
//A control variable that exists to help with loading images and preventing graphical errors.
var minefieldImagesLoaded = 0;

//minefieldInput [String]
//The string that represents a minefield being provided to create the game.
var minefieldInput = null;

//minefieldInitialized [Boolean]
//A control variable to prevent reloading of images upon updating the map.
var minefieldInitialized = false;

//minefieldSquareSize [Integer]
//The size of the minefield tiles (in pixels).
var minefieldSquareSize = 24;

//hoverFPS [double]
//The amount of time (in milliseconds) to update the hovering interaction on the minefield.
var hoverFPS = Math.floor(1000/30);

//previousHoverCoords [Array]
//The coordinates of the previously hovered-over tile.
var previousHoverCoords = null;

//selectedTilesPreviousValue [Integer]
//The control variable to help with selection logic.
var selectedTilesPreviousValue = -1;

var tankCoordinates = new Array();

//initMinefield(input, h, w)
//Initializes the minefield. If the minefield has been initialized already, the current minefield is updated with the new information. Otherwise, various control variables and functions will be called as part of the initialization process.
//@param input - The string representing the minefield data to show to the player.
//@param h - The integer that is the height of the minefield.
//@param w - The integer that is the width of the minefield.
//@param t - The array of coordinates that all tanks are currently located at.
function initMinefield(input, h, w, t) {
	minefieldInput = input;
	minefieldHeight = h;
	minefieldWidth = w;
	tankCoordinates = t;
	if (!minefieldInitialized) {
		initImages();
	} else {
		updateMinefield(input);
	}		
}

//finishInitMinefield()
//Finishes initialization of the minefield after loading images.
function finishInitMinefield() {
	if (!minefieldInitialized) {
		minefieldInitialized = true;
		initMinefieldDisplay(minefieldInput);
		drawMinefield();
		initMinefieldInterface();
	}
}

//initImages()
//Attempts to load all images necessary for the game. When loading, each image will increment the amount of images loaded. Then, if that number is equal to the number of images, the initialization will be finished.
function initImages() {
	var allImages = ["mine", "unrevealed", "0", "1", "2", "3", "4", "5", "6", "7", "8", "flag", "shovel", "plantflag", "hover", "otherPlayer", "tank"];
	for (var i = 0; i < allImages.length; i++) {
		var img = new Image();
		img.onload = function() {
			minefieldImagesLoaded++;
			if (minefieldImagesLoaded >= allImages.length) {
				finishInitMinefield();	
			}
		};
		img.src = "./images/" + allImages[i] + ".png";
		minefieldImages[allImages[i]] = img;	
	}
}

//initMinefieldDisplay(input)
//Sets the context of the canvas as well as finalizes the minefield input.
//@param input - The input data for the minefield.
function initMinefieldDisplay(input) {
	minefieldContext = document.getElementById("gameArea").getContext("2d");
	
	if (input.length !== (minefieldHeight*minefieldWidth)) {
		console.log("Input is not of the correct size, aborting initMinefieldDisplay");
	} else {
		for (var x = 0; x < minefieldWidth; x++) {
			minefield.push([]);
			for (var y = 0; y < minefieldHeight; y++) {
				minefield[x].push(input.shift());
			}
		}
	}
}

//initMinefieldInterface()
//Adds event listeners to the canvas for various mouse interaction.
function initMinefieldInterface() {
	document.getElementById("gameArea").addEventListener('mousemove', function(evt) {
		updateHover(evt);
	}, false);
	document.getElementById("gameArea").addEventListener('click', function(evt) {
		selectTile(evt);	
	}, false);
}

//updateMinefield(input)
//Updates all current minefield information to match the input provided.
//@param input - The minefield information to update to.
function updateMinefield(input) {
	//Save all tiles currently altered by the player.
	var previouslyShoveled = getAllTilesWithValue(10);
	var previouslyFlagged = getAllTilesWithValue(11);

	//Update the input based on those altered tiles.
	var temp = [];

	for (var x = 0; x < minefieldWidth; x++) {
		temp.push([]);
		for (var y = 0; y < minefieldHeight; y++) {
			temp[x].push(minefieldInput.shift());
		}
	}

	for (var i = 0; i < previouslyShoveled.length; i++) {
		var xCoord = previouslyShoveled[i][0];
		var yCoord = previouslyShoveled[i][1];

		if (temp[xCoord][yCoord] == -1 || temp[xCoord][yCoord] == 9 || temp[xCoord][yCoord] == 12) {
			temp[xCoord][yCoord] = 10;
		}
	}

	for (var i = 0; i < previouslyFlagged.length; i++) {
		var xCoord = previouslyFlagged[i][0];
		var yCoord = previouslyFlagged[i][1];

		if (temp[xCoord][yCoord] == -1 && temp[xCoord][yCoord] != 9) {
			temp[xCoord][yCoord] = 11;
		}
	}

	minefield = temp;

	drawMinefield();
}

//drawMinefield
//Draws the minefield to the canvas.
function drawMinefield() {
	for (var i = 0; i < minefield.length; i++) {
		for (var j = 0; j < minefield[i].length; j++) {
			drawTileAtCoordinates(minefield[i][j], i, j);	
		}
	}

	for (var i = 0; i < tankCoordinates.length; i++) {
		drawTankAtCoordinates(tankCoordinates[i][0], tankCoordinates[i][1]);
	}
}

//getSelectedTile()
//Formats the selection data into an associative array. The array has values for x, y, and action.
//@return The formatted associative array.
function getSelectedTile() {
	var temp = getAllTilesWithValue(10);
	if (temp.length >= 1) {
		var cur = temp[0];
		var ret = [];
		ret["x"] = cur[0];
		ret["y"] = cur[1];
		ret["action"] = 0;
		return ret;
	} else {
		temp = getAllTilesWithValue(11);
		if (temp.length >= 1) {
			var cur = temp[0];
			var ret = [];
			ret["x"] = cur[0];
			ret["y"] = cur[1];
			ret["action"] = 1;
			return ret;
		}
		return null;
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

//drawTileAtCoordinates(value, x, y)
//Draws the appropriate image for the value specified at the tile position x, y.
//@param value - The minefield tile value to draw at the specified tile position.
//@param x - The x-coordinate to draw the tile at.
//@param y - The y-coordinate to draw the tile at.
function drawTileAtCoordinates(value, x, y) {
	var realX = x * minefieldSquareSize;
	var realY = y * minefieldSquareSize;
	var val = getTileValueString(value);
	
	minefieldContext.drawImage(minefieldImages[val], realX, realY);
}

//drawTankAtCoordinates(x, y)
//Draws a tank at the tile position x, y.
//@param x - The x-coordinate to draw the tile at.
//@param y - The y-coordinate to draw the tile at.
function drawTankAtCoordinates(x, y) {
	var realX = x * minefieldSquareSize;
	var realY = y * minefieldSquareSize;
	
	minefieldContext.drawImage(minefieldImages["tank"], realX, realY);
}

//getTileCoordinatesFromRealCoordinates(x, y)
//Takes pixel-perfect coordinates and translates them to tile coordinates.
//@param x - The x-coordinate to translate.
//@param y - The y-coordinate to translate.
//@return The tile coordinates in array form.
function getTileCoordinatesFromRealCoordinates(x, y) {
	return [Math.floor(x / minefieldSquareSize), Math.floor(y / minefieldSquareSize)];	
}

//getTileValueString(value)
//Returns the appropriate string value for drawing images for tile values that don't correspond directly to images.
//@param value - The value of the tile to translate into a string.
//@return The string representing the image name to display for the value provided.
function getTileValueString(value) {
	var temp = value + "";
	if (value === -2) {
		temp = "mine";
	} else if (value === -1) {
		temp = "unrevealed";	
	} else if (value === 9) {
		temp = "flag";
	} else if (value === 10) {
		temp = "shovel";	
	} else if (value === 11) {
		temp = "plantflag";
	} else if (value === 12) {
		temp = "otherPlayer";
	}
	
	return temp;
}

//selectTile(evt)
//Updates control variables and the canvas to reflect a selected tile after the user clicks on the canvas.
//@param evt - The mouse click event to process for the selection.
function selectTile(evt) {	
	var pos = calculateMousePosition(evt.clientX, evt.clientY);
	var cur = getTileCoordinatesFromRealCoordinates(pos[0], pos[1]);

	for (var i = 0; i < tankCoordinates.length; i++) {
		var curTank = tankCoordinates[i];
		if (curTank[0] === cur[0]) {
			if (curTank[1] === cur[1]) {
				return;
			}
		}
	}
	
	if ((minefield[cur[0]][cur[1]] === -1) || (minefield[cur[0]][cur[1]] === 9)) {
		var prev = getAllTilesWithValue(10);
		for (var i = 0; i < prev.length; i++) {
			var toChange = prev[i];
			minefield[toChange[0]][toChange[1]] = selectedTilesPreviousValue;
			drawTileAtCoordinates(selectedTilesPreviousValue, toChange[0], toChange[1]);
		}

		prev = getAllTilesWithValue(11);
		for (var i = 0; i < prev.length; i++) {
			var toChange = prev[i];
			minefield[toChange[0]][toChange[1]] = selectedTilesPreviousValue;
			drawTileAtCoordinates(selectedTilesPreviousValue, toChange[0], toChange[1]);
		}
		
		selectedTilesPreviousValue = minefield[cur[0]][cur[1]];
		minefield[cur[0]][cur[1]] = 10;
		drawTileAtCoordinates("shovel", cur[0], cur[1]);
	} else if (minefield[cur[0]][cur[1]] === 10 && selectedTilesPreviousValue !== 9) {
		var prev = getAllTilesWithValue(10);
		for (var i = 0; i < prev.length; i++) {
			var toChange = prev[i];
			minefield[toChange[0]][toChange[1]] = selectedTilesPreviousValue;
			drawTileAtCoordinates(-1, toChange[0], toChange[1]);
		}

		prev = getAllTilesWithValue(11);
		for (var i = 0; i < prev.length; i++) {
			var toChange = prev[i];
			minefield[toChange[0]][toChange[1]] = selectedTilesPreviousValue;
			drawTileAtCoordinates(-1, toChange[0], toChange[1]);
		}
		
		minefield[cur[0]][cur[1]] = 11;
		drawTileAtCoordinates("plantflag", cur[0], cur[1]);
	}
}

//updateHover(evt)
//Clears any previously drawn hovers and draws a new hover over the currently hovered-over tile on the canvas.
//@param evt - The mouseover event to process.
function updateHover(evt) {
	if (previousHoverCoords !== null) {
		drawTileAtCoordinates(minefield[previousHoverCoords[0]][previousHoverCoords[1]], previousHoverCoords[0], previousHoverCoords[1]);
	}
	
	var pos = calculateMousePosition(evt.clientX, evt.clientY);
	var cur = getTileCoordinatesFromRealCoordinates(pos[0], pos[1]);

	for (var i = 0; i < tankCoordinates.length; i++) {
		var curTank = tankCoordinates[i];

		if (previousHoverCoords !== null) {
			if (curTank[0] === previousHoverCoords[0]) {
				if (curTank[1] === previousHoverCoords[1]) {
					drawTankAtCoordinates(previousHoverCoords[0], previousHoverCoords[1]);
				}
			}
		}

		if (curTank[0] === cur[0]) {
			if (curTank[1] === cur[1]) {
				return;
			}
		}
	} 
	
	if (minefield[cur[0]][cur[1]] == -1) {
		drawTileAtCoordinates("hover", cur[0], cur[1]);
	}
	
	previousHoverCoords = [cur[0], cur[1]];
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
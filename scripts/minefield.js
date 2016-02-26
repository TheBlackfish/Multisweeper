var squareSize = 24;
var minefield = [];
var minefieldHeight = 1;
var minefieldWidth = 1;
var minefieldContext = null;
var minefieldImages = new Array();
var minefieldImagesLoaded = 0;
var minefieldInput = null;
var minefieldInitialized = false;
var hoverFPS = Math.floor(1000/30);

var previousHoverCoords = null;
var previousSelectCoords = null;

var selectedTilesPreviousValue = -1;

/*
	Tile numbers in the minefield translate to the following:
	-2 	Revealed mine
	-1	Unrevealed
	0-8	Number of adjacent mines
	9	Flag placed
	10	Shovel selection
	11	Flag selection
*/

/*
	Initializes the necessary variables for minefield.js
*/
function initMinefield(input, h, w) {
	minefieldInput = input;
	minefieldHeight = h;
	minefieldWidth = w;
	if (!minefieldInitialized) {
		initImages();
	} else {
		updateMinefield(input);
	}		
}

function finishInitMinefield() {
	if (!minefieldInitialized) {
		minefieldInitialized = true;
		initMinefieldDisplay(minefieldInput);
		drawMinefield();
		initMinefieldInterface();
	}
}

/*
	Loads all images needed into the images array for later use.
*/
function initImages() {
	var allImages = ["mine", "unrevealed", "0", "1", "2", "3", "4", "5", "6", "7", "8", "flag", "shovel", "plantflag", "hover", "otherPlayer"];
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

/*
	Initializes the minefield on the screen using the input given.
	input - A string straight from the php response, post-cleaning for transportation s.
	h - The height of the minefield.
	w - The width of the minefield.
*/
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

/*
	Initializes various mouse functionality with the minefield.
*/
function initMinefieldInterface() {
	document.getElementById("gameArea").addEventListener('mousemove', function(evt) {
		updateHover(evt);
	}, false);
	document.getElementById("gameArea").addEventListener('click', function(evt) {
		selectTile(evt);	
	}, false);
}

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

		if (temp[xCoord][yCoord] == -1 || temp[xCoord][yCoord] == 9) {
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

/*
	Draws the entire minefield on the screen by drawing each tile individually.
*/
function drawMinefield() {
	for (var i = 0; i < minefield.length; i++) {
		for (var j = 0; j < minefield[i].length; j++) {
			drawTileAtCoordinates(minefield[i][j], i, j);	
		}
	}
}

/*
	Returns an array with the following values:
		x: The x-coordinate of the selected tile.
		y: The y-coordinate of the selected tile.
		action: The action to perform on the selected tile.
*/
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

/*
	Returns an array of all tile coordinates with the given value
*/
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

/*
	Draws the appropriate tile at the grid coordinates provided.
*/
function drawTileAtCoordinates(value, x, y) {
	var realX = x * squareSize;
	var realY = y * squareSize;
	var val = getTileValueString(value);
	
	minefieldContext.drawImage(minefieldImages[val], realX, realY);
}

/*
	Returns tile coordinates from real coordinates.
*/
function getTileCoordinatesFromRealCoordinates(x, y) {
	return [Math.floor(x / squareSize), Math.floor(y / squareSize)];	
}

/*
	Returns the string used for correct retrieval of images from the image array based on the value provided.
*/
function getTileValueString(value) {
	var temp = value;
	if (temp === -2) {
		temp = "mine";
	} else if (temp === -1) {
		temp = "unrevealed";	
	} else if (temp === 9) {
		temp = "flag";
	} else if (temp === 10) {
		temp = "shovel";	
	} else if (temp === 11) {
		temp = "plantflag";
	} else if (temp === 12) {
		temp = "otherPlayer";
	}
	
	temp = temp + "";
	
	return temp;
}

/*
	Changes the status of the clicked-on tile to "shovel" if not currently selected. If it was previously selected, it becomes "plantflag" instead.
*/
function selectTile(evt) {	
	var pos = calculateMousePosition(evt.clientX, evt.clientY);
	var cur = getTileCoordinatesFromRealCoordinates(pos[0], pos[1]);
	
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

/*
	Draws the "hover" tile over the currently hovered over tile.
*/
function updateHover(evt) {
	if (previousHoverCoords !== null) {
		drawTileAtCoordinates(minefield[previousHoverCoords[0]][previousHoverCoords[1]], previousHoverCoords[0], previousHoverCoords[1]);
	}
	
	var pos = calculateMousePosition(evt.clientX, evt.clientY);
	var cur = getTileCoordinatesFromRealCoordinates(pos[0], pos[1]);
	
	if (minefield[cur[0]][cur[1]] == -1) {
		drawTileAtCoordinates("hover", cur[0], cur[1]);
	}
	
	previousHoverCoords = [cur[0], cur[1]];
}

/*
	Corrects incorrect mouse positioning on the canvas.
*/
function calculateMousePosition(x, y) {
	var realX = x - document.getElementById("gameArea").getBoundingClientRect().left;
	var realY = y - document.getElementById("gameArea").getBoundingClientRect().top;
	return [realX, realY];
}
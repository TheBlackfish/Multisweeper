var squareSize = 24;
var minefield = [];
var minefieldWidth = 50;
var minefieldHeight = 30;
var minefieldContext = null;
var minefieldImages = new Array();
var minefieldImagesLoaded = 0;
var minefieldInput = null;
var hoverFPS = Math.floor(1000/30);

var previousHoverCoords = null;
var previousSelectCoords = null;

/*
	Tile numbers in the minefield translate to the following:
	-2 	Revealed mine
	-1	Unrevealed
	0-8	Number of adjacent mines
	9	Flag placed
	10	Selection
*/

/*
	Initializes the necessary variables for minefield.js
*/
function initMinefield(input) {
	minefieldInput = input;
	initImages();		
}

function finishInitMinefield() {
	initMinefieldDisplay(minefieldInput);
	drawMinefield();
	initMinefieldInterface();
}

/*
	Loads all images needed into the images array for later use.
*/
function initImages() {
	var allImages = ["mine", "unrevealed", "0", "1", "2", "3", "4", "5", "6", "7", "8", "flag", "selection", "hover"];
	for (var i = 0; i < allImages.length; i++) {
		var img = new Image();
		img.onload = function() {
			minefieldImagesLoaded++;
			if (minefieldImagesLoaded >= minefieldImages.length) {
				finishInitMinefield();	
			}
		};
		img.src = "./images/" + allImages[i] + ".png";
		minefieldImages[allImages[i]] = img;	
	}
}

/*
	Initializes the minefield on the screen using the input given.
	input - An array of parsed values from the server about the current game state.
			Each object in the input should be a 3-length array with the following values:
				The x-position on the grid of the tile.
				The y-position on the grid of the tile.
				The numerical value of the tile.
*/
function initMinefieldDisplay(input) {
	minefieldContext = document.getElementById("gameArea").getContext("2d");
	
	for (var i = 0; i < minefieldWidth; i++) {
		minefield.push([]);
		for (var j = 0; j < minefieldHeight; j++) {
			minefield[i].push(-1);
		}
	}
	
	for (var k = 0; k < input.length; k++) {
		var nextInput = input[k];
		if (nextInput.length === 3) {
			var x = nextInput[0];
			var y = nextInput[1];
			var value = nextInput[2];
			
			var canBeParsed = false;
			if ((x >= 0) && (x < minefieldWidth)) {
				if ((y >= 0) && (y < minefieldHeight)) {
					if ((value >= -2) && (value <= 9)) {
						canBeParsed = true;	
					}
				}
			}
			
			if (canBeParsed) {
				minefield[x][y] = value;
			}
		}
	}
}

function initMinefieldInterface() {
	document.getElementById("gameArea").addEventListener('mousemove', function(evt) {
		updateHover(evt);
	}, false);
	document.getElementById("gameArea").addEventListener('click', function(evt) {
		selectTile(evt);	
	}, false);
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
		temp = "selection";	
	}
	
	temp = temp + "";
	
	return temp;
}

function selectTile(evt) {	
	var cur = getTileCoordinatesFromRealCoordinates(evt.clientX, evt.clientY);
	
	if (minefield[cur[0]][cur[1]] == -1) {
		var prev = getAllTilesWithValue(10);
		for (var i = 0; i < prev.length; i++) {
			var toChange = prev[i];
			minefield[toChange[0]][toChange[1]] = -1;
			drawTileAtCoordinates(-1, toChange[0], toChange[1]);
		}
		
		minefield[cur[0]][cur[1]] = 10;
		drawTileAtCoordinates("selection", cur[0], cur[1]);
	}
}

function updateHover(evt) {
	if (previousHoverCoords !== null) {
		drawTileAtCoordinates(minefield[previousHoverCoords[0]][previousHoverCoords[1]], previousHoverCoords[0], previousHoverCoords[1]);
	}
	
	var cur = getTileCoordinatesFromRealCoordinates(evt.clientX, evt.clientY);
	
	if (minefield[cur[0]][cur[1]] == -1) {
		drawTileAtCoordinates("hover", cur[0], cur[1]);
	}
	
	previousHoverCoords = [cur[0], cur[1]];
}
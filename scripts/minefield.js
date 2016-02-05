var squareSize = 24;
var minefield = [];
var minefieldWidth = 50;
var minefieldHeight = 35;
var minefieldContext = null;
var images = new Array();

/*
	Tile numbers in the minefield translate to the following:
	-2 	Revealed mine
	-1	Unrevealed
	0-8	Number of adjacent mines
	9	Flag placed
*/

/*
	Loads all images needed into the images array for later use.
*/
function initImages() {
	var allImages = ["unrevealed", "flag", "0", "1", "2", "3", "4", "5", "6", "7", "8"];
	for (var i = 0; i < allImages.length; i++) {
		var img = new Image();
		img.src = "images/" + allImages[i] + ".png";
		images[allImages[i]] = img;	
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
	for (var i = 0; i < minefieldWidth; i++) {
		minefield.push([]);
		for (var j = 0; j < minefieldHeight; j++) {
			minefield[i].push(0);
		}
	}
	
	for (var k = 0; k < input.length; k++) {
		var nextInput = input[k];
		if (k.length === 3) {
			var x = k[0];
			var y = k[1];
			var value = k[2];
			
			var canBeParsed = false;
			if ((x >= 0) && (x < minefieldWidth)) {
				if ((y >= 0) && (y < minefieldHeight)) {
					if ((value >= -1) && (value <= 9)) {
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
	Draws the appropriate tile at the grid coordinates provided.
*/
function drawTileAtCoordinates(value, x, y) {
	var realX = x * squareSize;
	var realY = y * squareSize;
	
}
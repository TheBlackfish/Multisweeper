/*
	minefieldGraphics.js

	This file controls all graphics-related functionality of the canvas/game area.

	The main graphics control now is a combined overlay/underlay system. The underlay shows the basic terrain of a tile, while the overlay shows relevant information about that tile to the players. This is to 1) allow for more stylistic changes such as terrain and B) make it easier to switch out things such as tanks and other players.
*/

//overlayImages [array]
//Contains all image files for overlay images.
var overlayImages = [];

//underlayImages [array]
//Contains all image files for underlay images.
var underlayImages = [];

//minefieldContext [GraphicsContext]
//The main context for drawing onto the canvas.
var minefieldContext = null;

//minefieldImagesLoaded [int]
//The number of image files successfully loaded.
var minefieldImagesLoaded = 0;

//minefieldTileSize [int]
//A control variable for tile sizes.
var minefieldTileSize = 30;

//minefieldGraphicsInitialized [boolean]
//Whether or not the graphics engine has been fully initialized.
var minefieldGraphicsInitialized = false;

//initMinefieldGraphics()
//Initializes the graphics appropriately.
function initMinefieldGraphics() {
	minefieldContext = document.getElementById("gameArea").getContext("2d");
	if (minefieldImagesLoaded === 0) {
		initMinefieldImages();
	}
}

//initMinefieldImages()
//Loads all of the images needed.
function initMinefieldImages() {
	var overlayFiles = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "flag", "mine", "otherPlayer", "plantFlag", "shovel", "friendlyTank", "enemyTank", "wreck", "layTrap", "proximityMine", "radioNest", "ballista"];
	var underlayFiles = ["revealed", "unrevealed"];
	var targetNum = overlayFiles.length + underlayFiles.length;
	for (var i = 0; i < overlayFiles.length; i++) {
		var img = new Image();
		img.onload = function() {
			minefieldImagesLoaded++;
			if (minefieldImagesLoaded >= targetNum) {
				minefieldGraphicsInitialized = true;
			}
		};
		img.src = "./images/overlays/" + overlayFiles[i] + ".png";
		overlayImages[overlayFiles[i]] = img;	
	}

	for (var i = 0; i < underlayFiles.length; i++) {
		var img = new Image();
		img.onload = function() {
			minefieldImagesLoaded++;
			if (minefieldImagesLoaded >= targetNum) {
				minefieldGraphicsInitialized = true;
			}
		};
		img.src = "./images/underlays/" + underlayFiles[i] + ".png";
		underlayImages[underlayFiles[i]] = img;	
	}
}

//drawMinefield
//Draws the minefield to the canvas.
function drawMinefield() {
	if (minefieldGraphicsInitialized) {
		var canvas = document.getElementById("gameArea");
		canvas.width = minefieldWidth * minefieldTileSize;
		canvas.height = minefieldHeight * minefieldTileSize;

		var field = getMinefield();
		if (field !== null) {
			for (var i = 0; i < field.length; i++) {
				for (var j = 0; j < field[i].length; j++) {
					drawTileAtCoordinates(i, j);	
				}
			}
			return;
		}
	}
	
	setTimeout(function() {
		drawMinefield();
	}, 500);
}

//drawTileAtCoordinates(x, y)
//Draws the appropriate image for the value specified at the tile position x, y.
//@param x - The x-coordinate to draw the tile at.
//@param y - The y-coordinate to draw the tile at.
function drawTileAtCoordinates(x, y) {
	if (minefieldGraphicsInitialized) {
		var realX = x * minefieldTileSize;
		var realY = y * minefieldTileSize;
		
		var underlay = selectUnderlayForTile(x, y);
		if (underlay !== null) {
			minefieldContext.drawImage(underlay, realX, realY);
		}

		var overlay = selectOverlayForTile(x, y);
		if (overlay !== null) {
			minefieldContext.drawImage(overlay, realX, realY);
		}
	}
}

//drawTileAtCoordinatesOverrideOverlay(x, y, override)
//Draws the appropriate image for the value specified at the tile position x, y, using the specified overlay instead of the normal one.
//@param x - The x-coordinate to draw the tile at.
//@param y - The y-coordinate to draw the tile at.
//@param override - The overlay image to draw on the tile instead of the normal overlay.
function drawTileAtCoordinatesOverrideOverlay(x, y, override) {
	if (minefieldGraphicsInitialized) {
		if (override in overlayImages) {
			var realX = x * minefieldTileSize;
			var realY = y * minefieldTileSize;
		
			var underlay = selectUnderlayForTile(x, y);
			if (underlay !== null) {
				minefieldContext.drawImage(underlay, realX, realY);
			}
			
			minefieldContext.drawImage(overlayImages[override], realX, realY);
		}
	}
}

//selectOverlayForTile(x, y)
//Returns the appropriate overlay for the tile specified.
//@param x - The x-coordinate of the tile specified.
//@param y - The y-coordinate of the tile specified.
//@return The image file that is the correct overlay for the tile, or null if no overlay specified.
function selectOverlayForTile(x, y) {
	var s = getSelectedActionArray();

	if (s !== null) {
		if (s["x"] === x && s["y"] === y) {
			if (s["action"] === 0) {
				return overlayImages["shovel"];
			} else if (s["action"] === 1) {
				return overlayImages["plantFlag"];
			} else if (s["action"] === 2) {
				return overlayImages["layTrap"];
			}
		}
	}

	var ft = getFriendlyTanks();

	for (var i = 0; i < ft.length; i++) {
		if (ft[i][0] == x && ft[i][1] == y) {
			return overlayImages["friendlyTank"];
		}
	}

	var et = getEnemyTanks();

	for (var i = 0; i < et.length; i++) {
		if (et[i][0] == x && et[i][1] == y) {
			return overlayImages["enemyTank"];
		}
	}

	var tr = getTraps();

	for (var i = 0; i < tr.length; i++) {
		if (tr[i][1] == x && tr[i][2] == y) {
			if (tr[i][0] == 0) {
				return overlayImages["proximityMine"];
			} else if (tr[i][0] == 1) {
				return overlayImages["radioNest"];
			} else if (tr[i][0] == 2) {
				return overlayImages["ballista"];
			}
		}
	}

	var o = getOtherPlayers();

	for (var i = 0; i < o.length; i++) {
		if (o[i][0] == x && o[i][1] == y) {
			return overlayImages["otherPlayer"];
		}
	}

	var val = getTileValue(x, y);

	if (val === -1) {
		return null;
	} else if (val === -2) {
		return overlayImages["mine"];
	} else if (val === 9) {
		return overlayImages["flag"];
	} else if (val === 10) {
		return overlayImages["wreck"];
	} else {
		return overlayImages[""+val];
	}

	return null;
}

//selectUnderlayForTile(x, y)
//Returns the appropriate underlay for the tile specified.
//@param x - The x-coordinate of the tile specified.
//@param y - The y-coordinate of the tile specified.
//@return The image file that is the correct underlay for the tile.
function selectUnderlayForTile(x, y) {
	var val = getTileValue(x, y);
	if (val === -1 || val === 9) {
		return underlayImages["unrevealed"];
	} else {
		return underlayImages["revealed"];
	}
}
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

//ghostContext [GraphicsContext]
//The duplicator context for scaling images appropriately.
var ghostContext = null;

//minefieldImagesLoaded [int]
//The number of image files successfully loaded.
var minefieldImagesLoaded = 0;

//minefieldTileSize [int]
//A control variable for tile sizes.
var minefieldTileSize = 30;

//minefieldTileScale [float]
//The scale to draw the tiles onto the canvas at.
var minefieldTileScale = 1;

//interpolatedTileSize [int]
//The size, in pixels, to draw tiles onto the ghost context.
var interpolatedTileSize = -1;

//finalTileSize [int]
//The size, in pixels, of the tiles set to the minefieldTileScale variable.
var finalTileSize = -1;

//minefieldTileVerticalOffset [int]
//The vertical offset to draw tiles at.
var minefieldTileVerticalOffset = 0;

//minefieldTileHorizontalOffset [int]
//The current scrolling offset to draw tiles at along the x-axis.
var minefieldTileHorizontalOffset = 0;

//minefieldTileFixedHorizontalOffset [int]
//If the minefield to draw is smaller than the canvas, this sets it to a fixed point along the x-axis.
var minefieldTileFixedHorizontalOffset = 0;

//minefieldFPS [double]
//The time (in milliseconds) between each draw of the minefield.
var minefieldFPS = 12/1000;

//minefieldFPSSet [bool]
//If true, this will draw the minefield whenever minefieldFPS milliseconds have passed.
var minefieldFPSSet = false;

//minefieldShouldDraw [bool]
//The control variable of if the minefield should draw or not.
var minefieldShouldDraw = true;

//minefieldGraphicsInitialized [boolean]
//Whether or not the graphics engine has been fully initialized.
var minefieldGraphicsInitialized = false;

//initMinefieldGraphics()
//Initializes the graphics appropriately.
function initMinefieldGraphics() {
	minefieldContext = document.getElementById("gameArea").getContext("2d");

	var ghostCanvas = document.createElement('canvas');
	ghostCanvas.width = document.getElementById("gameArea").width;
	ghostCanvas.height = document.getElementById("gameArea").height;
	ghostContext = ghostCanvas.getContext("2d");

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

//drawMinefieldWithResize()
//Draws the minefield to the canvas while also resizing the tile sizes used to draw tiles.
function drawMinefieldWithResize() {
	if (minefieldGraphicsInitialized) {
		var canvas = document.getElementById("gameArea");
		canvas.height = canvas.parentElement.clientHeight;
		minefieldTileScale = canvas.height / (minefieldHeight * minefieldTileSize);
		minefieldTileScale = parseFloat(minefieldTileScale.toFixed(3));

		var steppingScale = (1 + minefieldTileScale) / 2;
		interpolatedTileSize = Math.floor(minefieldTileSize * steppingScale);
		finalTileSize = Math.floor(minefieldTileSize * minefieldTileScale);

		minefieldTileVerticalOffset = (canvas.height - (finalTileSize * minefieldHeight))/2;

		canvas.width = document.getElementById("mainArea").clientWidth;

		minefieldTileFixedHorizontalOffset = (canvas.width - (minefieldWidth * finalTileSize))/2;
		if (minefieldTileFixedHorizontalOffset <= 0) {
			minefieldTileFixedHorizontalOffset = 0;
			var maxOffset = document.getElementById("mainArea").clientWidth - (finalTileSize * minefieldWidth);
			if (minefieldTileHorizontalOffset < maxOffset) {
				minefieldTileHorizontalOffset = maxOffset;
			}
		} else {
			minefieldTileHorizontalOffset = 0;
		}

		minefieldShouldDraw = true;

		drawMinefield();
	}
	
	setTimeout(function() {
		drawMinefieldWithResize();
	}, 500);
}

//drawMinefield()
//Draws the minefield to the canvas using the current tile size settings and offsets.
function drawMinefield() {
	if (minefieldGraphicsInitialized) {
		if (minefieldShouldDraw) {
			var field = getMinefield();
			if (field !== null) {
				for (var i = 0; i < field.length; i++) {
					for (var j = 0; j < field[i].length; j++) {
						drawTileAtCoordinates(i, j);	
					}
				}
				hideLoading();

				if (!minefieldFPSSet) {
					setInterval(function() {
						drawMinefield();
					}, minefieldFPS);
					minefieldFPSSet = true;
				}
				return;
			}
		} else {
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
		var realX = (x * finalTileSize) + minefieldTileHorizontalOffset + minefieldTileFixedHorizontalOffset;
		var realY = (y * finalTileSize) + minefieldTileVerticalOffset;
		
		var underlay = selectUnderlayForTile(x, y);
		if (underlay !== null) {
			ghostContext.drawImage(underlay, 0, 0, interpolatedTileSize, interpolatedTileSize);
			minefieldContext.drawImage(underlay, 0, 0, interpolatedTileSize, interpolatedTileSize, realX, realY, finalTileSize, finalTileSize);
		}

		var overlay = selectOverlayForTile(x, y);
		if (overlay !== null) {
			ghostContext.drawImage(overlay, 0, 0, interpolatedTileSize, interpolatedTileSize);
			minefieldContext.drawImage(overlay, 0, 0, interpolatedTileSize, interpolatedTileSize, realX, realY, finalTileSize, finalTileSize);
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
			var realX = (x * finalTileSize) + minefieldTileHorizontalOffset + minefieldTileFixedHorizontalOffset;
			var realY = (y * finalTileSize) + minefieldTileVerticalOffset;
		
			var underlay = selectUnderlayForTile(x, y);
			if (underlay !== null) {
				ghostContext.drawImage(underlay, 0, 0, interpolatedTileSize, interpolatedTileSize);
				minefieldContext.drawImage(underlay, 0, 0, interpolatedTileSize, interpolatedTileSize, realX, realY, finalTileSize, finalTileSize);
			}
			
			ghostContext.drawImage(overlayImages[override], 0, 0, interpolatedTileSize, interpolatedTileSize);
			minefieldContext.drawImage(overlayImages[override], 0, 0, interpolatedTileSize, interpolatedTileSize, realX, realY, finalTileSize, finalTileSize);
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

//adjustHorizontalOffset(offset)
//Alters the horizontal offset of the minefield on the screen by the amount given in offset.
//@param offset (int) The amount to alter the horizontal offset by.
function adjustHorizontalOffset(offset) {
	if (minefieldTileFixedHorizontalOffset === 0) {
		var newOffset = minefieldTileHorizontalOffset + offset;
		if (newOffset > 0) {
			newOffset = 0;
		} else {
			var maxOffset = document.getElementById("mainArea").clientWidth - (finalTileSize * minefieldWidth);
			if (newOffset < maxOffset) {
				newOffset = maxOffset;
			}
		}
		if (minefieldTileHorizontalOffset !== newOffset) {
			minefieldTileHorizontalOffset = newOffset;
			minefieldShouldDraw = true;
		}
	}
}
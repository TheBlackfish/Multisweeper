var overlayImages = [];
var underlayImages = [];

var minefieldContext = null;
var minefieldImagesLoaded = 0;
var minefieldTileSize = 30;
var minefieldGraphicsInitialized = false;

function initMinefieldGraphics() {
	minefieldContext = document.getElementById("gameArea").getContext("2d");
	if (minefieldImagesLoaded === 0) {
		initMinefieldImages();
	}
}

function initMinefieldImages() {
	var overlayFiles = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "flag", "mine", "otherPlayer", "plantFlag", "shovel", "tank"];
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
//@param value - The minefield tile value to draw at the specified tile position.
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
			minefieldContext.drawImage(selectOverlayForTile(x, y), realX, realY);
		}
	}
}

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

function selectOverlayForTile(x, y) {
	var s = getSelectedActionArray();

	if (s !== null) {
		if (s["x"] === x && s["y"] === y) {
			if (s["action"] === 0) {
				return overlayImages["shovel"];
			} else if (s["action"] === 1) {
				return overlayImages["plantFlag"];
			}
		}
	}

	var t = getTanks();

	for (var i = 0; i < t.length; i++) {
		if (t[0] == x && t[1] == y) {
			return overlayImages["tank"];
		}
	}

	var o = getOtherPlayers();

	for (var i = 0; i < o.length; i++) {
		if (o[0] == x && o[1] == y) {
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
	} else {
		return overlayImages[""+val];
	}

	return null;
}

function selectUnderlayForTile(x, y) {
	var val = getTileValue(x, y);
	if (val === -1 || val > 8) {
		return underlayImages["unrevealed"];
	} else {
		return underlayImages["revealed"];
	}
}
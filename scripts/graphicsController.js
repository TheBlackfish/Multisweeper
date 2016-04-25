/*
	GraphicsController.js

	This file contains all functionality for CSS style and graphics not immediately related to the minefield canvas.
*/

function hideLoading() {
	if (document.getElementById("loadingScreen").className.lastIndexOf("doneLoading") === -1) {
		document.getElementById("loadingScreen").className += " doneLoading";
	}
}
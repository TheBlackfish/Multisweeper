/*
	GraphicsController.js

	This file contains all functionality for CSS style and graphics not immediately related to the minefield canvas.
*/

//hideLoading()
//Hides the loading screen.
function hideLoading() {
	if (document.getElementById("loadingScreen").className.lastIndexOf("doneLoading") === -1) {
		document.getElementById("loadingScreen").className += " doneLoading";
	}
}

//setLoadingIconStatus(status)
//Shows or hides the loading icon based on the status given.
//@param status (Boolean) Whether or not to show the loading icon.
function setLoadingIconStatus(status) {
	var icon = document.getElementById("loadingIcon");
	if (status) {
		if (!icon.hasAttribute("style")) {
			icon.setAttribute("style", "");
		}
		icon.style.top = "10px";
	} else {
		icon.removeAttribute("style");
	}
}
/*
	BarController.js

	This file controls functionality relating to the UI bar.
*/

//initBar()
//Initializes the UI bar.
function initBar() {
	updateBar();
}

//updateBar()
//Updates the bar to fit the window and resize itself.
function updateBar() {
	var targetHeight = Math.floor(window.innerHeight / 4);
	if (targetHeight < 200) {
		targetHeight = 200;
	}
	document.getElementById("bottomBar").style.height = targetHeight + "px";
	document.getElementById("bottomBar").style.bottom = -targetHeight + "px";

	updateOptions();
}

//updateOptions()
//Updates which tabs are visible are which are not.
function updateOptions() {
	if (getPlayerName() === null) {
		setTabVisible("loginTab", true);
		setTabVisible("ordersTab", false);
		setTabVisible("unitTab", false);
	} else {
		setTabVisible("loginTab", false);
		setTabVisible("ordersTab", true);
		setTabVisible("unitTab", true);
	}

	setTabHeaderPositions();
}

//setTabHeaderPositions()
//Sets the distance from the right-hand side of the screen for each visible tab such that they appear to be offset in an orderly manner.
function setTabHeaderPositions() {
	var allHeaders = document.getElementsByClassName("tabHeader");
	var rightDistance = 0;
	for (var i = allHeaders.length - 1; i >= 0; i--) {
		if (allHeaders[i].parentElement.className.lastIndexOf("inactive") === -1) {
			allHeaders[i].style.right = rightDistance+"px";
			rightDistance += allHeaders[i].offsetWidth;
		} else {
			allHeaders[i].style.right = 0;
		}
	}
}

//setTabVisible(tabName, state)
//Takes the tab given and sets it to the state specified.
//@param tabName - The name of the tab to alter.
//@param state - The boolean value of the state to set the tab to.
function setTabVisible(tabName, state) {
	var cur = document.getElementById(tabName);
	if (cur !== null) {
		if (state) {
			cur.className = cur.className.replace(/inactive/g, "");
		} else {
			cur.className += " inactive";
		}
	}
}

//toggleTab(headerElement)
//Toggles the visibility of a tab given its header element.
//@param headerElement - The element that comprises the header of the tab to alter.
function toggleTab(headerElement) {
	var parent = headerElement.parentElement;
	if (parent.className.lastIndexOf("extended") !== -1) {
		parent.className = parent.className.replace(/extended/g, "");
	} else {
		var elems = document.getElementsByClassName("extended");
		while (elems.length > 0) {
			elems[0].className = elems[0].className.replace(/extended/g, "");
		}
		parent.className += " extended";
	}
}
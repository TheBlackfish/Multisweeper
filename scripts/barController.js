/*
	BarController.js

	This file controls functionality relating to the UI bar.
*/

//initBar()
//Initializes the UI bar.
function initBar() {
	initBarIcons();
	updateBar();	
}

function initBarIcons() {
	var allIcons = ["traptools", "submitButton", "submissionStatusButton"];

	for (var i = 0; i < allIcons.length; i++) {
		var newImg = "<img id='" + allIcons[i] + "' src='images/bar_icons/" + allIcons[i] + ".png' class='barIcon'>";
		document.getElementById("bottomBar").innerHTML += newImg;
	}

	var images = document.getElementsByClassName("barIcon");
	for (var i = 0; i < images.length; i++) {
		images[i].style.top = "-" + images[i].clientHeight + ".px";
		if (images[i].id === "submitButton") {
			images[i].onclick = submitAction;
		} else if (images[i].id === "submissionStatusButton") {
			images[i].src = "images/bar_icons/submissionStatusButton_waiting.png";
		}
	}
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
	updateIcons();
}

function updateIcons() {
	var leftSide = 20;
	var statusButton = document.getElementById("submissionStatusButton");
	var submitButton = document.getElementById("submitButton");
	var toolsIcon = document.getElementById("traptools");

	if (allowedActions.lastIndexOf(2) > -1) {
		if (!toolsIcon.hasAttribute("style")) {
			toolsIcon.setAttribute("style", "");
		}
		toolsIcon.style.left = leftSide+"px";
		toolsIcon.style.top = "-" + toolsIcon.clientHeight + "px";
		leftSide += toolsIcon.width + 20;
	} else {
		toolsIcon.removeAttribute("style");
	}

	if (allowedActions.length > 0) {
		if (!submitButton.hasAttribute("style")) {
			submitButton.setAttribute("style", "");
		}
		submitButton.style.left = leftSide+"px";
		submitButton.style.top = "-" + submitButton.clientHeight + "px";
		leftSide += submitButton.width + 20;

		if (!statusButton.hasAttribute("style")) {
			statusButton.setAttribute("style", "");
		}
		statusButton.style.left = leftSide+"px";
		statusButton.style.top = "-" + statusButton.clientHeight + "px";
		var img = "images/bar_icons/submissionStatusButton_";
		switch (playerActionStatus) {
			case -1:
				img += "bad";
				break;
			case 1:
				img += "good";
				break;
			default:
				img += "waiting";
		}
		statusButton.src = img+".png";
		leftSide += statusButton.width + 20;
	} else {
		statusButton.removeAttribute("style");
		submitButton.removeAttribute("style");
	}
}

//updateOptions()
//Updates which tabs are visible are which are not.
function updateOptions() {
	if (getPlayerName() === null) {
		setTabVisible("loginTab", true);
		setTabVisible("unitTab", false);
		setTabVisible("chatTab", false);
	} else {
		setTabVisible("loginTab", false);
		setTabVisible("unitTab", true);
		setTabVisible("chatTab", true);
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
/*
	AutoController.js

	This file contains everything related to the automatic updates that occur every few seconds on the page, including controlling the timers and calling various functionalities.
*/

//timerPeriod [integer]
//This is the amount of time (in milliseconds) between each tick of the timer.
var timerPeriod = 1000;

//timerMax [integer]
//This is the amount of time (in seconds)
var timerMax = 10;

//timerCurrent [integer]
//This is the current amount of time (in seconds) until the next time the callback function is called.
var timerCurrent = timerMax;

//initQueryTimer()
//This function initializes the timer with the appropriate variables and functions.
function initQueryTimer() {
	window.setInterval( timerCallback, timerPeriod ); 
}

//forceTimerToTime(time)
//This function sets the current timer to the number of seconds specified
//@param time - The number of seconds to set the timer to.
function forceTimerToTime(time) {
	timerCurrent = time + 1;
}

//timerCallback()
//This function updates the timer by lowering the number of seconds remaining. Then, if the timer is at 0 seconds, various automatic callback functions are called.
function timerCallback() {
	timerCurrent--;

	if (timerCurrent == 0) {
		document.getElementById("timerText").innerHTML = "Updating...";

		getMinefieldData();

		timerCurrent = timerMax;
	}

	document.getElementById("timerText").innerHTML = "Updating in " + timerCurrent;
}
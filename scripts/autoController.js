var timerPeriod = 1000;
var timerMax = 10;
var timerCurrent = timerMax;

function initQueryTimer() {
	window.setInterval( timerCallback, timerPeriod ); 
}

function timerCallback() {
	timerCurrent--;

	if (timerCurrent == 0) {
		document.getElementById("timerText").innerHTML = "Updating...";

		getMinefieldData();

		timerCurrent = timerMax;
	}

	document.getElementById("timerText").innerHTML = timerCurrent;
}
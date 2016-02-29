function initInteractions() {
	document.onkeypress = handleInteractions;
}

function handleInteractions(e) {
	console.log(e.key);
	if (e.key == "Enter") {
		if (getPlayerID === null) {
			attemptLogIn();
		} else {
			submitAction();
		}
	}
} 
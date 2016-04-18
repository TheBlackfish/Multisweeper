/*
	InteractionController.js

	This file contains all functionality relating to keyboard functionality.

	Currently, the keyboard interactions are as follows:

		ENTER - If the player is not logged in, attempt to log in. Otherwise, submit the current action on the minefield.
*/

//initInteractions
//Initializes keyboard interactions.
function initInteractions() {
	document.onkeypress = handleInteractions;
}

//handleInteractions(e)
//Handles key presses and call specific functions based on what key was pressed.
//@param e - The 'onkeypress' event to process.
function handleInteractions(e) {
	if (e.key == "Enter") {
		if (getPlayerName() === null) {
			attemptLogIn();
		} else if (document.getElementById("chatEntry").value.length > 0) {
			attemptChatSubmit(e);
		} else {
			submitAction();
		}
	}
} 
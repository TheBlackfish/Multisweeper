# Sweep Elite
An HTML5 game where multiple players are attempting to clear an ever-growing minefield for friendly forces to go through, while placing traps to prevent enemy forces from getting to the home base.

# Gameplay
The game works off of a rough turn-based structure in that players can see what everyone else is doing, but the actions only get resolved roughly 5 seconds after everyone has submitted their actions.
Players can clear a tile, place a flag, or place a trap if they have one available.

# Technology
The game utilizes websockets to carry out what is essentially a MVC with the server controlling the model and the webpage controlling the view.
The server is PHP and MySQL, with the actual server instance based off of PHP Websockets by ghedipunk. (URL: https://github.com/ghedipunk/PHP-Websockets)
The client is HTML and Javascript utilizing lots of canvas functionality.
Aside from the PHP Websockets server class, no external libraries are needed.

# Current Status
The game is on sort of a hiatus due to lack of a real 'fun factor' and a lack of a web-server to run it.
The 'fun factor' is definitely the bigger issue. Despite being a massively-multiplayer game, there exists no reason for massive amounts of people to play it.
In the future, I was hoping to add the functionality to allow for private games. However, playtesting reveals that that might be the only way to actually enjoy a game like this.
Honestly, I am hoping to try a massive rewrite of the whole game to accomodate such a structure. No promises, though.

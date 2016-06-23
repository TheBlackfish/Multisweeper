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

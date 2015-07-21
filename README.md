# CSGO Win Big

### What is CSGO Win Big?
CSGO Win Big is a Counter-Strike: Global Offensive jackpot skin betting website, created by me, Jordan Turley. It is hosted through Bluehost at http://csgowinbig.jordanturley.com/.

### How does CSGO Win Big work?
This is the repository for the website for CSGO Win Big. It is written in HTML/CSS/JavaScript for the client-side, and PHP for the server-side.  
We are also making use of the following libraries/frameworks:
* jQuery - http://jquery.com/
* Bootstrap - http://getbootstrap.com/
* Underscore.js - http://underscorejs.org/
* SweetAlert - http://t4t5.github.io/sweetalert/
* SteamAuthentication - https://github.com/SmItH197/SteamAuthentication

In addition to the website, we are also making use of a custom version of [Jessecar96's Steam Bot](https://github.com/Jessecar96/SteamBot), which can be found [here](https://github.com/ztizzlegaming/SteamBot), and is written in C#.

### How to setup this project for my own use?
If you would like to setup this project for your own project, there are a couple of steps you must follow:

* In src/php/default.php, you must configure your own database credentials. The way I have it setup is I have a file outside of the web root with my passwords in it, which I read and get the password for the database from, instead of writing my password directly in the code. Then, import database-config.sql to your MySQL database.  If you want to use a database other than MySQL, you will have to set it up on your own.
* Because this project utilizes many aspects of the Steam API, you must have a Steam API key of your own. I am actually making use of 3 different API keys: one for logging in through Steam, one for getting the users' information of the chat, and one for getting the users' information of items deposited. I believe this could be done with only one API key, but I wanted to use a different key for each of these things to keep everything organized and separated. Again, like the database password, my API keys are stored outside of the web root for safety, so you will have to change that.

### How can I contribute to this project?
Please read [CONTRIBUTING.md](https://github.com/ztizzlegaming/csgo-win-big/blob/master/CONTRIBUTING.md).
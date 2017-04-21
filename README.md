# CSGO Win Big

### What is this?
CSGO Win Big is a Counter-Strike: Global Offensive jackpot skin betting website, created by me, Jordan Turley. It is no longer hosted, but you can view images of the site [here](http://imgur.com/a/PQDRs), or download and inspect the code yourself.

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

* In src/php/default.php, you must configure your own database credentials. The way I have it setup is I have a file outside of the web root with my passwords in it, called 'passwords.txt', which I read and get the password for the database from, instead of writing my password directly in the code. Then, import database-config.sql to your MySQL database.  If you want to use a database other than MySQL, you will have to set it up on your own.
* Also, because you login through Steam for this site, you must have a Steam API Key. You can request a key for yourself [here](https://steamcommunity.com/dev/apikey). Like the database password, I also have this key stored outside of the web root in passwords.txt.
* You will also have to put in your own website url and database stuff in some places, instead of mine. These places are:
  * [Lines 5, 6, and 7 of default.php](https://github.com/ztizzlegaming/CSGOWinBig/blob/master/src/php/default.php#L5)
  * [Line 14 of settings.php](https://github.com/ztizzlegaming/CSGOWinBig/blob/master/src/php/SteamAuthentication/steamauth/settings.php#L14)
  * [Line 30 of support-ticket.php](https://github.com/ztizzlegaming/CSGOWinBig/blob/master/src/php/support-ticket.php#L30)
  * [Line 6 of bot-withdraw.php](https://github.com/ztizzlegaming/CSGOWinBig/blob/master/src/php/bot-withdraw.php#L6). For this, you will need to enter the 64bit ID of your Steam bot. You can find this in the bot's profile url, or on websites such as http://steamrep.com.
  * [Lines 54](https://github.com/ztizzlegaming/CSGOWinBig/blob/master/src/index.html#L54) and [120](https://github.com/ztizzlegaming/CSGOWinBig/blob/master/src/index.html#L120) of index.html, [line 106 of support.html](https://github.com/ztizzlegaming/CSGOWinBig/blob/master/src/support.html#L106), [line 92 of donations.html](https://github.com/ztizzlegaming/CSGOWinBig/blob/master/src/donations.html#L92), and [line 158 of prices.html](https://github.com/ztizzlegaming/CSGOWinBig/blob/master/src/prices.html#L158). Here, you must modify the sign in url to have your own website's url. You must change where it says 'openid.return_to=' and 'openid.realm=' to be your own website's url.
  * [Line 141 of script.js](https://github.com/ztizzlegaming/CSGOWinBig/blob/master/src/script.js#L141). Here, you must put in the trade url of your own bot.
* One last thing, the site assumes that there will always be chat messages, so you have to manually insert one chat message into the chat database table.
* Here is an example of my passwords.txt:  
{"default-password":"YOUR DATABASE PASSWORD","steamAPIKey":"YOUR STEAM API KEY"}

### How can I contribute to this project?
Please read [CONTRIBUTING.md](https://github.com/ztizzlegaming/csgo-win-big/blob/master/CONTRIBUTING.md).

### How can I donate to this project?
Donations are not necessary, but are greatly appreciated and help us out a lot. There are four ways you can donate:
* Send a trade offer [here](https://steamcommunity.com/tradeoffer/new/?partner=60354605&token=gxN5u_IK) with skin donations.
* Send actual money through PayPal  [here](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SKL49QJVZGKXC).
* Send Bitcoin to this address: 1GqszRekcjuUTARfXiroMnPoytRJWdk66A
* Send Dogecoin to this address: DMWd9PLkDyQqEaQnoCWHi8EFDv2biD4AcS

If you are sending skins or money through PayPal, and would like to be recognized for your donation on our [donations page](http://www.csgowinbig.com/donations.html), please add your name to the trade offer message or field on PayPal. 

//Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

//The most recent ID for the chat, pot count, and last game ID
var localMostRecentID = 0, potCount = -1, mLastGameID = 0;

//Whether or not the call of update() is the first call
var firstUpdate = true;

//The most recent ID for the user sending the chat.
//Used if they want to send multiple messages before the chat refreshes.
var localChatIDForColor = 0;

//Whether or not the user is logged in
var loggedIn = false;

//The user's information
var mUserInfo = null;

var mTradeToken = null;

var updateWaitTime = 1000;

$(function () {
	$.getJSON('php/login-status.php', function (jsonObj) {
		handleJsonResponse(jsonObj, function (data) {
			var loginStatus = data['loginStatus'];

			mUserInfo = data['userInfo'];

			if (loginStatus === 1) {
				//They are logged in
				$('.logout').css('display', 'block');
				$('#chat-input').css('display', 'block');
				$('#deposit-main').css('display', 'block');

				loggedIn = true;

				mTradeToken = data['tradeToken'];

			} else {
				//They are not logged in
				$('.login').css('display', 'block');
				$('#login-main').css('display', 'block');
			}

			$('#loading-menubar').css('display', 'none');

			//Check if the page is the home page
			var page = location.pathname.substring(location.pathname.lastIndexOf("/") + 1);
			if (page === '' || page === 'index.html') {
				//Get info and put it on the home page
				var info = data['info'];
				var totalGamesPlayed = info['totalGamesPlayed'],
					totalMoneyWon = info['totalMoneyWon'],
					biggestPotEver = info['biggestPotEver'];

				totalMoneyWon = getFormattedPrice(totalMoneyWon);

				if (biggestPotEver === null) {
					biggestPotEver = 'N/A';
				} else {
					biggestPotEver = getFormattedPrice(biggestPotEver);
				}

				$('#total-games-played').text(totalGamesPlayed);
				$('#total-money-won').text(totalMoneyWon);
				$('#biggest-pot-ever').text(biggestPotEver);

				//Start updating
				setTimeout(update, 200);
			}
		});
	});

	$('#chat-input').on('keydown', function (event) {
		if (event.which === 13 || loggedIn === false) {
			var text = this.value;

			if (text.length === 0) {
				return;
			}

			localChatIDForColor++;

			var date = getFormattedDate(), time = getFormattedTime();

			var msgObj = {
				id: localChatIDForColor,
				text: text,
				date: date,
				time: time,
				steamUserInfo: mUserInfo
			};

			var str = generateChatMsgStr(msgObj);

			$('#chatmessages').append(str);
			$('#chatmessages').scrollTop($('#chatmessages')[0].scrollHeight);

			$('#chat-input').val('');

			$.post('php/send-chat-message.php', {text: text});
		}
	});

	$('.deposit-btn').on('click', function () {
		if (mTradeToken === null) {
			var swalText = 'Please enter your trade url for payouts.<br><br>NOTE: Make sure your url is correct; otherwise, you will not receive your winnings. You can find your trade url <a href="http://steamcommunity.com/id/me/tradeoffers/privacy" target="_blank">here</a>. Also, be sure to make your inventory public; this can be done <a href="http://steamcommunity.com/id/me/edit/settings/" target="_blank">here</a>.';
			swal({
				title: 'Trade URL',
				text: swalText,
				type: 'input',
				showCancelButton: true,
				closeOnConfirm: false,
				showLoaderOnConfirm: true,
				html: true,
				inputPlaceholder: 'trade url'
			}, function (inputValue) {
				if (inputValue === false) {
					return false;
				}

				if (inputValue.length === 0) {
					swal.showInputError('You must enter your trade url.');
					return false;
				}

				$.post('php/save-trade-token.php', {tradeUrl: inputValue}, function (jsonObj) {
					handleJsonResponse(jsonObj, function (data) {
						if (data['valid'] === 0) {
							swal.showInputError(data['errMsg']);
							return false;
						} else {
							mTradeToken = data['tradeToken'];
							successMsg('Your trade url was successfully saved. Click OK then Deposit Skins again to deposit.');
						}
					});
				}, 'json');
			});
		} else {
			$('<a>').attr('href', 'https://steamcommunity.com/tradeoffer/new/?partner=278478260&token=s8MZ56C5').attr('target', '_blank')[0].click();
		}
	});

	$('#support-btn').on('click', function () {
		window.location = 'support.html';
	});

	$('#donate-btn').on('click', function () {
		window.location = 'donations.html';
	});

	$('#history-btn').on('click', function () {
		window.location = 'history.html';
	});

	$('#logout-btn').on('click', function () {
		window.location = 'php/SteamAuthentication/steamauth/logout.php';
	});

	$('#change-trade-url-btn').on('click', function () {
		if (!loggedIn) {
			return;
		}

		swal({
			title: 'Change Trade URL',
			text: 'Enter your updated trade url here.<br><br>You can find your trade url <a href="http://steamcommunity.com/id/me/tradeoffers/privacy" target="_blank">here</a>.',
			type: 'input',
			showCancelButton: true,
			closeOnConfirm: false,
			showLoaderOnConfirm: true,
			html: true,
			inputPlaceholder: 'trade url'
		}, function (inputValue) {
			if (inputValue === false) {
				return false;
			}

			if (inputValue.length === 0) {
				swal.showInputError('You must enter your trade url.');
				return false;
			}

			$.post('php/update-trade-token.php', {tradeUrl: inputValue}, function (jsonObj) {
				handleJsonResponse(jsonObj, function (data) {
					if (data['valid'] === 0) {
						swal.showInputError(data['errMsg']);
						return false;
					} else {
						successMsg('Your new trade url was successfully saved.');
					}
				});
			}, 'json');
		});
	});
});

var mTimeLeft = 120;
var timerRunning = false;
function timer (timeLeft) {
	if (!timerRunning || timeLeft <= 0) {
		timerRunning = false;
		return;
	}

	if (timeLeft !== undefined) {
		mTimeLeft = timeLeft;
	}

	//Set something on the page to show the time left
	$('#time-left').text(parseInt(mTimeLeft) + 's');

	mTimeLeft--;

	if (mTimeLeft <= 0) {
		timerRunning = false;
		return;
	}

	setTimeout(timer, 1000);
}

function update () {
	$.getJSON('php/update.php', function (jsonObj) {
		//console.log(JSON.stringify(jsonObj));
		handleJsonResponse(jsonObj, function (data) {
			var chat = data['chat'],
				pot = data['pot'],
				potPrice = data['potPrice'],
				roundEndTime = data['roundEndTime'],
				mostRecentAllItems = data['mostRecentAllItems'],
				mostRecentGame = data['mostRecentGame'];

			if (roundEndTime !== null && roundEndTime !== '0') {
				//Calculate the time left in the timer, depending on the end time
				roundEndTime = parseInt(roundEndTime);

				if (roundEndTime !== NaN) {
					var curTimeMillis = Date.now();

					if (curTimeMillis > roundEndTime) {
						timerRunning = false;
					}

					timeLeft = (roundEndTime - curTimeMillis) / 1000;

					if (!timerRunning) {
						console.log('Starting timer');
						timerRunning = true;
						timer(timeLeft);
					}
				}
			} else {
				//If the round end time is null or 0
				$('#time-left').text('0s');
			}

			var serverMostRecentID = parseInt(chat[chat.length - 1]['id'], 10);

			//Check if this is the first time update has been called
			if (firstUpdate) {
				$('#pot-items-price').css('display', 'block');
				firstUpdate = false;
			}

			//Check for new messages
			if (serverMostRecentID > localMostRecentID) {
				localMostRecentID = serverMostRecentID;
				localChatIDForColor = serverMostRecentID;

				var chatStr = generateChatStr(chat);
				$('#chatmessages').html(chatStr);
				$('#chatmessages').scrollTop($('#chatmessages')[0].scrollHeight);
			}

			//First, check if a round just ended
			//This will only be null when the current round is the first one ever
			if (mostRecentGame['prevGameID'] !== null) {
				//Get all of the info for the previous game
				var prevGameID = parseInt(mostRecentGame['prevGameID']),
					winnerSteamInfo = mostRecentGame['winnerSteamInfo'],
					userPutInPrice = parseInt(mostRecentGame['userPutInPrice']),
					potPricePrevGame = parseInt(mostRecentGame['potPrice']),
					allItems = JSON.parse(mostRecentGame['allItems']),
					paid = mostRecentGame['paid'];

				//if (allItemsJson.length > 0 && mLastGameID !== 0)
				if (prevGameID > mLastGameID && mostRecentAllItems.length > 0 && mLastGameID !== 0) {
					//A round just ended and someone just now won. For now, just sweetalert the winner.
					
					//First, stop the timer
					timerRunning = false;

					mLastGameID = prevGameID;

					//Do some fancy stuff
					var potPriceReal = getFormattedPrice(potPricePrevGame);
					var percentageChance = (userPutInPrice / potPricePrevGame * 100).toFixed(2);
					var winnerSteamID = winnerSteamInfo['steamid'], winnerProfileName = winnerSteamInfo['personaname'];

					//Set stuff in previous game box on the left of the screen
					var percentageChance = (userPutInPrice / potPricePrevGame * 100).toFixed(2);
					var profileName = winnerSteamInfo['personaname'];
					var profileAvatar = winnerSteamInfo['avatarfull'];
					var potPriceReal = getFormattedPrice(potPricePrevGame);

					$('#prev-winner-pic').attr('src', profileAvatar);
					$('#prev-winner-name').text(profileName);
					$('#prev-winner-amnt').text(potPriceReal);
					$('#prev-winner-chance').text(percentageChance + '%');

					if (mUserInfo !== null && winnerSteamID === mUserInfo['steamid']) {
						var msg = 'You have won ' + potPriceReal + ', with a ' + percentageChance + '% chance! Expect a trade request from our bot shortly. <b>Make sure that you are only receiving items. Our bot will never try to take any items from you.</b><br><br>Round ID: ' + prevGameID + '<br><br>If you do not receive a trade request shortly, please open a <a href="support.html">support ticket</a>, including the round id, your steam id, and any items that you can remember being in the pot.';

						swal({
							title: 'You win!',
							text: msg,
							closeOnConfirm: true,
							html: true,
						});
					} else {
						swal('Round ended!', winnerProfileName + ' has won ' + potPriceReal + ', with a ' + percentageChance + '% chance!', 'success');
					}

					potCount = -1;

					//TODO: Add circle thing here
					drawCircle(0);

					$('#pot-price').text('$0.00');
					$('#pot-items').text('0');
					$('#pot').text('');

					$('#items-deposited-count').text(0);
					$('#items-deposited-price').text(getFormattedPrice(0));
					$('#items-deposited-chance').text('0%');

					setTimeout(update, updateWaitTime);

					return;
				} else if (mLastGameID === 0) {
					mLastGameID = prevGameID;
				} else {
					//Set stuff in previous game box on the left of the screen
					var percentageChance = (userPutInPrice / potPricePrevGame * 100).toFixed(2);
					var profileName = winnerSteamInfo['personaname'];
					var profileAvatar = winnerSteamInfo['avatarfull'];
					var potPriceReal = getFormattedPrice(potPricePrevGame);

					$('#prev-winner-pic').attr('src', profileAvatar);
					$('#prev-winner-name').text(profileName);
					$('#prev-winner-amnt').text(potPriceReal);
					$('#prev-winner-chance').text(percentageChance + '%');
				}
			}

			//Check for new items in the pot
			if (pot.length > potCount || (pot.length < potCount && prevGameID !== mLastGameID)) {
				potCount = pot.length;

				drawCircle(potCount);

				//Set pot price
				var realPotPrice = getFormattedPrice(potPrice);
				$('#pot-price').text(realPotPrice);
				document.title = realPotPrice + ' | CSGO Win Big';

				//Set number of pot items
				$('#pot-items').text(potCount);

				//Set items in pot
				var potStr = generatePotStr(pot);
				console.log('Pot updated for new items.');
				$('#pot').html(potStr);

				//Get all items that are put in by the user logged in
				if (loggedIn) {
					var loggedInSteamID = mUserInfo['steamid'];
					var loggedInUserItems = [];
					for (var i1 = 0; i1 < pot.length; i1++) {
						var item = pot[i1];
						var itemOwner = item['itemSteamOwnerInfo'];
						var ownerSteamID = itemOwner['steamid'];

						if (ownerSteamID === loggedInSteamID) {
							loggedInUserItems.push(item);
						}
					}

					var loggedInUserPrice = 0;

					for (var i1 = 0; i1 < loggedInUserItems.length; i1++) {
						var item = loggedInUserItems[i1];
						var itemPrice = parseInt(item['itemPrice'], 10);
						loggedInUserPrice += itemPrice;
					}

					$('#items-deposited-count').text(loggedInUserItems.length);
					$('#items-deposited-price').text(getFormattedPrice(loggedInUserPrice));

					var chance;
					if (potPrice === 0) {
						chance = 0;
					} else {
						chance = loggedInUserPrice / potPrice * 100;
						chance = Math.round(chance * 100) / 100;
					}
					$('#items-deposited-chance').text(chance);
				}

				//Set the players in round table
				var allPlayers = [];
				for (var i1 = 0; i1 < pot.length; i1++) {
					var item = pot[i1];

					var itemPrice = parseInt(item['itemPrice']);

					var owner = item['itemSteamOwnerInfo'];

					var ownerAvatar = owner['avatarfull'], ownerName = owner['personaname'], ownerUrl = owner['profileurl'];

					//Check if already in the allPlayers array
					var inArray = false;
					for (var i2 = 0; i2 < allPlayers.length; i2++) {
						var player = allPlayers[i2];

						if (player['url'] === ownerUrl) {
							//They are already in the array, just update the object
							player['price'] += itemPrice;
							player['itemCount']++;
							inArray = true;
							break;
						}
					}

					if (!inArray) {
						var obj = {'name': ownerName, 'avatar': ownerAvatar, 'url': ownerUrl, 'price': itemPrice, 'itemCount': 1};
						allPlayers.push(obj);
					}
				}

				/* <tr style="height: 50px;">
					<td style="width: 10%; height: 100%;">
						<img src="http://cdn.akamai.steamstatic.com/steamcommunity/public/images/avatars/3c/3c837d8c933b1ce1d78c82ff94b872573d5a3f88_full.jpg" style="width: 100%;">
					</td>
					<td style="width: 90%; height: 100%; padding-left: 5%;">
						CSGOWINBIG.COM deposited 2 items for a 100% chance
					</td>
				</tr> */

				//Sort the array of players by price/chance. If they have the same price, sort by name.
				allPlayers.sort(function (a, b) {
					return b['price'] - a['price'] || a['name'].localeCompare(b['name']);
				});

				var str = '';
				for (var i1 = 0; i1 < allPlayers.length; i1++) {
					var player = allPlayers[i1];

					var chance = player['price'] / potPrice * 100;

					chance = Math.round(chance * 100) / 100;

					str += '<tr style="height: 50px;">';
					str += '<td style="width: 10%; height: 100%;">';
					str += '<a href="' + player['url'] + '" class="link" target="_blank"><img src="' + player['avatar'] + '" style="width: 100%; border-radius: 5px;"></a>';
					str += '</td>';
					str += '<td style="width: 90%; height: 100%; padding-left: 5%;">';
					str += '<a href="' + player['url'] + '" class="link" target="_blank">' + player['name'] + '</a>' + '<br>' + player['itemCount'] + ' items worth ' + getFormattedPrice(player['price']) + ' for a ' + chance + '% chance';
					str += '</td>';
					str += '</tr>';
				}
				$('#users-deposited-table').html(str);
			}

			if (pot.length === 0) {
				//The pot is empty, set a message
				$('#pot').html('<div style="font-size: 20px;">The pot is empty. Be the first to deposit!</div>');
			}

			setTimeout(update, updateWaitTime); //Call update again after 2 seconds
		});
	});
}

function drawCircle (itemCount) {
	var perc = itemCount / 60;
	var percRadians = perc * (2 * Math.PI);

	var c = $('#items-canvas')[0];
	var ctx = c.getContext('2d');

	var cSize = c.width;

	ctx.rect(0, 0, cSize, cSize);
	ctx.fillStyle = 'white';
	ctx.fill();

	/* ctx.beginPath();
	ctx.arc(cSize / 2, cSize / 2, cSize / 2, 0, 2 * Math.PI);
	ctx.stroke(); */

	//Draw gray background circle
	ctx.beginPath();
	ctx.arc(cSize / 2, cSize / 2, cSize / 2, 0, 2 * Math.PI);
	ctx.fillStyle = '#C7C7C7';
	ctx.fill();

	//Draw part of circle taken up
	ctx.beginPath();
	ctx.moveTo(cSize / 2, cSize / 2);
	ctx.lineTo(cSize / 2, cSize * 3 / 4);
	ctx.arc(cSize / 2, cSize / 2, cSize / 2, Math.PI / 2, Math.PI / 2 - percRadians, true);
	ctx.lineTo(cSize / 2, cSize / 2);
	ctx.fillStyle = 'blue';
	ctx.fill();

	//Draw white circle in the middle	
	ctx.beginPath();
	ctx.arc(cSize / 2, cSize / 2, cSize * 3 / 8, 0, Math.PI * 2, true);
	ctx.fillStyle = 'white';
	ctx.fill();

	//Draw black circle outline around white circle
	/* ctx.beginPath();
	ctx.arc(cSize / 2, cSize / 2, cSize * 1 / 4, 0, Math.PI * 2, true);
	ctx.fillStyle = 'black';
	ctx.stroke(); */
}

function generateChatStr (chat) {
	var str = '';
	for (var i1 = 0; i1 < chat.length; i1++) {
		var msg = chat[i1];

		str += generateChatMsgStr(msg);
	}
	return str;
}

function generateChatMsgStr (msg) {
	var id = parseInt(msg['id'], 10),
		text = msg['text'],
		date = msg['date'],
		time = msg['time'],
		userInfo = msg['steamUserInfo'];

	var profileName = userInfo['personaname'],
		profileURL = userInfo['profileurl'],
		steamID = userInfo['steamid'];
		profilePicSmall = userInfo['avatar'];

	var colorClass = id % 2 === 0 ? 'chat-message-even' : 'chat-message-odd';

	var moderators = ['76561198026845481', '76561198058039750', '76561198079439072', '76561198202339448', '76561197995164153', '76561198025574371'];

	var str = '<div class="chat-message ' + colorClass + '" title="' + date + ' at ' + time + '">';
	str += '<a href="' + profileURL + '" target="_blank" class="link">' + profileName + '</a>';

	if (steamID === '76561198020620333') {
		str += ' (Owner)';
	} else if ($.inArray(steamID, moderators) !== -1) {
		str += ' (Moderator)';
	}

	str += ': ';

	str += text;
	str += '</div>';

	//This code is for the 
	/* var str = '<div class="chat-message ' + colorClass + '">';
	str += '<a href="' + profileURL + '" target="_blank" class="link">';
	str += '<img src="' + profilePicSmall + '" class="chat-profile-pic">';
	str += '</a>';
	str += '<div class="chat-profile-name"><a href="' + profileURL + '" target="_blank" class="link">' + profileName + '</a>';

	if (steamID === '76561198020620333') {
		str += ' (Owner)';
	} else if ($.inArray(steamID, moderators) !== -1) {
		str += ' (Moderator)';
	}

	str += '</div>';
	str += '<div class="chat-date-time">' + date + ' at ' + time + '</div>';
	str += '<div class="chat-text">' + text + '</div>';
	str += '</div>'; */

	return str;
}

function generatePotStr (pot) {
	var str = '';
	for (var i1 = 0; i1 < pot.length; i1++) {
		var item = pot[i1];

		var itemID = item['id'],
			itemOwnerSteamInfo = item['itemSteamOwnerInfo'],
			itemName = item['itemName'],
			itemPrice = item['itemPrice'],
			itemIcon = item['itemIcon'],
			itemRarityColor = item['itemRarityColor'];

		var rgbRarityColor = hexToRgb(itemRarityColor);

		if (itemName.indexOf("Sticker %7C")) {
			itemIcon = 'http://steamcommunity-a.akamaihd.net/economy/image/' + itemIcon + '/90fx90f';
		} else {
			itemIcon = 'http://steamcommunity-a.akamaihd.net/economy/image/' + itemIcon + '/360fx360f';
		}

		var profileName = itemOwnerSteamInfo['personaname'],
			profileAvatar = itemOwnerSteamInfo['avatarfull'],
			profileURL = itemOwnerSteamInfo['profileurl'];

		var itemRealPrice = getFormattedPrice(itemPrice);

		var colorStr = 'rgba(' + rgbRarityColor.r + ', ' + rgbRarityColor.g + ', ' + rgbRarityColor.b + ', 0.5)';

		str += '<div class="pot-item container" style="padding: 0px; background-color: ' + colorStr + '">';

		str += '<div class="col-sm-1 pot-item-inner-container">';
		str += '<a href="' + profileURL + '" target="_blank" class="link"><img src="' + profileAvatar + '" class="pot-image">';
		str += '</a></div>';

		str += '<div class="col-sm-10 pot-item-inner-container" style="text-align: center;"><a href="' + profileURL + '" target="_blank" class="link">' + profileName + '</a>' + ': ' + itemName + ' - ' + itemRealPrice + '</div>';

		str += '<div class="col-sm-1 pot-item-inner-container"><img src="' + itemIcon + '" class="pot-image"></div>';

		str += '</div>';
	}

	return str;
}

function hexToRgb(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}

function handleJsonResponse (jsonObj, callback) {
	if (jsonObj['success'] === 1) {
		var data = jsonObj['data'];
		callback(data);
	} else {
		var msg = jsonObj['errMsg'];
		errMsg(msg);
	}
}

function errMsg (message) {
	if (message === null || message === undefined) {
		message = 'An unknown error has occured. Please refresh the page and try again.';
	}
	swal('Hmm, something went wrong', message, 'error');
}

function successMsg (message) {
	swal('Success!', message, 'success');
}

function getFormattedDate() {
	var date = new Date();

	var month = date.getMonth() + 1;
	if (month < 10) {
		month = '0' + month;
	}

	return date.getFullYear() + "-" + month + "-" + date.getDate();
}

function getFormattedTime () {
	var date = new Date();

	return date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds();
}

function getFormattedPrice (cents) {
	if (typeof cents !== 'number') {
		cents = parseInt(cents);
	}

	var price = cents / 100;
	
	if (cents % 100 === 0) { //If it is an even dollar, add the .00
		price = price + '.00';
	} else if (cents % 10 === 0) { //If it is like $3.40, add the trailing 0
		price = price + '0';
	}

	return '$' + price;
}

function playSound (soundId) {
	var player = $('#' + soundId)[0];
	player.currentTime = 0;
	player.play();
}
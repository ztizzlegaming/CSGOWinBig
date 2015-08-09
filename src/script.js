//Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

//The most recent ID for the chat, pot count, and last game ID
var localMostRecentID = 0, potCount = 0, lastGameID = 0;

//Whether or not the call of update() is the first call
var firstUpdate = true;

//The most recent ID for the user sending the chat.
//Used if they want to send multiple messages before the chat refreshes.
var localChatIDForColor = 0;

//Whether or not the user is logged in
var loggedIn = false;

//The user's information
var mUserInfo = null;

$(function () {
	$.getJSON('php/login-status.php', function (jsonObj) {
		handleJsonResponse(jsonObj, function (data) {
			var loginStatus = data['loginStatus'];

			mUserInfo = data['userInfo'];

			if (loginStatus === 1) {
				//They are logged in
				$('.logout').css('display', 'inline');
				$('#chat-input').css('display', 'block');

				loggedIn = true;

				var tradeTokenEntered = data['tradeTokenEntered'];
				if (tradeTokenEntered === 0) {
					//They have not entered their trade URL, prompt them to enter it
					swal({
						title: 'Trade URL',
						text: 'Please enter your trade url for payouts.<br>NOTE: Make sure your url is correct; otherwise, you will not receive your winnings. You can find your trade url <a href="http://steamcommunity.com/id/me/tradeoffers/privacy" target="_blank">here</a>.',
						type: 'input',
						showCancelButton: false,
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
							console.log(jsonObj);
							handleJsonResponse(jsonObj, function (data) {
								if (data['valid'] === 0) {
									swal.showInputError(data['errMsg']);
									return false;
								} else {
									successMsg('Your trade url was successfully saved.');
								}
							});
						}, 'json');
					});
				}

			} else {
				//They are not logged in
				$('.login').css('display', 'inline');
			}

			$('#loading-menubar').css('display', 'none');

			//Check if the page is the home page
			var page = location.pathname.substring(location.pathname.lastIndexOf("/") + 1);
			if (page === '' || page === 'index.html') {
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

	$('#change-trade-url').on('click', function () {
		if (!loggedIn) {
			return;
		}

		swal({
			title: 'Change Trade URL',
			text: 'Enter your updated trade url here.',
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
				console.log(jsonObj);
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

function update () {
	$.getJSON('php/update.php', function (jsonObj) {
		handleJsonResponse(jsonObj, function (data) {
			console.log('Response received.');
			var chat = data['chat'],
				pot = data['pot'],
				potPrice = data['potPrice'],
				mostRecentGame = data['mostRecentGame'];

			var serverMostRecentID = parseInt(chat[chat.length - 1]['id'], 10);

			//Check if this is the first time update has been called
			if (firstUpdate) {
				$('#pot-items-price').css('display', 'block');
			}

			//Check for new messages
			if (serverMostRecentID > localMostRecentID) {
				console.log('New messages!');
				localMostRecentID = serverMostRecentID;
				localChatIDForColor = serverMostRecentID;

				var chatStr = generateChatStr(chat);
				$('#chatmessages').html(chatStr);
				$('#chatmessages').scrollTop($('#chatmessages')[0].scrollHeight);
			}

			//Check for new items in the pot
			if (pot.length !== potCount) {
				console.log('New items in pot!');
				potCount = pot.length;

				//Set pot price
				var realPotPrice = getFormattedPrice(potPrice);
				$('#pot-price').text(realPotPrice);

				//Set number of pot items
				$('#pot-items').text(potCount);

				//Set items in pot
				var potStr = generatePotStr(pot);
				$('#pot').html(potStr);
			}

			if (mostRecentGame !== null) {
				var prevGameID = parseInt(mostRecentGame['prevGameID']),
					winnerSteamInfo = mostRecentGame['winnerSteamInfo'],
					userPutInPrice = parseInt(mostRecentGame['userPutInPrice']),
					potPrice = parseInt(mostRecentGame['potPrice']),
					allItems = mostRecentGame['allItems'],
					paid = mostRecentGame['paid'];

				if (prevGameID > lastGameID && lastGameID !== 0) {
					//A round just ended and someone just now won. For now, just sweetalert the winner.
					lastGameID = prevGameID;

					var potPriceReal = getFormattedPrice(potPrice);
					var percentageChance = (userPutInPrice / potPrice * 100).toFixed(2);
					var winnerSteamID = winnerSteamInfo['steamid'], winnerProfileName = winnerSteamInfo['personaname'];

					if (winnerSteamID === mUserInfo['steamid']) {
						var msg = 'You have won ' + potPriceReal + ', with a ' + percentageChance + '% chance! Expect a trade request from our bot shortly.';
						swal('You Win!', msg, 'success');
					} else {
						swal('Round ended!', winnerProfileName + ' has won ' + potPriceReal + ', with a ' + percentageChance + ' chance!', 'success');
					}
				} else if (lastGameID === 0) {
					lastGameID = prevGameID;
				}

				var percentageChance = (userPutInPrice / potPrice * 100).toFixed(2);
				var profileName = winnerSteamInfo['personaname'];
				var potPriceReal = getFormattedPrice(potPrice);

				var str = 'Previous Winner: ' + profileName + ' won ' + potPriceReal + ' with a ' + percentageChance + '% chance.';
				$('#prev-game-info').html(str);
				$('#prev-game-info').css('display', 'block');
			}

			setTimeout(update, 2000); //Call update again after 2 seconds
		});
	});
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

	var str = '<div class="chat-message ' + colorClass + '">';
	str += '<a href="' + profileURL + '" target="_blank">';
	str += '<img src="' + profilePicSmall + '" class="chat-profile-pic">';
	str += '<div class="chat-profile-name">' + profileName + '</div>';
	str += '</a>';
	str += '<div class="chat-date-time">' + date + ' at ' + time + '</div>';
	str += '<div class="chat-text">' + text + '</div>';
	str += '</div>';

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
			itemIcon = item['itemIcon'];

		var profileName = itemOwnerSteamInfo['personaname'],
			profileAvatar = itemOwnerSteamInfo['avatarfull'],
			profileURL = itemOwnerSteamInfo['profileurl'];

		var itemRealPrice = getFormattedPrice(itemPrice);

		str += '<div class="pot-item">'
		str += '<a href="' + profileURL + '" target="_blank"><img src="' + profileAvatar + '" class="pot-item-profile-image">';
		str += '<div class="pot-item-name">' + profileName + '</a>' + ': ' + itemName + ' - ' + itemRealPrice + '</div>';
		str += '<img src="' + itemIcon + '" class="pot-item-image">';
		str += '</div>';
	}

	return str;
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
	var price = cents / 100;
	
	if (cents % 100 === 0) { //If it is an even dollar, add the .00
		price = price + '.00';
	} else if (cents % 10 === 0) { //If it is like $3.40, add the trailing 0
		price = price + '0';
	}

	return '$' + price;
}
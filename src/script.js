//The most recent ID for the chat
var localMostRecentID = 0, potCount = 0;

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
			} else {
				//They are not logged in
				$('.login').css('display', 'inline');
			}

			$('#loading-menubar').css('display', 'none');

			setTimeout(update, 1000);
		});
	});

	var isSending = false;
	$('#chat-input').on('keydown', function (event) {
		if (event.which === 13 || loggedIn === false) {
			var text = this.value;

			if (text.length === 0) {
				return;
			}

			if (isSending) {
				return;
			}

			isSending = true;

			var dateTime = getFormattedDate(),
				profileName = mUserInfo['personaname'],
				avatar = mUserInfo['avatar'];

			var str = '<div class="chat-message">';
			str += '<img src="' + avatar + '" class="chat-profile-pic">';
			str += '<div class="chat-profile-name">' + profileName + '</div>';
			str += '<div class="chat-date-time">' + dateTime + '</div>';
			str += '<div class="chat-text">' + text + '</div>';
			str += '</div>';

			$('#chatmessages').append(str);
			$('#chatmessages').scrollTop($('#chatmessages')[0].scrollHeight);

			$('#chat-input').val('');

			$.post('php/send-chat-message.php', {text: text}, function () {
				isSending = false;
			});
		}
	});
});

function update () {
	$.getJSON('php/update.php', function (jsonObj) {
		handleJsonResponse(jsonObj, function (data) {
			console.log('Response received.');
			var chat = data['chat'],
				pot = data['pot'],
				potPrice = data['potPrice'];

			var serverMostRecentID = chat[chat.length - 1]['id'];

			//Check for new messages
			if (serverMostRecentID > localMostRecentID) {
				console.log('New messages!');
				localMostRecentID = serverMostRecentID;

				var chatStr = generateChatStr(chat);
				$('#chatmessages').html(chatStr);
				$('#chatmessages').scrollTop($('#chatmessages')[0].scrollHeight);
			}

			//Check for new items in the pot
			if (pot.length > potCount) {
				console.log('New items in pot!');
				potCount = pot.length;

				var potStr = generatePotStr(pot);
				$('#pot').html(potStr);
			}

			setTimeout(update, 2000); //Call update again after 2 seconds
		});
	});
}

function generateChatStr (chat) {
	var str = '';
	for (var i1 = 0; i1 < chat.length; i1++) {
		var msg = chat[i1];

		var id = msg['id'],
			text = msg['text'],
			date = msg['date'],
			time = msg['time'],
			userInfo = msg['steamUserInfo'];

		var profileName = userInfo['personaname'],
			profilePicSmall = userInfo['avatar'];

		str += '<div class="chat-message">';
		str += '<img src="' + profilePicSmall + '" class="chat-profile-pic">';
		str += '<div class="chat-profile-name">' + profileName + '</div>';
		str += '<div class="chat-date-time">' + date + ' at ' + time + '</div><div class="chat-text">' + text + '</div></div>';
	}
	return str;
}

function generatePotStr (pot) {
	var str = '';
	for (var i1 = 0; i1 < pot.length; i1++) {
		var item = pot[i1];

		var itemID = item['id'],
			itemOwnerSteamID = item['itemOwnerSteamID'],
			
	};
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

function getFormattedDate() {
	var date = new Date();
	var str = date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate() + " at " +  date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds();

	return str;
}
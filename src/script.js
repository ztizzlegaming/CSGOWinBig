var mostRecentID = 0;

$(function () {
	$.getJSON('php/login-status.php', function (jsonObj) {
		handleJsonResponse(jsonObj, function (data) {
			var loginStatus = data['loginStatus'],
				steamProfileName = data['steamProfileName'],
				steamProfileID = data['steamProfileID'];

			if (loginStatus === 1) {
				//They are logged in
				$('.logout').css('display', 'inline');
				$('#chat-input').css('display', 'block');
			} else {
				//They are not logged in
				$('.login').css('display', 'inline');
			}

			$('#loading-menubar').css('display', 'none');
		});
	});

	var isSending = false;
	$('#chat-input').on('keydown', function (event) {
		if (event.which === 13) {
			var text = this.value;

			if (text.length === 0) {
				return;
			}

			if (isSending) {
				return;
			}

			isSending = true;

			$.post('php/send-chat-message.php', {text: text}, function () {
				$('#chat-input').val('');
				isSending = false;
			});
		}
	});

	update();
});

function update () {
	$.ajax({
		type: 'GET',
		url: 'php/update.php?mostRecentMessageID=' + mostRecentID,
		async: true,
		cache: false,
		dataType: 'json',
		timeout: 9000,
		success: function (jsonObj) {
			console.log('Response received.');
			handleJsonResponse(jsonObj, function (data) {
				var chat = data['chat'];
				var recentID = chat[chat.length - 1]['id'];

				var chatStr = generateChatStr(chat);
				$('#chatmessages').html(chatStr);
				$('#chatmessages').scrollTop($('#chatmessages')[0].scrollHeight);

				mostRecentID = recentID;

				setTimeout(update, 1000);
			});
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			//if (textStatus === 'timeout') {
			console.log('An error has occured. Error message: ' + textStatus);
				setTimeout(update, 1000);
			//} else {
			//	errMsg('Please refresh the page and try again.\nError message: ' + textStatus);
			//}
		}
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

		str += '<div class="chat-message"><img src="' + profilePicSmall + '" class="chat-profile-pic"><div class="chat-profile-name">' + profileName + '</div><div class="chat-date-time">' + date + ' at ' + time + '</div><div class="chat-text">' + text + '</div></div>';
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
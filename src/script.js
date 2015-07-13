$(function () {
	$.getJSON('php/login-status.php', function (jsonObj) {
		handleJsonResponse(jsonObj, function (data) {
			var loginStatus = data['loginStatus'];

			if (loginStatus === 1) {
				//They are logged in
				$('.logout').css('display', 'inline');
				$('#steam-profile-name').text('Your profile name: ' + data['steamProfileName']);
			} else {
				//They are not logged in
				$('.login').css('display', 'inline');
			}

			$('#loading-menubar').css('display', 'none');
		});
	});
});

function handleJsonResponse (jsonObj, callback) {
	if (jsonObj['success'] === 1) {
		var data = jsonObj['data'];
		callback(data);
	} else {
		var msg = jsonObj['errMsg'];
		errMsg(msg);
	}
}
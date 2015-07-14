<?php
require ('steamauth/openid.php');


function getSteamProfileInfoForSteamID($allUsersInfoStr, $steamIDToFind) {
	$allUsersInfo = json_decode($allUsersInfoStr, true);
	$players = $allUsersInfo['response']['players'];
	
	foreach ($players as $player) {
		$steamID = $player['steamid'];
		
		if ($steamIDToFind === $steamID) {
			return $player;
		}
	}

	# If the user is not found, then return false
	return false;
}

function generateChatArr() {
	$db = getDB();
	$stmt = $db->query('SELECT * FROM `chat` ORDER BY `id` DESC LIMIT 50');
	$chatMessages = $stmt->fetchAll();

	$allUserIDs = array();

	foreach ($chatMessages as $message) {
		$steamUserID = $message['steamUserID'];
		array_push($allUserIDs, $steamUserID);
	}

	# Convert array to csv
	$allUserIDsStr = join(',', $allUserIDs);

	# Get all user info for the steam user IDs
	$usersInfoStr = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=6BF5980401E3EB67D921BF4704521AD5&steamids=$allUserIDsStr");

	$chatMessagesArr = array();

	for ($i1 = count($chatMessages) - 1; $i1 >= 0; $i1--) {
		$message = $chatMessages{$i1};

		$id = $message['id'];
		$text = htmlspecialchars(stripcslashes($message['text']));
		$date = $message['date'];
		$time = $message['time'];

		$steamUserID = $message['steamUserID'];
		$steamUserInfo = getSteamProfileInfoForSteamID($usersInfoStr, $steamUserID);

		$arr = array('id' => $id, 'text' => $text, 'date' => $date, 'time' => $time, 'steamUserInfo' => $steamUserInfo);
		array_push($chatMessagesArr, $arr);
	}

	return $chatMessagesArr;
}

function jsonSuccess($data) {
	return json_encode(array('success' => 1, 'data' => $data));
}

function jsonErr($errMsg) {
	return json_encode(array('success' => 0, 'errMsg' => $errMsg));
}
?>
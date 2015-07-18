<?php
function getDB() {
	$dbHost = 'localhost';
	$db     = 'jordantu_csgowinbig';
	$dbUser = "jordantu_ztizzle";

	# Get database password from outside of web root
	$fileLoc = $_SERVER['DOCUMENT_ROOT'] . '/../../passwords.txt';
	if (file_exists($fileLoc)) {
		$fh = fopen($fileLoc, 'r');
		$jsonStr = fgets($fh);
		$arr = json_decode($jsonStr, true);
		$dbPass = $arr['default-password'];
		fclose($fh);
	} else {
		die('no file found');
	}

	$db = new PDO("mysql:host=$dbHost;dbname=$db;charset=utf8", $dbUser, $dbPass);
	return $db;
}

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
	$usersInfoStr = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=1FBC1D48247E517DB7CE37C093450807&steamids=$allUserIDsStr");

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

function getSteamAPIKey() {
	return '1FBC1D48247E517DB7CE37C093450807';
}
?>
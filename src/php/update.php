<?php
session_start();
include 'default.php';
$db = getDB();

# Get all chat messages
$stmt = $db->query('SELECT * FROM `chat` ORDER BY `id` DESC LIMIT 50');
$chatMessages = $stmt->fetchAll();

$allUserIDs = array();

foreach ($chatMessages as $message) {
	$steamUserID = $message['steamUserID'];
	array_push($allUserIDs, $steamUserID);
}

# Convert array to csv for steam API call
$allUserIDsStr = join(',', $allUserIDs);

# Get all user info for the steam user IDs
$chatAPIKey = getSteamAPIKey('chat');
$usersInfoStr = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$chatAPIKey&steamids=$allUserIDsStr");

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

# Get the current pot
$stmt = $db->query('SELECT * FROM `currentPot`');

$currentPotArr = $stmt->fetchAll();

$currentPot = array();

foreach ($currentPotArr as $itemInPot) {
	$itemID = $itemInPot['id'];
	$itemOwnerSteamID = $itemInPot['ownerSteamID'];
	$itemName = $itemInPot['itemName'];
	$itemPrice = $itemInPot['itemPrice'];

	$arr = array('itemID' => $itemID, 'itemOwnerSteamID' => $itemOwnerSteamID, 'itemName' => $itemName, 'itemPrice' => $itemPrice);
	array_push($currentPot, $arr);
}

$data = array('chat' => $chatMessagesArr);
echo jsonSuccess($data);
?>
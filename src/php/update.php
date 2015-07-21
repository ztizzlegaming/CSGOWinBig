<?php
session_start();
include 'default.php';
$db = getDB();

# Get all chat messages
$stmt = $db->query('SELECT * FROM `chat` ORDER BY `id` DESC LIMIT 50');
$chatMessages = $stmt->fetchAll();

$allUserIDsChat = array();

foreach ($chatMessages as $message) {
	$steamUserID = $message['steamUserID'];
	array_push($allUserIDsChat, $steamUserID);
}

# Convert array to csv for steam API call
$allUserIDsStr = join(',', $allUserIDsChat);

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

$allUserIDsPot = array();

foreach ($currentPotArr as $item) {
	$steamUserID = $item['ownerSteamUserID'];
	array_push($allUserIDsPot, $steamUserID);
}

# Convert array to csv for steam API call
$allUserIDsPotStr = join(',', $allUserIDsPot);

# Get all user info for the steam user IDs for the pot
$potAPIKey = getSteamAPIKey('pot');
$usersInfoStrPot = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$chatAPIKey&steamids=$allUserIDsStr");

$currentPot = array();
$potPrice = 0;

foreach ($currentPotArr as $itemInPot) {
	$itemID = $itemInPot['id'];
	$itemName = $itemInPot['itemName'];
	$itemPrice = $itemInPot['itemPrice'];

	$itemOwnerSteamID = $itemInPot['ownerSteamID'];
	$steamUserInfo = getSteamProfileInfoForSteamID($usersInfoStrPot, $itemOwnerSteamID);

	$arr = array('itemID' => $itemID, 'itemSteamOwnerInfo' => $steamUserInfo, 'itemName' => $itemName, 'itemPrice' => $itemPrice);
	array_push($currentPot, $arr);

	$potPrice += $itemPrice;
}

$data = array('chat' => $chatMessagesArr, 'pot' => $currentPot, 'potPrice' => $potPrice);
echo jsonSuccess($data);
?>
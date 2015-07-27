<?php
session_start();
include 'default.php';
$db = getDB();

# Get all users in chat messages
$stmt = $db->query('SELECT * FROM `chat` ORDER BY `id` DESC LIMIT 50');
$chatMessages = $stmt->fetchAll();

$allUserIDsChat = array();

foreach ($chatMessages as $message) {
	$steamUserID = $message['steamUserID'];
	array_push($allUserIDsChat, $steamUserID);
}

# Get all users in pot
$stmt = $db->query('SELECT * FROM `currentPot`');
$currentPotArr = $stmt->fetchAll();

$allUserIDsPot = array();

foreach ($currentPotArr as $item) {
	$steamUserID = $item['ownerSteamID'];
	array_push($allUserIDsPot, $steamUserID);
}

# Get previous winner's steam ID
$stmt = $db->query('SELECT * FROM history ORDER BY id DESC');
$prevPot = $stmt->fetch();
$prevWinner = $prevPot['winnerSteamID'];

$prevWinnerArr = array($prevWinner);

# Create array of all users, without repeats
$allUsersArr = array_unique(array_merge($allUserIDsChat, $allUserIDsPot, $prevWinnerArr));
$allUserIDsStr = join(',', $allUsersArr);

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
$potPrice = 0;

foreach ($currentPotArr as $itemInPot) {
	$itemID = $itemInPot['id'];
	$itemName = $itemInPot['itemName'];
	$itemPrice = $itemInPot['itemPrice'];
	$itemIcon = $itemInPot['itemIcon'];

	$itemOwnerSteamID = $itemInPot['ownerSteamID'];
	$steamUserInfo = getSteamProfileInfoForSteamID($usersInfoStr, $itemOwnerSteamID);

	$arr = array('itemID' => $itemID, 'itemSteamOwnerInfo' => $steamUserInfo, 'itemName' => $itemName, 'itemPrice' => $itemPrice, 'itemIcon' => $itemIcon);
	array_push($currentPot, $arr);

	$potPrice += $itemPrice;
}

# Get the past pot and check if someone just now won
$prevGameID = $prevPot['id'];
$winnerSteamID = $prevPot['winnerSteamID'];
$userPutInPrice = $prevPot['userPutInPrice'];
$potPrice = $prevPot['potPrice'];
$allItems = $prevPot['allItems'];
$paid = $prevPot['paid'];

$winnerSteamInfo = getSteamProfileInfoForSteamID($usersInfoStr, $winnerSteamID);

$mostRecentGame = array(
	'prevGameID' => $prevGameID,
	'winnerSteamInfo' => $winnerSteamInfo,
	'userPutInPrice' => $userPutInPrice,
	'potPrice' => $potPrice,
	'allItems' => $allItems,
	'paid' => $paid
);

$data = array(
	'chat' => $chatMessagesArr,
	'pot' => $currentPot,
	'potPrice' => $potPrice,
	'mostRecentGame' => $mostRecentGame
);
echo jsonSuccess($data);
?>
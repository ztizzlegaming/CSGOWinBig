<?php
# Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

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
	$steamUserID = $item['ownerSteamId64'];
	array_push($allUserIDsPot, $steamUserID);
}

# Get previous winner's steam ID
$stmt = $db->query('SELECT * FROM history ORDER BY id DESC');
$allRounds = $stmt->fetchAll();

$mostRecentRound = $allRounds[0];
# If the most recent round in history has items in it, then the current round doesn't have any items in it,
# and there isn't a current round in the history table yet
if (strlen($mostRecentRound['allItemsJson']) > 0) {
	$prevPot = $mostRecentRound;
	$currentRound = null;
} else {
	$prevPot = $allRounds[1];
	$currentRound = $mostRecentRound;
}

$prevWinner = $prevPot['winnerSteamId64'];

$prevWinnerArr = array($prevWinner);

# Create array of all users, without repeats
$allUsersArr = array_unique(array_merge($allUserIDsChat, $allUserIDsPot, $prevWinnerArr));
$allUserIDsStr = join(',', $allUsersArr);

# Get all user info for the steam user IDs
$chatAPIKey = getSteamAPIKey();
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
$stmt = $db->query('SELECT * FROM currentPot ORDER BY id DESC');
$currentPotArr = $stmt->fetchAll();

$currentPot = array();
$potPrice = 0;

foreach ($currentPotArr as $itemInPot) {
	$itemID = $itemInPot['id'];
	$itemName = $itemInPot['itemName'];
	if ($itemName[0] === '?') {
		$itemName = substr($itemName, 2);
	}
	$itemPrice = $itemInPot['itemPrice'];
	$itemIcon = $itemInPot['itemIcon'];
	$itemRarityColor = $itemInPot['itemRarityColor'];

	$itemOwnerSteamID = $itemInPot['ownerSteamId64'];
	$steamUserInfo = getSteamProfileInfoForSteamID($usersInfoStr, $itemOwnerSteamID);

	$arr = array(
		'itemID' => $itemID,
		'itemSteamOwnerInfo' => $steamUserInfo,
		'itemName' => $itemName,
		'itemPrice' => $itemPrice,
		'itemIcon' => $itemIcon,
		'itemRarityColor' => $itemRarityColor
	);
	array_push($currentPot, $arr);

	$potPrice += $itemPrice;
}

# Get the time left in the current round
$roundEndTime = is_null($currentRound) ? null : $currentRound['endTime'];

$stmt = $db->query('SELECT * FROM history ORDER BY id DESC');
$mostRecentInHistory = $stmt->fetch();

$mostRecentAllItems = $mostRecentInHistory['allItemsJson'];

# Get the past pot and check if someone just now won
$prevGameID = $prevPot['id'];
$winnerSteamId64 = $prevPot['winnerSteamId64'];
$userPutInPrice = $prevPot['userPutInPrice'];
$prevPotPrice = $prevPot['potPrice'];
$allItems = $prevPot['allItemsJson'];

$winnerSteamInfo = getSteamProfileInfoForSteamID($usersInfoStr, $winnerSteamId64);
$winnerSteamInfo['personaname'] = html_entity_decode($winnerSteamInfo['personaname']);

# The information for the previous round
$mostRecentGame = array(
	'prevGameID' => $prevGameID,
	'winnerSteamInfo' => $winnerSteamInfo,
	'userPutInPrice' => $userPutInPrice,
	'potPrice' => $prevPotPrice,
	'allItems' => $allItems
);

# The information for the current round
$data = array(
	'chat' => $chatMessagesArr,
	'pot' => $currentPot,
	'potPrice' => $potPrice,
	'roundEndTime' => $roundEndTime,
	'mostRecentAllItems' => $mostRecentAllItems,
	'mostRecentGame' => $mostRecentGame
);
echo jsonSuccess($data);
?>
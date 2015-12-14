<?php
# Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

include 'default.php';
$db = getDB();

$password = isset($_POST['password']) ? $_POST['password'] : null;

if (is_null($password) || strlen($password) === 0) {
	echo jsonErr('One of the required fields was not sent correctly or was left blank.');
	return;
}

# Get the password from config file and make sure it matches
$fileLoc = $_SERVER['DOCUMENT_ROOT'] . '/../passwords.txt';
if (file_exists($fileLoc)) {
	$fh = fopen($fileLoc, 'r');
	$jsonStr = fgets($fh);
	$arr = json_decode($jsonStr, true);
	$realPassword = $arr['default-password'];
	fclose($fh);
} else {
	die('no file found');
}

if ($password !== $realPassword) {
	echo jsonErr('The password was incorrect.');
	return;
}

$stmt = $db->query('SELECT * FROM currentPot');
$allPotItems = $stmt->fetchAll();

$ticketsArr = array();
$totalPotPrice = 0;

foreach ($allPotItems as $item) {
	$itemOwnerId32 = $item['ownerSteamId32'];
	$itemOwnerId64 = $item['ownerSteamId64'];
	$itemPrice = $item['itemPrice'];

	$totalPotPrice += $itemPrice;

	for ($i1=0; $i1 < $itemPrice; $i1++) {
		array_push($ticketsArr, array('32' => $itemOwnerId32, '64' => $itemOwnerId64));
	}
}

# Pick a winner randomly
$winner = $ticketsArr[array_rand($ticketsArr)];
$winnerSteamId32 = $winner['32'];
$winnerSteamId64 = $winner['64'];

# Get trade token for winner
$stmt = $db->prepare('SELECT * FROM users WHERE steamId32 = :id');
$stmt->bindValue(':id', $winnerSteamId32);
$stmt->execute();

$userRow = $stmt->fetch();

$winnerTradeToken = $userRow['tradeToken'];

# Check the price of what the winner put in, to get their odds
$stmt = $db->prepare('SELECT * FROM currentPot WHERE ownerSteamId32 = :id');
$stmt->bindValue(':id', $winnerSteamId32);
$stmt->execute();

$winnerItems = $stmt->fetchAll();

$userPrice = 0;

foreach ($winnerItems as $item) {
	$itemPrice = $item['itemPrice'];

	$userPrice += $itemPrice;
}

# Calculate which items to keep and which to give to winner
# The site will take ~2%, but no more than 5%
$stmt = $db->query('SELECT * FROM currentPot ORDER BY itemPrice ASC');

$allItems = $stmt->fetchAll();

$keepPercentage = 0;
$itemsToKeep = array();
$itemsToGive = array();
$give = false;

foreach ($allItems as $item) {
	if ($give) {
		array_push($itemsToGive, $item);
		continue;
	}

	$itemPrice = intval($item['itemPrice']);
	$itemPercentage = $itemPrice / $totalPotPrice;

	if ($keepPercentage + $itemPercentage > 0.5) {
		array_push($itemsToGive, $item);
		$give = true;
		continue;
	}

	if ($keepPercentage + $itemPercentage >= 0.03 && $keepPercentage < 0.03) {
		array_push($itemsToKeep, $item);
		$give = true;
		continue;
	}

	array_push($itemsToKeep, $item);
	$keepPercentage += $itemPercentage;
}

# Generate JSON string for all items for database
$allUsersArr = array();
foreach ($allItems as $item) {
	array_push($allUsersArr, $item['ownerSteamId64']);
}
$allUserIDsStr = join(',', $allUsersArr);

# Get all user info for the steam user IDs
$key = getSteamAPIKey();
$usersInfoStr = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$key&steamids=$allUserIDsStr");

$allItemsInPrevGame = array();

foreach ($allItems as $item) {
	$id = $item['id'];
	$itemName = $item['itemName'];
	$itemPrice = $item['itemPrice'];
	$itemIcon = $item['itemIcon'];

	$itemOwnerId64 = $item['ownerSteamId64'];
	$steamUserInfo = getSteamProfileInfoForSteamID($usersInfoStr, $itemOwnerId64);

	$arr = array('itemID' => $id, 'itemSteamOwnerInfo' => $steamUserInfo, 'itemName' => $itemName, 'itemPrice' => $itemPrice, 'itemIcon' => $itemIcon);
	array_push($allItemsInPrevGame, $arr);
}

$allItemsJsonForDB = json_encode($allItemsInPrevGame);

# Get the id for the current round
$stmt = $db->query('SELECT * FROM history ORDER BY id DESC');
$mostRecentHistory = $stmt->fetch();
$roundId = $mostRecentHistory['id'];

# Update the history entry for this round to add all the items and the winner
$sql =
'UPDATE history
SET winnerSteamId32 = :id32, winnerSteamId64 = :id64, userPutInPrice = :userprice, potPrice = :potprice, allItemsJson = :allitemsjson, date = NOW()
WHERE id = :roundid';

$stmt = $db->prepare($sql);
$stmt->bindValue(':id32', $winnerSteamId32);
$stmt->bindValue(':id64', $winnerSteamId64);
$stmt->bindValue(':userprice', $userPrice);
$stmt->bindValue(':potprice', $totalPotPrice);
$stmt->bindValue(':allitemsjson', $allItemsJsonForDB);
$stmt->bindValue(':roundid', $roundId);
$stmt->execute();

# Clear the current pot
$stmt = $db->query('TRUNCATE TABLE currentPot');

# Echo out jsonSuccess with potOver = 1 and all of the items
$data = array(
	'winnerSteamId' => $winnerSteamId32,
	'winnerTradeToken' => $winnerTradeToken,
	'tradeItems' => $itemsToGive,
	'profitItems' => $itemsToKeep
);
echo jsonSuccess($data);
?>
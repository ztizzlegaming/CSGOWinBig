<?php
# Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

include 'default.php';
$db = getDB();

$maxPotCount = 60;

# Get password, owner steam ID, and all deposit items
$password = isset($_POST['password']) ? $_POST['password'] : null;
$tradeOwnerSteamId32 = isset($_POST['owner']) ? $_POST['owner'] : null;
$allItemsJson = isset($_POST['items']) ? $_POST['items'] : null;

if (is_null($password) || is_null($tradeOwnerSteamId32) || is_null($allItemsJson) || strlen($password) === 0 || strlen($tradeOwnerSteamId32) === 0 || strlen($allItemsJson) === 0) {
	echo jsonErr('One of the required fields was not sent correctly or was left blank, in deposit.php');
	return;
}

# Convert Steam ID to Steam64 ID
$tradeOwnerSteamId64 = steamid32ToSteamid64($tradeOwnerSteamId32);

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

# Create all items array from json
$allItems = json_decode($allItemsJson, true);

$totalPrice = 0;
$itemsArr = array();

# Get the bot's inventory
$botInventory = json_decode(file_get_contents("https://steamcommunity.com/profiles/76561198238743988/inventory/json/730/2"), true);

if ($botInventory['success'] !== true) {
	echo jsonErr('An error occured fetching the bot\'s inventory.');
	return;
}

$rgInventory = $botInventory['rgInventory'];
$rgDescriptions = $botInventory['rgDescriptions'];

# Loop through each item and get their name and price, and add them to the database
foreach ($allItems as $item) {
	$classId = $item['classId'];
	$instanceId = $item['instanceId'];
	$marketName = $item['marketName'];
	$rarityName = $item['rarityName'];
	$rarityColor = $item['rarityColor'];
	$price = $item['price'];
	$iconUrl = $item['iconUrl'];

	$sql = 
		'INSERT INTO currentPot
		(classId, instanceId, ownerSteamId64, ownerSteamId32, itemName, itemPrice, itemRarityName, itemRarityColor, itemIcon)
		VALUES
		(:class, :instance, :owner64, :owner32, :itemname, :itemprice, :itemrarityname, :itemraritycolor, :itemicon)';

	$stmt = $db->prepare($sql);
	$stmt->bindValue(':class', $classId);
	$stmt->bindValue(':instance', $instanceId);
	$stmt->bindValue(':owner64', $tradeOwnerSteamId64);
	$stmt->bindValue(':owner32', $tradeOwnerSteamId32);
	$stmt->bindValue(':itemname', $marketName);
	$stmt->bindValue(':itemprice', $price);
	$stmt->bindValue(':itemrarityname', $rarityName);
	$stmt->bindValue(':itemraritycolor', $rarityColor);
	$stmt->bindValue(':itemicon', $iconUrl);
	$stmt->execute();
}

# Check if this is the first, second, or another deposit
$stmt = $db->query('SELECT * FROM history ORDER BY id DESC');
$mostRecentHistory = $stmt->fetch();

# If items is not empty, then this is the first deposit
$mostRecentItems = $mostRecentHistory['allItemsJson'];
if (strlen($mostRecentItems) > 0) {
	# Put a new row in, starting the next pot/round. However, don't start the timer yet.
	$stmt = $db->query('INSERT INTO history (endTime) VALUES (0)');
	$startTimer = 0;
}

# If there is already a round going but the timer hasn't started yet, then check to see if there are multiple people in the round
if ($mostRecentHistory['endTime'] === '0') {
	if (strlen($mostRecentItems) === 0) {
		$stmt = $db->query('SELECT * FROM currentPot');
		$allItemsCurPot = $stmt->fetchAll();
		$steamId = $allItemsCurPot[0]['ownerSteamId64'];

		$startTimer = 0;

		foreach ($allItemsCurPot as $item) {
			if ($steamId !== $item['ownerSteamId64']) {
				# There are multiple people in the pot, start the timer
				$endTime = round(microtime(true) * 1000 + 120000.0);
				$stmt = $db->prepare('UPDATE history SET endTime = :endtime');
				$stmt->bindValue(':endtime', $endTime);
				$stmt->execute();

				$startTimer = 1;
			}
		}
	}
} else { # This is if the timer is already running. It doesn't matter what $startTimer is
	$startTimer = 0;
}

# Check if there are over the max number of items in the pot
# Get count of all items in pot
$stmt = $db->query('SELECT COUNT(*) FROM `currentPot`');
$countRow = $stmt->fetch();
$currentPotCount = $countRow['COUNT(*)'];

# If the pot is over the max, pick a winner, and tell the bot to stop the timer
if ($currentPotCount >= $maxPotCount) {
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

	# Get the round id, from mostRecentHistory, above
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
		'minDeposit' => 1,
		'potOver' => 1,
		'winnerSteamId' => $winnerSteamId32,
		'winnerTradeToken' => $winnerTradeToken,
		'tradeItems' => $itemsToGive,
		'profitItems' => $itemsToKeep
	);
	echo jsonSuccess($data);
	return;
}

# If the pot was not over the top, potOver = 0
$data = array('minDeposit' => 1, 'potOver' => 0, 'startTimer' => $startTimer);
echo jsonSuccess($data);
?>
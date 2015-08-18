<?php
# Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

include 'default.php';
$db = getDB();

$maxPotCount = 75;

# Get password, owner steam ID, and all deposit items
$password = isset($_POST['password']) ? $_POST['password'] : null;
$tradeOwnerSteamId32 = isset($_POST['owner']) ? $_POST['owner'] : null;
$allItemsJson = isset($_POST['items']) ? $_POST['items'] : null;

if (is_null($password) || is_null($tradeOwnerSteamId32) || is_null($allItemsJson) || strlen($password) === 0 || strlen($tradeOwnerSteamId32) === 0 || strlen($allItemsJson) === 0) {
	echo jsonErr('One of the required fields was not sent correctly or was left blank.');
	return;
}

# Convert Steam ID to Steam64 ID
$idParts = explode(':', $tradeOwnerSteamId32);
$authServer = intval($idParts[1]);
$accountNumber = intval($idParts[2]);
$tradeOwnerSteamId64 = $accountNumber * 2 + 76561197960265728 + $authServer;

# Get the password from config file and make sure it matches
$fileLoc = $_SERVER['DOCUMENT_ROOT'] . '/../../passwords.txt';
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

# Get the depositor's inventory
$depositorInventory = json_decode(file_get_contents("https://steamcommunity.com/profiles/$tradeOwnerSteamId64/inventory/json/730/2"), true);

if ($depositorInventory['success'] !== true) {
	echo jsonErr('An error occured fetching the depositor\'s inventory.');
	return;
}

$rgInventory = $depositorInventory['rgInventory'];
$rgDescriptions = $depositorInventory['rgDescriptions'];

$totalPrice = 0;
$itemsArr = array();

# Loop through each item and get their name and price
foreach ($allItems as $item) {
	$appId = $item['appid'];
	$contextId = $item['contextid'];
	$amount = $item['amount'];
	$assetId = $item['assetid'];

	# Check if any items are not for CSGO, just in case.
	if ($appId !== 730 || $contextId !== 2) {
		echo jsonErr('One of the items was not for CSGO.');
		return;
	}

	$inventoryItem = $rgInventory[$assetId];
	$classId = $inventoryItem['classid'];
	$instanceId = $inventoryItem['instanceid'];

	$descriptionItem = $rgDescriptions[$classId . '_' . $instanceId];
	
	$marketName = $descriptionItem['market_name'];
	$iconUrl = $descriptionItem['icon_url'];

	# Get all item tags
	$tags = $descriptionItem['tags'];

	# Loop through all tags and find the rarity tag
	$tagFound = false;
	foreach ($tags as $tag) {
		$tagCategory = $tag['category'];
		if ($tagCategory === 'Rarity') {
			$tagFound = true;

			$rarityName = $tag['name'];
			$rarityColor = $tag['color'];
		}
	}

	# Just in case for some reason the rarity couldn't be found
	if (!$tagFound) {
		$rarityName = '';
		$rarityColor = '';
	}

	# Get price of item from database
	$stmt = $db->prepare('SELECT * FROM items WHERE marketName = :name');
	$stmt->bindValue(':name', $marketName);
	$stmt->execute();

	$item = $stmt->fetch();

	$price = intval($item['avgPrice30Days']);

	# If the 30 day average is 0, set it to the 7 day average
	if ($price === 0) {
		$price = intval($item['avgPrice7Days']);
	}

	# If the 7 day average is 0 again, set it to the current price
	if ($price === 0) {
		$price = intval($item['currentPrice']);
	}

	if ($price === 0) {
		$price = intval($item['suggestedPriceMin']);
	}

	# If all of those are 0, set it to the Steam market price
	if ($price === 0) {
		$hash = urlencode($marketName);
		$marketObj = json_decode(file_get_contents("http://steamcommunity.com/market/priceoverview/?currency=1&appid=730&market_hash_name=$hash"), true);
		if ($marketObj['success'] !== true) {
			echo jsonErr('An error occured while fetching market price for an item.');
			return;
		}

		$medianPrice = $marketObj['median_price'];
		$lowestPrice = $marketObj['lowest_price'];

		if (!isset($medianPrice) && !isset($lowestPrice)) {
			echo jsonErr('One or more items was not found on the steam market place.');
			return;
		}

		if (isset($medianPrice)) {
			$price = doubleval(substr($medianPrice, 1)) * 100;
		} else {
			$price = doubleval(substr($lowestPrice, 1)) * 100;
		}
	}

	$arr = array(
		'classId' => $classId,
		'instanceId' => $instanceId,
		'marketName' => $marketName,
		'rarityName' => $rarityName,
		'rarityColor' => $rarityColor,
		'price' => $price,
		'iconUrl' => $iconUrl
	);

	# Just in case the SteamBot decides to have the amount more than 1
	for ($i1=0; $i1 < $amount; $i1++) { 
		array_push($itemsArr, $arr);
		$totalPrice += $price;
	}
}

# Check to see if they reached the minimum deposit
if ($totalPrice < 100) {
	$data = array('minDeposit' => 0);
	echo jsonSuccess($data);
	return;
}

# Loop through all items again, adding them to the pot database
foreach ($itemsArr as $item) {
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

# Get count of all items in pot
$stmt = $db->query('SELECT COUNT(*) FROM `currentPot`');
$countRow = $stmt->fetch();
$currentPotCount = $countRow['COUNT(*)'];

# Check if this deposit put the pot over the top
# If it did, generate the array of tickets and pick a winner
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

		if ($keepPercentage + $itemPercentage > 0.05) {
			array_push($itemsToGive, $item);
			$give = true;
			continue;
		}

		if ($keepPercentage + $itemPercentage >= 0.02 && $keepPercentage < 0.02) {
			array_push($itemsToKeep, $item);
			$give = true;
			continue;
		}

		array_push($itemsToKeep, $item);
		$keepPercentage += $itemPercentage;
	}

	$allItemsJsonForDB = json_encode($allItems);

	# Add this game to the past games database
	$sql =
		'INSERT INTO history
		(winnerSteamId32, winnerSteamId64, userPutInPrice, potPrice, allItemsJson, date)
		VALUES
		(:id32, :id64, :userprice, :potprice, :allitemsjson, NOW())';

	$stmt = $db->prepare($sql);
	$stmt->bindValue(':id32', $winnerSteamId32);
	$stmt->bindValue(':id64', $winnerSteamId64);
	$stmt->bindValue(':userprice', $userPrice);
	$stmt->bindValue(':potprice', $totalPotPrice);
	$stmt->bindValue(':allitemsjson', $allItemsJsonForDB);
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
$data = array('minDeposit' => 1, 'potOver' => 0);
echo jsonSuccess($data);
?>
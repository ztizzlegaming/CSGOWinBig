<?php
# Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

include 'default.php';
$db = getDB();

$maxPotCount = 100;

# Get password, owner steam ID, and all deposit items
$password = isset($_POST['password']) ? $_POST['password'] : null;
$tradeOwnerSteamId = isset($_POST['owner']) ? $_POST['owner'] : null;
$allItemsJson = isset($_POST['items']) ? $_POST['items'] : null;

if (is_null($password) || is_null($tradeOwnerSteamId) || is_null($allItemsJson) || strlen($password) === 0 || strlen($tradeOwnerSteamId) === 0 || strlen($allItemsJson) === 0) {
	echo jsonErr('One of the required fields was not sent correctly or was left blank.');
	return;
}

# Convert Steam ID to Steam64 ID
$idParts = explode(':', $tradeOwnerSteamId);
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
	if ($appId !== 730) {
		echo jsonErr('One of the items was not for CSGO.');
		return;
	}

	$inventoryItem = $rgInventory[$assetId];
	$classId = $inventoryItem['classid'];
	$instanceId = $inventoryItem['instanceid'];

	$descriptionItem = $rgDescriptions[$classId . '_' . $instanceId];
	
	$marketHashName = urlencode($descriptionItem['market_hash_name']);
	$marketName = $descriptionItem['market_name'];
	$iconUrl = $descriptionItem['icon_url'];

	# Get rarity of item
	$tags = $descriptionItem['tags'];
	$rarityTag = $tags[4];
	$rarityName = $rarityTag['name'];
	$rarityColor = $rarityTag['color'];

	# Get price of item from Steam market
	$marketObj = json_decode(file_get_contents("http://steamcommunity.com/market/priceoverview/?currency=1&appid=730&market_hash_name=$marketHashName"), true);
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

	$arr = array(
		'contextId' => $contextId,
		'assetId' => $assetId,
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
	$contextId = $item['contextId'];
	$assetId = $item['assetId'];
	$marketName = $item['marketName'];
	$rarityName = $item['rarityName'];
	$rarityColor = $item['rarityColor'];
	$price = $item['price'];
	$iconUrl = $item['iconUrl'];

	$sql = 
		'INSERT INTO currentPot
		(contextId, assetId, ownerSteamId, ownerSteamId32, itemName, itemPrice, itemRarityName, itemRarityColor, itemIcon)
		VALUES
		(:context, :asset, :owner, :owner32, :itemname, :itemprice, :itemrarityname, :itemraritycolor, :itemicon)';

	$stmt = $db->prepare($sql);
	$stmt->bindValue(':context', $contextId);
	$stmt->bindValue(':asset', $assetId);
	$stmt->bindValue(':owner', $tradeOwnerSteamId64);
	$stmt->bindValue(':owner32', $tradeOwnerSteamId);
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
		$itemOwner = $item['ownerSteamID32'];
		$itemPrice = $item['itemPrice'];

		$totalPotPrice += $itemPrice;

		for ($i1=0; $i1 < $tickets; $i1++) { 
			array_push($ticketsArr, $itemOwner);
		}
	}

	# Pick a winner randomly
	$winnerSteamID = $ticketsArr[array_rand($ticketsArr)];

	# Check what the winner put in, to get their odds
	$stmt = $db->prepare('SELECT * FROM currentPot WHERE ownerSteamID = :id');
	$stmt->bindValue(':id', $winnerSteamID);
	$stmt->execute();

	$winnerItems = $stmt->fetchAll();

	$userPrice = 0;

	foreach ($winnerItems as $item) {
		$itemPrice = $item['itemPrice'];

		$userPrice += $itemPrice;
	}

	# Calculate which items to keep and which to give to winner
	# The site will take ~2%, but no more than 5%
	$stmt = $db->query('SELECT * FROM currentPot ORDER BY itemPrice DESC');

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
			$give = true;
			continue;
		}

		if ($keepPercentage + $itemPercentage >= 0.02 && $keepPercentage < 0.02) {
			array_push($itemsToKeep, $item);
			$give = true;
			continue;
		}

		array_push($itemsToKeep, $item);
	}

	$allItemsJsonForDB = json_encode($allItems);

	# Add this game to the past games database
	$stmt = $db->prepare('INSERT INTO history (winnerSteamID, userPutInPrice, potPrice, allItems) VALUES (:id, :userprice, :potprice, :allitems)');
	$stmt->bindValue(':id', $winnerSteamID);
	$stmt->bindValue(':userprice', $userPrice);
	$stmt->bindValue(':potprice', $totalPotPrice);
	$stmt->bindValue(':allitemsJson', $allItemsJsonForDB);
	$stmt->execute();

	# Clear the current pot
	$stmt = $db->query('TRUNCATE TABLE currentPot');

	# Echo out jsonSuccess with potOver = 1 and all of the items
	$data = array('minDeposit' => 1, 'potOver' => 1, 'winnerSteamID' => $winnerSteamID, 'tradeItems' => $itemsToGive, 'profitItems' => $itemsToKeep);
	echo jsonSuccess($data);
	return;
}

# If the pot was not over the top, potOver = 0
$data = array('minDeposit' => 1, 'potOver' => 0);
echo jsonSuccess($data);
?>
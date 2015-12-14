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
	echo jsonErr('One of the required fields was not sent correctly or was left blank, in check-items.php ' . $password . ' \ ' . $tradeOwnerSteamId32 . ' \ ' . $allItemsJson);
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
		$marketObj = json_decode(file_get_contents('http://steamcommunity.com/market/priceoverview/?currency=1&appid=730&market_hash_name=' . $hash), true);

		/* if ($marketObjStr === false) {
			echo jsonErr('An error occured while doing the url request');
			return;
		}

		$marketObj = json_decode($marketObjStr, true); */

		if ($marketObj['success'] !== true) {
			echo jsonErr('An error occured while fetching market price for an item.');#' Server response: |' . $marketObjStr . '|');
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

# 20 means $0.20, or 20 cents
$minDeposit = $totalPrice >= 20 ? 1 : 0;

$allItemsObj = json_encode($itemsArr);

$data = array('totalPrice' => $totalPrice, 'minDeposit' => $minDeposit, 'allItems' => $allItemsObj);

echo jsonSuccess($data);
?>
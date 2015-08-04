<?php
# Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

include 'default.php';
$db = getDB();

$maxPotCount = 100;

# Get password, owner steam ID, and all deposit items
$password = isset($_POST['password']) ? $_POST['password'] : null;
$tradeOwnerSteamId = isset($_POST['owner']) ? $_POST['owner'] : null;
$allItemsJson = isset($_POST['allItems']) ? $_POST['allItems'] : null;

if (is_null($tradeOffer) || is_null($allItemsJson)  || is_null($password) || strlen($tradeOwner) === 0 || strlen($allItemsJson) === 0 || strlen($password) === 0) {
	echo jsonErr('One of the required fields was not sent correctly or was left blank.');
	return;
}

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
$allItems = json_decode($allItemsJson);

# Get the depositor's inventory
$depositorInventory = json_decode(file_get_contents("https://steamcommunity.com/profiles/$tradeOwnerSteamId/inventory/json/730/2"));

if ($depositorInventory['success'] !== true) {
	echo jsonErr('An error occured fetching the depositor\'s inventory.');
	return;
}

$rgInventory = $allItems['rgInventory'];
$rgDescriptions = $allItems['rgDescriptions'];

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
	
	$marketHashName = $descriptionItem['market_hash_name'];
	$marketName = $descriptionItem['market_name'];
	$iconUrl = $descriptionItem['icon_url'];

	# Get rarity of item
	$tags = $descriptionItem['tags'];
	$rarityTag = $tags[5];
	$rarityName = $rarityTag['name'];
	$rarityColor = $rarityTag['color'];

	# Get price of item from Steam market
	$marketObj = json_decode(file_get_contents("http://steamcommunity.com/market/priceoverview/?currency=1&appid=730&market_hash_name=$marketHashName"));
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
		$price = intval(substr($medianPrice, 1)) * 100;
	} else {
		$price = intval(substr($lowestPrice, 1)) * 100;
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
	array_push($itemsArr, $arr);

	$totalPrice += $price;
}

# Check to see if they reached the minimum deposit
if ($totalPrice < 100) {
	echo jsonErr('The minimum deposit was not reached.');
	return;
}

# Check if pot items count is greater than limit
# If it is greater, then these items will go into the next pot
if ($currentPotCount >= $maxPotCount) {
	$table = 'nextPot';
} else {
	$table = 'currentPot';
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
		(contextId, assetId, ownerSteamId, itemName, itemPrice, itemRarityName, itemRarityColor, itemIcon)
		VALUES
		(:context, :asset, :owner, :itemname, :itemprice, :itemrarityname, :itemraritycolor, :itemicon)';

	$stmt = $db->prepare($sql);
	$stmt->bindValue(':context', $contextId);
	$stmt->bindValue(':asset', $assetId);
	$stmt->bindValue(':owner', $tradeOwnerSteamId);
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
		$itemOwner = $item['ownerSteamID'];
		$itemPrice = $item['itemPrice'];

		$totalPotPrice += $itemPrice;

		for ($i1=0; $i1 < $tickets; $i1++) { 
			array_push($ticketsArr, $itemOwner);
		}
	}

	$winnerSteamID = $ticketsArr[array_rand($ticketsArr)];

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


	# Add this game to the past games database
	$stmt = $db->prepare('INSERT INTO history (winnerSteamID, userPutInPrice, potPrice, allItems) VALUES (:id, :userprice, :potprice, :allitems)');
	$stmt->bindValue(':id', $winnerSteamID);
	$stmt->bindValue(':userprice', $userPrice);
	$stmt->bindValue(':potprice', $totalPotPrice);
	$stmt->bindValue(':allitems', $); # Add this later, once the bot is working and I can see what all I would need for it.
	$stmt->execute();

	# Clear the current pot
	$stmt = $db->query('TRUNCATE TABLE currentPot');

	# Get items from nextPot and put them in currentPot
	$stmt = $db->query('INSERT INTO currentPot SELECT * FROM nextPot');

	# Clear nextPot
	$stmt = $db->query('TRUNCATE TABLE nextPot');

	# Echo out jsonSuccess
	$data = array('potOver' => 1, 'tradeItems' => $itemsToGive, 'profitItems' => $itemsToKeep);
	echo jsonSuccess();
	return;
}

# If the pot was not over the top, echo jsonSuccess with just a message
echo jsonSuccess(array('potOver' => 0));





# Get bot's inventory for getting item information
$botInventory = json_decode(file_get_contents('https://steamcommunity.com/profiles/76561198238743988/inventory/json/730/2'));

$allInventoryItems = $botInventory['rgDescriptions'];

# Get count of all items in pot
$stmt = $db->query('SELECT COUNT(*) FROM `currentPot`');
$countRow = $stmt->fetch();
$currentPotCount = $countRow['COUNT(*)'];

# Check if pot items count is greater than limit
# If it is greater, then these items will go into the next pot
if ($currentPotCount >= $maxPotCount) {
	$table = 'nextPot';
} else {
	$table = 'currentPot';
}

# Insert items into database
$allItems = json_decode($allItemsJson);
foreach ($allItems as $item) {
	$name = $item['name'];
	$price = $item['price'];

	$appId = $item['appId'];
	$contextId = $item['contextId'];
	$assetId = $item['assetId'];

	$itemInventoryDesc = findItemInInventory($name);
	$itemIcon = $itemInventoryDesc['icon_url'];

	$itemIconURL = "http://steamcommunity-a.akamaihd.net/economy/image/$itemIcon/360fx360f";

	$stmt = $db->prepare("INSERT INTO $table (ownerSteamID, itemName, itemPrice, appId, contextId, assetId) VALUES (:steamid, :name, :price, :appid, :contextid, assetid)");
	$stmt->bindValue(':steamid', $tradeOwnerSteamId);
	$stmt->bindValue(':name', $name);
	$stmt->bindValue(':price', $price);
	$stmt->bindValue(':appid', $appId);
	$stmt->bindValue(':contextid', $contextId);
	$stmt->bindValue(':assetid', $assetId);
	$stmt->execute();
}

# Check if this deposit put the pot over the top
# If it did, generate the array of tickets and pick a winner
if ($currentPotCount >= $maxPotCount) {
	$stmt = $db->query('SELECT * FROM currentPot');
	$allPotItems = $stmt->fetchAll();

	$ticketsArr = array();
	$totalPotPrice = 0;

	foreach ($allPotItems as $item) {
		$itemOwner = $item['ownerSteamID'];
		$itemPrice = $item['itemPrice'];

		$totalPotPrice += $itemPrice;

		for ($i1=0; $i1 < $tickets; $i1++) { 
			array_push($ticketsArr, $itemOwner);
		}
	}

	$winnerSteamID = $ticketsArr[array_rand($ticketsArr)];

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
	foreach ($allItems as $item) {
		$itemPrice = intval($item['itemPrice']);
		$itemPercentage = $itemPrice / $totalPotPrice;

		if ($keepPercentage + $itemPercentage > 0.05) {
			break;
		}

		if ($keepPercentage + $itemPercentage >= 0.02 && $keepPercentage < 0.02) {
			array_push($itemsToKeep, $item);
			break;
		}

		array_push($itemsToKeep, $item);
	}


	# Add this game to the past games database
	$stmt = $db->prepare('INSERT INTO history (winnerSteamID, userPutInPrice, potPrice, allItems) VALUES (:id, :userprice, :potprice, :allitems)');
	$stmt->bindValue(':id', $winnerSteamID);
	$stmt->bindValue(':userprice', $userPrice);
	$stmt->bindValue(':potprice', $totalPotPrice);
	$stmt->bindValue(':allitems', $); # Add this later, once the bot is working and I can see what all I would need for it.
	$stmt->execute();

	# Clear the current pot
	$stmt = $db->query('TRUNCATE TABLE currentPot');

	# Get items from nextPot and put them in currentPot
	$stmt = $db->query('INSERT INTO currentPot SELECT * FROM nextPot');

	# Clear nextPot
	$stmt = $db->query('TRUNCATE TABLE nextPot');
}

# echo out jsonSuccess stuff, and if it is the end of a round, echo the winner and all items
?>
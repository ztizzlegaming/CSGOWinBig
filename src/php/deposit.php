<?php
include 'default.php';
$db = getDB();

$maxPotCount = 100;

# Get owner steam ID and all deposit items
$tradeOwnerSteamID = isset($_POST['owner']) ? $_POST['owner'] : null;
$allItemsJson = isset($_POST['allItems']) ? $_POST['allItems'] : null;

if (is_null($tradeOffer) || is_null($allItemsJson) || strlen($tradeOwner) === 0 || strlen($allItemsJson) === 0) {
	echo jsonErr('One of the required fields was not sent correctly or was left blank.');
	return;
}

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
	$stmt->bindValue(':steamid', $tradeOwnerSteamID);
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
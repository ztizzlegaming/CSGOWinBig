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
$query = $db->query('SELECT COUNT(*) FROM `currentPot`');
$countRow = $query->fetch();
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

	$query = $db->prepare("INSERT INTO $table (ownerSteamID, itemName, itemPrice) VALUES (:steamid, :name, :price)");
	$query->bindValue(':steamid', $tradeOwnerSteamID);
	$query->bindValue(':name', $name);
	$query->bindValue(':price', $price);
	$query->execute();
}

# Check if this deposit put the pot over the top
# If it did, generate the array of tickets and pick a winner
if ($currentPotCount >= $maxPotCount) {
	$query = $db->query('SELECT * FROM currentPot');
	$allPotItems = $query->fetchAll();

	$ticketsArr = array();

	foreach ($allPotItems as $item) {
		$itemOwner = $item['ownerSteamID'];
		$tickets = $item['itemPrice'];

		for ($i1=0; $i1 < $tickets; $i1++) { 
			array_push($ticketsArr, $itemOwner);
		}
	}

	$winnerSteamID = $ticketsArr[array_rand($ticketsArr)];

	# Add this game to the past games database
	$stmt = $db->prepare('INSERT INTO history (winnerSteamID, userPutInPrice, potPrice, allItems) VALUES (:id, :userprice, :potprice, :allitems)');
	$stmt->bindValue(':id', $winnerSteamID);
	$stmt->bindValue(':userprice', $);
	$stmt->bindValue(':potprice', $);
	$stmt->bindValue(':allitems', $);
	$stmt->execute();

	# Clear the current pot
	$stmt = $db->query('TRUNCATE TABLE currentPot');

	# Get items from nextPot and put them in currentPot
	$stmt = $db->query('INSERT INTO currentPot SELECT * FROM nextPot');

	# Clear nextPot
	$stmt = $db->query('TRUNCATE TABLE nextPot');
}
?>
<?php
include 'default.php';
$db = getDB();

$query = getVar('query');

if (is_null($query)) {
	echo jsonErr('The search query was not sent correctly. Please refresh the page and try again.');
	return;
}

$stmt = $db->prepare('SELECT * FROM items WHERE marketName LIKE :query');
$stmt->bindValue(':query', "%$query%");
$stmt->execute();

if ($stmt->rowCount() === 0) {
	echo jsonErr('No items were found for your query. Please try again.');
	return;
}


$allItems = $stmt->fetchAll();
$results = array();

foreach ($allItems as $item) {
	$name = $item['marketName'];
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
		$hash = urlencode($name);
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

	$arr = array('name' => $name, 'price' => $price);
	array_push($results, $arr);
}

$data = array('allItems' => $results);
echo jsonSuccess($data);
?>
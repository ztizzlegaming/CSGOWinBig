<?php
include 'default.php';
$db = getDB();

$alreadyLoadedCount = isset($_GET['count']) ? $_GET['count'] : null;

$alreadyLoadedCount = (int) $alreadyLoadedCount;

if ($alreadyLoadedCount === 0) {
	$sql = 'SELECT * FROM history ORDER BY id DESC LIMIT 0, 11';
} else {
	$sql = "SELECT * FROM history ORDER BY id DESC LIMIT $alreadyLoadedCount, 10";
}



$stmt = $db->query($sql);
$allRounds = $stmt->fetchAll();

$allUserSteam64Ids = array();

foreach ($allRounds as $round) {
	$id = $round['winnerSteamId64'];
	array_push($allUserSteam64Ids, $id);
}

$allUserSteam64IdsStr = join(',', $allUserSteam64Ids);

$apiKey = getSteamAPIKey();
$usersInfoStr = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$apiKey&steamids=$allUserSteam64IdsStr");

$roundsArr = array();

$count = 0;
foreach ($allRounds as $round) {
	if (strlen($round['allItemsJson']) === 0) {
		continue;
	}
	if ($count === 10) {
		continue;
	}

	$count++;

	$id = $round['id'];
	$winnerSteamId64 = $round['winnerSteamId64'];
	
	$winnerInfo = getSteamProfileInfoForSteamID($usersInfoStr, $winnerSteamId64);

	$userPutInPrice = $round['userPutInPrice'];
	$potPrice = $round['potPrice'];
	$allItemsJson = $round['allItemsJson'];

	$arr = array('id' => $id, 'winnerInfo' => $winnerInfo, 'userPutInPrice' => $userPutInPrice, 'potPrice' => $potPrice, 'allItemsJson' => $allItemsJson);
	array_push($roundsArr, $arr);
}

echo jsonSuccess(array('rounds' => $roundsArr));
?>
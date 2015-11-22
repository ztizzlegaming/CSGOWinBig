<?php
# Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

session_start();
include 'default.php';
include 'SteamAuthentication/steamauth/userInfo.php';

$db = getDB();

$steamID = isset($_SESSION['steamid']) ? $_SESSION['steamid'] : null;

$loginStatus = !is_null($steamID) && isset($steamID) ? 1 : 0;

if ($loginStatus === 1) {
	# Get user's 64bit Steam ID
	$steam64Id = $steamprofile['steamid'];

	# Check if the user has entered their trade link
	$stmt = $db->prepare('SELECT * FROM users WHERE steamId64 = :id');
	$stmt->bindValue(':id', $steam64Id);
	$stmt->execute();

	# First, check the count
	if ($stmt->rowCount() === 0) {
		$tradeToken = null;
	} else {
		$userInfo = $stmt->fetch();
		$tradeToken = $userInfo['tradeToken'];
	}
} else {
	# This is only for when they are not logged in, so the trade token wouldn't matter
	$tradeToken = null;
}

# Home page stuff: Minimum bet ($0.20), total games played, total money won, biggest pot ever
$stmt = $db->query('SELECT * FROM history');
$allGames = $stmt->fetchAll();

$totalGamesPlayed = count($allGames);

$totalMoneyWon = 0;
foreach ($allGames as $game) {
	$totalMoneyWon += intval($game['potPrice']);
}

# Get all games from the past 24 hours for home page info shit
/* $stmt = $db->query('SELECT * FROM history WHERE date > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND date <= NOW()');

$gamesPlayedToday = $stmt->rowCount();

$allFromPastDay = $stmt->fetchAll();
$moneyWonToday = 0;
$biggestPotToday = 0;

foreach ($allFromPastDay as $pot) {
	$potPrice = $pot['potPrice'];

	$moneyWonToday += $potPrice;

	if ($potPrice > $biggestPotToday) {
		$biggestPotToday = $potPrice;
	}
} */

# Get the max pot price ever
$stmt = $db->query('SELECT MAX(potPrice) FROM history');
$row = $stmt->fetch();
$biggestPotEver = $row['MAX(potPrice)'];

$infoArr = array(
	'totalGamesPlayed' => $totalGamesPlayed,
	'totalMoneyWon' => $totalMoneyWon,
	'biggestPotEver' => $biggestPotEver
);

$data = array(
	'loginStatus' => $loginStatus,
	'userInfo' => $loginStatus === 1 ? $steamprofile : null,
	'tradeToken' => $tradeToken,
	'info' => $infoArr
);
echo jsonSuccess($data);
?>
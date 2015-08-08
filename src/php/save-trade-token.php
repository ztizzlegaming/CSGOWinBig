<?php
session_start();
include 'default.php';
include 'SteamAuthentication/steamauth/userInfo.php';

$db = getDB();

$tradeUrl = isset($_POST['tradeUrl']) ? $_POST['tradeUrl'] : null;

if (is_null($tradeUrl) || strlen($tradeUrl) === 0) {
	echo jsonErr('The required field was not sent.');
	return;
}

# Check if user is logged in
if (!isset($_SESSION['steamid'])) {
	echo jsonErr('You are not logged in.');
	return;
}

if (!filter_var($tradeUrl), FILTER_VALIDATE_URL)) {
	echo jsonSuccess(array('valid' => 0));
	return;
}

$query = urldecode($tradeUrl, PHP_URL_QUERY);

parse_str($query, $queryArr);

$tradeToken = $queryArr['token'];

# Get steam id
$steamUserId = intval($steamprofile['steamid']);

# Convert steam 64 id to steam 32 id
$steam32Id = ($steamUserId - (76561197960265728 + ($steamUserId % 2))) / 2;

$stmt = $db->prepare('INSERT INTO users (steamId32, steamId64, tradeToken) VALUES (:id32, :id64, :token)');
$stmt->bindValue(':id32', $steam32Id);
$stmt->bindValue(':id64', $steamUserID);
$stmt->bindValue(':token', $tradeToken);
$stmt->execute();

echo jsonSuccess(array('valid' => 1));
?>
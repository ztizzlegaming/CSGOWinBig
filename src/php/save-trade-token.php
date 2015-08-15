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

if (!filter_var($tradeUrl, FILTER_VALIDATE_URL)) {
	echo jsonSuccess(array('valid' => 0, 'errMsg' => 'The provided url was not valid.'));
	return;
}

$query = parse_url($tradeUrl, PHP_URL_QUERY);

parse_str($query, $queryArr);

$tradeToken = isset($queryArr['token']) ? $queryArr['token'] : null;

if (is_null($tradeToken) || strlen($tradeToken) === 0) {
	echo jsonSuccess(array('valid' => 0, 'errMsg' => 'Your trade token could not be found in the url.'));
	return;
}

# Get steam id
$steamUserId = intval($steamprofile['steamid']);

# Convert steam 64 id to steam 32 id
$steam32IdEnd = ($steamUserId - (76561197960265728 + ($steamUserId % 2))) / 2;
$steam32IdMid = $steamUserId % 2;
$steam32Id = "STEAM_0:$steam32IdMid:$steam32IdEnd";

$stmt = $db->prepare('INSERT INTO users (steamId32, steamId64, tradeToken) VALUES (:id32, :id64, :token)');
$stmt->bindValue(':id32', $steam32Id);
$stmt->bindValue(':id64', $steamUserId);
$stmt->bindValue(':token', $tradeToken);
$stmt->execute();

echo jsonSuccess(array('valid' => 1, 'tradeToken' => $tradeToken));
?>
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

$stmt = $db->prepare('UPDATE users SET tradeToken = :token WHERE steamId64 = :id64');
$stmt->bindValue(':id64', $steamUserId);
$stmt->bindValue(':token', $tradeToken);
$stmt->execute();

echo jsonSuccess(array('valid' => 1));
?>
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
		$tradeTokenEntered = 0;
	} else {
		# Get their field, and see if tradeToken is set
		# Technically if the field is in the username it should always be set, but it never hurts to check
		$userRow = $stmt->fetch();
		$tradeToken = $userRow['tradeToken'];
		if (strlen($tradeToken) === 0) {
			$tradeTokenEntered = 0;
		} else {
			$tradeTokenEntered = 1;
		}
	}
} else {
	# This is only for when they are not logged in, so the trade token wouldn't matter
	$tradeTokenEntered = null;
}

$data = array('loginStatus' => $loginStatus, 'userInfo' => $loginStatus === 1 ? $steamprofile : null, 'tradeTokenEntered' => $tradeTokenEntered);
echo jsonSuccess($data);
?>
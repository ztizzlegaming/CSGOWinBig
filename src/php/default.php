<?php
function getDB() {
	$dbHost = 'localhost';
	$db     = 'jordantu_csgowinbig';
	$dbUser = "jordantu_ztizzle";

	# Get database password from outside of web root
	$fileLoc = $_SERVER['DOCUMENT_ROOT'] . '/../../passwords.txt';
	if (file_exists($fileLoc)) {
		$fh = fopen($fileLoc, 'r');
		$jsonStr = fgets($fh);
		$arr = json_decode($jsonStr, true);
		$dbPass = $arr['default-password'];
		fclose($fh);
	} else {
		die('no file found');
	}

	$db = new PDO("mysql:host=$dbHost;dbname=$db;charset=utf8", $dbUser, $dbPass);
	return $db;
}

function getSteamProfileInfoForSteamID($allUsersInfoStr, $steamIDToFind) {
	$allUsersInfo = json_decode($allUsersInfoStr, true);
	$players = $allUsersInfo['response']['players'];
	
	foreach ($players as $player) {
		$steamID = $player['steamid'];
		
		if ($steamIDToFind === $steamID) {
			return $player;
		}
	}

	# If the user is not found, then return false
	return false;
}

function jsonSuccess($data) {
	return json_encode(array('success' => 1, 'data' => $data));
}

function jsonErr($errMsg) {
	return json_encode(array('success' => 0, 'errMsg' => $errMsg));
}

function getSteamAPIKey($type) {
	switch ($type) {
		case 'login':
			return '1FBC1D48247E517DB7CE37C093450807'; # Key for testbotztizzle

		case 'chat':
			return 'C3755F409079584CFEAF47C0C9164283'; # Key for jturley128

		case 'pot':
			return ''; # Add this later once I start working on the items, once the chat is working.
		
		default:
			return null;
	}
}
?>
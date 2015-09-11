<?php
# Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

function getDB() {
	$dbHost = 'localhost';
	$db     = 'csgowinb_default';
	$dbUser = 'csgowinb_ztizzle';

	# Get database password from outside of web root
	$fileLoc = $_SERVER['DOCUMENT_ROOT'] . '/../passwords.txt';
	if (file_exists($fileLoc)) {
		$fh = fopen($fileLoc, 'r');
		$jsonStr = fgets($fh);
		$arr = json_decode($jsonStr, true);
		$dbPass = $arr['default-password'];
		fclose($fh);
	} else {
		die('no file found');
	}

	$db = new PDO("mysql:host=$dbHost;dbname=$db;charset=utf8mb4", $dbUser, $dbPass);
	return $db;
}

function getSteamProfileInfoForSteamID($allUsersInfoStr, $steamIDToFind) {
	$allUsersInfo = json_decode($allUsersInfoStr, true);
	$players = $allUsersInfo['response']['players'];
	
	foreach ($players as $player) {
		$steamID = $player['steamid'];
		$player['personaname'] = htmlentities($player['personaname']);
		
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

function getSteamAPIKey() {
	$fileLoc = $_SERVER['DOCUMENT_ROOT'] . '/../passwords.txt';
	if (file_exists($fileLoc)) {
		$fh = fopen($fileLoc, 'r');
		$jsonStr = fgets($fh);
		$arr = json_decode($jsonStr, true);
		$key = $arr['steamAPIKey'];
		fclose($fh);
		return $key;
	} else {
		die('no file found');
	}
}

function postVar($varName) {
	$var = isset($_POST[$varName]) ? $_POST[$varName] : null;

	if (is_null($var) || strlen($var) === 0) {
		return null;
	} else {
		return $var;
	}
}

function getVar($varName) {
	$var = isset($_GET[$varName]) ? $_GET[$varName] : null;

	if (is_null($var) || strlen($var) === 0) {
		return null;
	} else {
		return $var;
	}
}
?>
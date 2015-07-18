<?php
session_start();
include 'default.php';

$steamID = $_SESSION['steamid'];

if (!is_null($steamID) && isset($steamID)) {
	# Make call to steam api for the user logged in's information
	$apiKey = getSteamAPIKey();
	$userInfoStr = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$apiKey&steamids=$steamID");
	$userInfo = getSteamProfileInfoForSteamID($userInfoStr, $steamID);

	$loginStatus = 1;
	$steamProfileName = $userInfo['personaname'];
	$steamProfileID = $userInfo['steamid'];
} else {
	$loginStatus = 0;
	$steamProfileName = '';
	$steamProfileID = '';
}

$data = array('loginStatus' => $loginStatus, 'steamProfileName' => $steamProfileName, 'steamProfileID' => $steamProfileID);
echo jsonSuccess($data);
?>
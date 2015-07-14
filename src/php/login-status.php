<?php
session_start();
include 'default.php';
include 'steamauth/userInfo.php';


if (isset($_SESSION['steamid'])) {
	$loginStatus = 1;
	$steamProfileName = $steamprofile['personaname'];
	$steamProfileID = $steamprofile['steamid'];
} else {
	$loginStatus = 0;
	$steamProfileName = '';
	$steamProfileID = '';
}

$data = array('loginStatus' => $loginStatus, 'steamProfileName' => $steamProfileName, 'steamProfileID' => $steamProfileID);
echo jsonSuccess($data);
?>
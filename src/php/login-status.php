<?php
session_start();
include 'default.php';
include 'steamauth/userInfo.php';


if (isset($_SESSION['steamid'])) {
	$loginStatus = 1;
	$steamProfileName = $steamprofile['personaname'];
} else {
	$loginStatus = 0;
	$steamProfileName = '';
}

$data = array('loginStatus' => $loginStatus, 'steamProfileName' => $steamProfileName);
echo jsonSuccess($data);
?>
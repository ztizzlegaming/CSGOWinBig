<?php
# Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

session_start();
include 'default.php';
include 'SteamAuthentication/steamauth/userInfo.php';

$steamID = isset($_SESSION['steamid']) ? $_SESSION['steamid'] : null;

$loginStatus = !is_null($steamID) && isset($steamID) ? 1 : 0;

$data = array('loginStatus' => $loginStatus, 'userInfo' => $loginStatus === 1 ? $steamprofile : null);
echo jsonSuccess($data);
?>
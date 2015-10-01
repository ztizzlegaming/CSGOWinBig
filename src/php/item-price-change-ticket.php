<?php
session_start();
include 'default.php';
include 'SteamAuthentication/steamauth/userInfo.php';

$steamID = isset($_SESSION['steamid']) ? $_SESSION['steamid'] : null;
$loginStatus = !is_null($steamID) && isset($steamID) ? 1 : 0;

$name = postVar('name');
$price = postVar('price');

if (is_null($name) || is_null($price)) {
	echo jsonErr('One of the required fields was not sent successfully.');
	return;
}

if ($loginStatus == 1) {
	$to = 'items@csgowinbig.com';
	$subject = 'Item Price Change Ticket';
	$message = "An item price change ticket has been submitted.\nName: $name\nPrice: $price";
	mail($to, $subject, $message);
	
	echo jsonSuccess(array('message' => 'Your ticket has successfully been submitted. Thank you!'));
} else {
	echo jsonErr('You are not logged in.');
}

echo jsonSuccess(array('message' => 'Your ticket has successfully been submitted. Thank you!'));
?>

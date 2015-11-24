<?php
# Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

session_start();
include 'default.php';
include 'SteamAuthentication/steamauth/userInfo.php';
$db = getDB();

if (!isset($_SESSION['steamid'])) {
	echo jsonErr('You are not logged in.');
	return;
}

$text = isset($_POST['text']) ? $_POST['text'] : null;

if (is_null($text) || strlen($text) === 0) {
	echo jsonErr('The required text for the message was not sent correctly or was left blank. Please refresh and try again.');
	return;
}

$steamUserID = $steamprofile['steamid'];

# Check if they are on the blacklist for the chat
$stmt = $db->query('SELECT * FROM chatBlacklist');
$blacklist = $stmt->fetchAll();

foreach ($blacklist as $user) {
	$steamId64 = $user['steamId64'];

	if ($steamId64 === $steamUserID) {
		echo jsonSuccess(array('message' => 'You have been banned from the chat.'));
		return;
	}
}

$stmt = $db->prepare('INSERT INTO `chat` (`steamUserID`, `text`, `date`, `time`) VALUES (:userid, :text, CURDATE(), CURTIME())');
$stmt->bindValue(':userid', $steamUserID);
$stmt->bindValue(':text', $text);
$stmt->execute();

echo jsonSuccess(array('message' => 'Message has been sent!'));
?>
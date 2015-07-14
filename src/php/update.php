<?php
session_start();
include 'default.php';
$db = getDB();

$clientMostRecentMessageID = intval($_GET['mostRecentMessageID']);

# Get all chat messages
$chatMessagesArr = generateChatArr();
$mostRecentChatID = intval($chatMessagesArr{count($chatMessagesArr) - 1}['id']);

while ($clientMostRecentMessageID >= $mostRecentChatID) {
	usleep(1000);
	clearstatcache();
	$chatMessagesArr = generateChatArr();
	$mostRecentChatID = intval($chatMessagesArr{count($chatMessagesArr) - 1}['id']);
}


$data = array('chat' => $chatMessagesArr);
echo jsonSuccess($data);
?>
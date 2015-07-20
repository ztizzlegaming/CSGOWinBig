<?php
session_start();
include 'default.php';
$db = getDB();

# Get all chat messages
$chatMessagesArr = generateChatArr();

$data = array('chat' => $chatMessagesArr);
echo jsonSuccess($data);
?>
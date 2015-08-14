<?php
include 'default.php';
$db = getDB();

# Get bot inventory
$botInventory = json_decode(file_get_contents('https://steamcommunity.com/profiles/76561198238743988/inventory/json/730/2'), true);
$rgInventory = $botInventory['rgInventory'];

# Get current pot
$stmt = $db->query('SELECT * FROM currentPot');
$currentPot = $stmt->fetchAll();

echo jsonSuccess(array('rgInventory' => $rgInventory, 'currentPot' => $currentPot));
?>
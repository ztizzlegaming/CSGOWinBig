<?php
# Copyright (c) 2015 Jordan Turley, CSGO Win Big. All Rights Reserved.

include 'default.php';
$db = getDB();

$stmt = $db->query('SELECT * FROM history ORDER BY id DESC');

if ($stmt->rowCount() === 0) {
	# It is the first ever pot, don't do anything
	echo jsonErr('Don\'t do anything, the current pot is the first one');
	return;
}

$mostRecentPot = $stmt->fetch();

echo jsonSuccess($mostRecentPot);
?>
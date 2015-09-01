<?php
include 'default.php';
$db = getDB();

$stmt = $db->query('SELECT * FROM donations ORDER BY price DESC');
$donations = $stmt->fetchAll();

$data = array('donations' => $donations);
echo jsonSuccess($data);
?>
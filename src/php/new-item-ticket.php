<?php
include 'default.php';

$name = postVar('name');
$price = postVar('price');
$link = postVar('link');

if (is_null($name) || is_null($price) || is_null($link)) {
	echo jsonErr('One of the required fields was not send correctly.');
	return;
}

$to = 'items@csgowinbig.com';
$subject = 'New Item Ticket';
$message = "A new item ticket has been submitted.\nName: $name\nPrice: $price\nLink: $link\n";

mail($to, $subject, $message);

echo jsonSuccess(array('message' => 'Your ticket has successfully been submitted. Thank you!'));
?>
<?php
include 'default.php';

$name = postVar('name');
$price = postVar('price');

if (is_null($name) || is_null($price)) {
	echo jsonErr('One of the required fields was not sent successfully.');
	return;
}

$to = 'items@csgowinbig.com';
$subject = 'Item Price Change Ticket';
$message = "An item price change ticket has been submitted.\nName: $name\nPrice: $price";
mail($to, $subject, $message);

echo jsonSuccess(array('message' => 'Your ticket has successfully been submitted. Thank you!'));
?>
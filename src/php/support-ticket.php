<?php
include 'default.php';
$db = getDB();

$name = postVar('name');
$email = postVar('email');
$steamProfileLink = postVar('steamProfileLink');
$desc = postVar('desc');

if (is_null($name) || is_null($email) || is_null($steamProfileLink) || is_null($desc)) {
	echo jsonErr('One of the required fields was left blank or not sent correctly.');
	return;
}

# Check steam profile link to make sure it is valid
if (!filter_var($steamProfileLink, FILTER_VALIDATE_URL)) {
	echo jsonErr('Your steam profile link was not a valid url.');
	return;
}

# Add to support database table
$stmt = $db->prepare('INSERT INTO support (name, email, steamProfileLink, desc, date, time) VALUES (:name, :email, :steamProfileLink, :desc, CURDATE(), CURTIME())');
$stmt->bindValue(':name', $name);
$stmt->bindValue(':email', $email);
$stmt->bindValue(':steamProfileLink', $steamProfileLink);
$stmt->bindValue(':desc', $desc);
$stmt->execute();

# Send email to our email
$to = 'support@csgowinbig.com';
$subject = 'Support Ticket Submitted';
$message = "A support ticket has been sent.\n\nName: $name\nEmail: $email\nProfile link: $steamProfileLink\nDescription: $desc";
mail($to, $subject, $message);

# Send email to user confirming their support ticket
$subject = 'Support ticket received';
$message = 
	"Hi, we have received your support ticket, with the following information:
	<br><br>
	Your name: $name
	<br>
	Your email: $email
	<br>
	Your Steam profile link: <a href=\"$steamProfileLink\">$steamProfileLink</a>
	<br>
	Your message: $desc
	<br><br>
	You can expect an email response about this issue within the next 24 to 48 hours.";
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
$headers .= "From: CSGO Win Big <jordantu@box1220.bluehost.com>";
mail($email, $subject, $message, $headers);

echo jsonSuccess(array('message' => 'Your support ticket was submitted successfully! Check your email for a confirmation.'));
?>
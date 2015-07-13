<?php
require ('steamauth/openid.php');

function jsonSuccess($data) {
	return json_encode(array('success' => 1, 'data' => $data));
}

function jsonErr($errMsg) {
	return json_encode(array('success' => 0, 'errMsg' => $errMsg));
}
?>
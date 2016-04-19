<?php

function sec_getNewSalt() {
	$salt = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
	return $salt;
}

function sec_getHashedValue($password, $salt) {
	$saltedPW = $password . $salt;
	$hashedPW = hash('whirlpool', $saltedPW);
	return $hashedPW;
}

?>
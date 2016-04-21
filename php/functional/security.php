<?php

#This file contains functionality important for password encryption.

#sec_getNewSalt()
#Returns a new salt for password encryption.
function sec_getNewSalt() {
	$salt = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
	return $salt;
}

#sec_getHashedvalue($password, $salt)
#Takes the two values to combine and returns the properly hashed password to save in MySQL or use for validation.
#@param $password (String) The string that represents the plaintext password.
#@param $salt (String) The generated salt for the current user.
#@return The salted, hashed password.
function sec_getHashedValue($password, $salt) {
	$saltedPW = $password . $salt;
	$hashedPW = hash('whirlpool', $saltedPW);
	return $hashedPW;
}

?>
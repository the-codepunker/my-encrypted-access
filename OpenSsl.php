<?php

	class OpenSsl
	{
		static function encrypt($plain, $key) {
			//make the key exactly 32 alpha numeric chars this should be fine for "easy to remember keys"... not cryptographically secure though
			$key = md5($key);

			$ivlen = openssl_cipher_iv_length($cipher="AES-256-CBC");
			$iv = openssl_random_pseudo_bytes($ivlen);
			$ciphertext_raw = openssl_encrypt($plain, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
			$hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
			$ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );

			return $ciphertext; // includes iv + hmac + encrypted raw text all concatenated and base64 encoded
		}

		static function decrypt($key, $ciphertext) {
			$c = base64_decode($ciphertext); //decode first
			$key = md5($key);

			$ivlen = openssl_cipher_iv_length($cipher="AES-256-CBC");

			$iv = substr($c, 0, $ivlen); // extract the iv from 0 to it's length
			$hmac = substr($c, $ivlen, $sha2len=32); //extract the hashhmac... key signed hash
			$ciphertext_raw = substr($c, $ivlen+$sha2len); //extract the encrypted text

			$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv); // decrypt

			//verify the caculated hashhmac matches the one from the encrypted data
			$calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
			if (hash_equals($hmac, $calcmac)) { //timing attack safe comparison
			    return $original_plaintext;
			} else {
				return 'Unable to decrypt' . PHP_EOL;
			}

		}
	}

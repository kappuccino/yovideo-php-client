<?php

namespace YoVideo;

class Crypto{

	private $iv;
	private $key;
	private $cstrong;

	public function __construct($key=NULL){
	#	$this->iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB), MCRYPT_DEV_URANDOM);
		if(!empty($key)) $this->key = $key;
		$this->iv = openssl_random_pseudo_bytes(16, $this->cstrong);
	}

	public function encrypt($text){
	#	return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->key, $text, MCRYPT_MODE_CFB, $this->iv)));
		return openssl_encrypt($text, 'AES-128-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv);
	}

	public function decrypt($text){
	#	return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, base64_decode($text), MCRYPT_MODE_CFB, $this->iv);
		return openssl_decrypt($text, 'AES-128-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv);
	}

	public function compare($clear, $encoded){
		return ($encoded == $this->encrypt($clear, $this->iv));
	}

	//--

	public function iv($iv=NULL){
		if(func_num_args() == 0) return $this->iv;
		$this->iv = $iv;
		return $this;
	}

	public function key($key=NULL){
		if(func_num_args() == 0) return $this->key;
		$this->key = $key;
		return $this;
	}

}
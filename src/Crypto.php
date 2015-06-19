<?php

namespace YoVideo;

class Crypto{

	private $iv;
	private $key;

	public function __construct(){
		$this->iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB), MCRYPT_DEV_URANDOM);
	}

	public function encrypt($text){
		return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->key, $text, MCRYPT_MODE_CFB, $this->iv)));
	}

	public function decrypt($text){
		return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, base64_decode($text), MCRYPT_MODE_CFB, $this->iv);
	}

	public function compare($clear, $encoded){
		return ($encoded == $this->encrypt($clear, $this->iv));
	}

	//--

	public function iv($iv){
		if(func_num_args() == 0) return base64_encode($this->iv);
		$this->iv = base64_decode($iv);
		return $this;
	}

	public function key($key){
		if(func_num_args() == 0) return $this->key();
		$this->key = $key;
		return $this;
	}

}
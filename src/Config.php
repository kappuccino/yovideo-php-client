<?php

namespace YoVideo;

class Config{

	private $config;
	private static $_instance = null;

	public function __construct(){
	}

	public static function getInstance() {

		if(is_null(self::$_instance)){
			self::$_instance = new Config();
		}

		return self::$_instance;
	}

	public static function get(){
		return Config::getInstance()->config;
	}

	public static function loadFromFile($file){
		if(!file_exists($file)){
			throw new Exception('Config file not found');
		}

		$config = require $file;

		$instance = Config::getInstance();
		$instance->config = $config['YoVideo'];
	}

}
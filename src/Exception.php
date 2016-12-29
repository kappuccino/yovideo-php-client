<?php

namespace YoVideo;

class Exception extends \Exception{

	private $name;
	private $path;
	private $data;
	protected $code;

	// Redéfinissez l'exception ainsi le message n'est pas facultatif
	public function __construct($data, $code = 0, \Exception $previous = null) {

		$this->data = $data;

		if($data['name']) $this->name = $data['name'];

		$message = $data;
		if(is_array($data)) $message = $data['error'];

		// assurez-vous que tout a été assigné proprement
		parent::__construct($message, $code, $previous);

		if(is_array($data)){
			$this->name = $data['name'];
			$this->path = $data['path'];
		}

		$this->code = $code;
	}

	// chaîne personnalisée représentant l'objet
	public function __toString() {
		$v = __CLASS__ . ": [{$this->code}]: {$this->message}\n";

		if($this->data['remote']){
			$v .= $json_string = json_encode($this->data['remote'], JSON_PRETTY_PRINT);
		}

		return $v;
	}

	public function getName(){
		return $this->name;
	}

	public function customMessage(){

		#echo '<pre>';
		#var_dump($this->name);
		#var_dump($this->path);
		#print_r($this);
		#echo '</pre>';

		return $this->getMessage();
	}
}
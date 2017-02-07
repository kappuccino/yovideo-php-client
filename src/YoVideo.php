<?php

namespace YoVideo;

class YoVideo{

	protected $request;

	public function __construct($data = array()){
		$this->request = new Request();
		return $this;
	}

	static function getPeople(){
		return ['actor', 'director', 'scriptwriter', 'composer', 'music', 'producer', 'scripthelper',
				'featuring', 'photography', 'author'];
	}

	/**
	 * Transforme une liste de stars (actor, director...) en une liste d'Oobjet \YoVideo\Star
	 *
	 */
	public function starMapping(){
		foreach(self::getPeople() as $field){

			$stars = $this->get($field);
			if(!empty($stars)){
				$list = [];
				foreach($stars as $e){
					$list[] = new Star($e);
				}
				$this->set($field, $list, false);
			}

		}
	}

	public static function domain(){

		$scheme = 'http';

		if(isset($_SERVER['REQUEST_SCHEME'])){
			$scheme = $_SERVER['REQUEST_SCHEME'];
		}else
		if($_SERVER['HTTPS'] === 'on'){
			$scheme = 'https';
		}

		return $scheme.'://'.$_SERVER['HTTP_HOST'];

	}
}
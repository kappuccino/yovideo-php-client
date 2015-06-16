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
}
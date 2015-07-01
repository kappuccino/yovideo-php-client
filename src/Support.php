<?php

namespace YoVideo;

class Support extends Model{

	public function  __construct($data = array()){
		if(!empty($data)) $this->set($data);
		parent::__construct();
	}

	public function stats(){

		$url  = '/support/stats';

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		$this->set($data);

		return $this;
	}


}
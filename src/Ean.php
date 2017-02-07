<?php

namespace YoVideo;

class Ean extends Model{

	public function  __construct($data = array()){
		if(!empty($data)) $this->set($data);
		parent::__construct();
	}

	public function search(Array $post=[]){

		$url  = '/ean';
		$data = [];

		try{
			$result = $this->request->post($url, $post);
		} catch(Exception $e){
			throw $e;
		}

		$data = $result['data'];
		$this->setTotal($result['total']);

		foreach($data as $n => $e){
			$data[$n] = new Ean($e);
		}

		$this->set($data);

		return $this;
	}

	public function getByEan($ean){

		$url  = '/ean/'.$ean;

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		$data['film_'] = new Film($data['film_']);
		$data['film_']->starMapping();

		$this->set($data);

		return $this;
	}



	public function permalink($full=false){
		$url = '/fr/ean13/'.$this->get('ean').'/';
		if($full) $url = self::domain().$url;
		return $url;
	}
}
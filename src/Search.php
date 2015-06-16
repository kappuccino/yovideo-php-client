<?php

namespace YoVideo;

class Search extends Model{

	private $support;

	public function  __construct(){
		parent::__construct();
		return $this;
	}

	public function film(Array $post){

		$url = '/search/film';

		try{
			$result = $this->request->post($url, $post);
		} catch(Exception $e){
			throw $e;
		}

		if(!empty($result['support'])) $this->support($result['support']);

		$data = $result['data'];
		$this->setTotal($result['total']);

		if(!empty($data)){
			foreach($data as $n => $e){
				$data[$n] = new Film($e);
			}
		}

		$this->set($data);

		return $this;
	}

	public function star(Array $post){

		$url = '/search/star';

		try{
			$result = $this->request->post($url, $post);
		} catch(Exception $e){
			throw $e;
		}

		$data = $result['data'];
		$this->setTotal($result['total']);

		if(!empty($data)){
			foreach($data as $n => $e){
				$data[$n] = new Star($e);
			}
		}

		$this->set($data);

		return $this;
	}

	//--

	function matchSupport(){
		return !empty($this->support);
	}

	function support($s=NULL){
		if(empty($s)) return $this->support;
		$this->support = $s;
		return $this;
	}

}
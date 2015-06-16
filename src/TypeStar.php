<?php

namespace YoVideo;

class TypeStar{

	private $data = [];

	public function __construct(){
		$this->setData();
	}

	public function setData(){
		$this->data = [
			['id' => 1, 'code' => 'cinema', 'name' => 'Cinéma'],
			['id' => 2, 'code' => 'misc', 'name' => 'Divers'],
			['id' => 5, 'code' => 'model', 'name' => 'Mannequin'],
			['id' => 3, 'code' => 'music', 'name' => 'Musique'],
			['id' => 7, 'code' => 'playmate', 'name' => 'Playmate'],
			['id' => 4, 'code' => 'porn', 'name' => 'PornoStar'],
			['id' => 6, 'code' => 'sport', 'name' => 'Sport'],
			['id' => 8, 'code' => 'tv', 'name' => 'TV']
		];
	}

	public function all(){
		return $this->data;
	}

	public function getByID($id){
		$data = array_filter($this->data, function($e) use ($id){
			return $id == $e['id'];
		});

		if(empty($data)) return false;

		$data = array_values($data);
		return $data[0];
	}

	public function getByCode($id){
		$data = array_filter($this->data, function($e) use ($id){
			return $id == $e['code'];
		});

		if(empty($data)) return false;

		$data = array_values($data);
		return $data[0];
	}

}
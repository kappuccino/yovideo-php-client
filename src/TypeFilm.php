<?php

namespace YoVideo;

class TypeFilm{

	private $data = [];

	public function  __construct(){
		$this->setData();
	}

	public function setData(){
		$this->data = [
			['code' => 'film', 'name' => 'Film'],
			['code' => 'short', 'name' => 'court métrage'],
			['code' => 'tv', 'name' => 'téléfilm'],
			['code' => 'doc', 'name' => 'documentaire'],
			['code' => 'serie', 'name' => 'Série'],
			['code' => 'package', 'name' => 'Coffret'],
			['code' => 'music', 'name' => 'Music'],
			['code' => 'learn', 'name' => 'Apprentissage'],
			['code' => 'sport', 'name' => 'Sport'],
			['code' => 'show', 'name' => 'Spectacle'],
			['code' => 'adult', 'name' => 'Adulte'],
			['code' => 'video', 'name' => 'Vidéo']
			
		];
	}

	public function all(){
		return $this->data;
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
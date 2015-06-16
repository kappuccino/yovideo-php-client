<?php

namespace YoVideo;

class Relation{

	private $data = [];

	public function  __construct(){
		$this->setData();
	}

	public function setData(){
		$this->data = [
			['id' => 2,  'name' => 'Parent de',                 'rel' => 'PARENT_OF'],
			['id' => 2,  'name' => 'Enfant de',                 'rel' => 'CHILD_OF'],
			['id' => 3,  'name' => 'Frère/Soeur de',            'rel' => 'BROTHER_OF'],
			['id' => 5,  'name' => 'Cousin/Cousine de',         'rel' => 'COUSIN_OF'],
			['id' => 9,  'name' => 'Beau Frere/Belle soeur de', 'rel' => 'BROTHERINLAW_OF'],
			['id' => 7,  'name' => 'Marié(e) à',                'rel' => 'MARRIED_TO'],
			['id' => 8,  'name' => 'Divorcé(e) de',             'rel' => 'DIVORCED_TO'],
			['id' => 10, 'name' => 'Petit(e) ami(e)',           'rel' => 'BOYFRIEND_OF'],
			['id' => 12, 'name' => 'Séparé(e) de',              'rel' => 'SEPARATED_OF']
		];
	}

	public function all(){
		return $this->data;
	}

}
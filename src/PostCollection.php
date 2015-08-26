<?php

namespace YoVideo;

class PostCollection extends Model{

	public function  __construct($data = array()){

		if(!empty($data)) $this->set($data);

		parent::__construct();
	}

	public function search(Array $post){

		$url  = '/post';
		$data = array();

		try{
			$result = $this->request->post($url, $post);
		} catch(Exception $e){
			throw $e;
		}

		$data = $result['data'];
		$this->setTotal(intval($result['total']));

		if(!empty($data)){
			foreach($data as $n => $e){
				$e['_user'] = new User($e['_user']);
				$data[$n] = new Post($e);
			}
		}

		$this->set($data);

		return $this;
	}

	public function getByUser($id=NULL){

		if(empty($id)){
			$user = new User();
			$id = $user->getUserId();
		}

		if(empty($id)){
			throw new Exception('Impossible to get post from user with empty `id`');
		}

		$this->search(['_user' => $id, 'auto' => false]);

		return $this->get();
	}

	public function getByPlace($place){

		if(empty($place)){
			throw new Exception('Impossible to get post from a place with no place');
		}

		$this->search(['place' => $place]);

		return $this->get();
	}

}
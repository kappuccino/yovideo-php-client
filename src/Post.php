<?php

namespace YoVideo;

class Post extends Model{

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
				$tmp = new Post($e);

				$data[$n] = $tmp;
			}
		}

		$this->set($data);

		return $this;
	}

	public function getById($id){

		$url  = '/post/'.$id;

		try{
			$data = $this->request->get($url);
		} catch(ApiException $e){
			throw $e;
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
			throw new Exception('Impossible to get post from a place with no place ID');
		}

		$this->search(['place' => $place]);

		return $this->get();
	}

	public function getReply($id=NULL){

		if(empty($id)) $id = $this->getId();

		if(empty($id)){
			throw new Exception('Impossible to get replies of a post with empty `id`');
		}

		$this->search(['_parent' => $id]);

		return $this->get();
	}

	public function create($data){

		$url  = '/post';

		// Ajouter le user à la volée
		if(!$data['_user']){
			$user = new User();
			$data['_user'] = $user->getUserId();
		}

		// Lever une exception si on n'a pas pu mettre de User dans ce post
		if(!$data['_user']){
			throw new Exception('Try to create a post with empty `_user` key');
		}

		try{
			$data = $this->request->put($url, $data);
		} catch(ApiException $e){
			throw $e;
		}

		$this->set($data);

		return $this;
	}

	public function addReply($data){

		$id = $this->getId();

		$reply = new Post();
		$reply->create(array_merge($data, [
			'_parent' => $id
		]));

		return $this;
	}


// HELPERS /////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function permalink($full=false){
		$url = '/fr/post/'.$this->getId();
		if($full) $url = 'http://'.$_SERVER['HTTP_HOST'].$url;
		return $url;
	}


}
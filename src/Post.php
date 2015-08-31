<?php

namespace YoVideo;

class Post extends PostCollection{

	public function  __construct($data = array()){

		if(!empty($data)) $this->set($data);

		parent::__construct();
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

		if(!empty($data['post'])) $data['post'] = stripslashes($data['post']);

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

		$data['_user'] = new User($data['_user']);

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

	public function getDate($format='%e %B %Y'){
		$date = $this->get('created');
		$date = new \DateTime($date);
		$timestamp = $date->getTimestamp();
		return utf8_encode(strftime($format, $timestamp));
	}

	public function getUser(){
		return $this->get('_user');
	}

	public function htmlDisplay(){

		$post = $this->get('post');

		$hash = $this->get('hashtags');
		if(!empty($hash)){
			foreach($hash as $e){
				$post = str_replace('#'.$e, '<a href="#">#'.$e.'</a>', $post);
			}
		}

		$users = $this->get('users');
		if(!empty($users)){
			foreach($users as $e){
				$post = str_replace('@'.$e, '<a href="#">@'.$e.'</a>', $post);
			}
		}

		return $post;
	}

}
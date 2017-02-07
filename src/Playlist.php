<?php

namespace YoVideo;

class Playlist extends Model{

	public function  __construct($data = array()){

		if(!empty($data)) $this->set($data);

		parent::__construct();
	}

	public function search(Array $post, Array $options = []){

		$url  = '/playlist';

		try{
		#	pre($url, $post, $options);
			$result = $this->request->post($url, $post, $options);
		} catch(Exception $e){
			throw $e;
		}

		$data = $result['data'];
		$this->setTotal($result['total']);

		if(!empty($data)){
			foreach($data as $n => $e){
				$data[$n] = new Playlist($e);
			}

			$this->set($data);
		}

		return $this;
	}

	public function getById($id){

		$url = '/playlist/'.$id;

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		$this->set($data);
		$this->filmMapping();

		return $this;
	}

	public function getByUser($id = 'me', Array $params = []){

		#$options = [];
		$params = array_merge(['_user' => $id, 'auto' => false], $params);

		if(empty($id)){
			throw new Exception('Impossible to get playlist from user with empty `id`');
		}

		if($id == 'me'){
			$user = new User();
			$auth = $user->getAuthId();

			if(!$auth){
				$this->set([]);
				$this->setTotal(0);
				return $this;
			}

			$params['_user'] = $user->getUserId();

			#$options = ['headers' => ['Auth' => $user->getAuthId()]];
		}

		$this->search($params); //, $options);

		return $this;
	}

	public function getFilms(){
		$films = $this->get('films');
		if(!$films || empty($films)) $films = [];
		return $films;
	}

	public function create($data){

		$url  = '/playlist';

		// Ajouter le user à la volée
		if(!$data['_user']){
			$user = new User();
			$data['_user'] = $user->getUserId();
		}

		// Lever une exception si on n'a pas pu mettre de User dans cette playlist
		if(!$data['_user']){
			throw new Exception('Try to create a playlist with empty `_user` key');
		}

		try{
			$data = $this->request->put($url, $data);
		} catch(Exception $e){
			throw $e;
		}

		$this->set($data);

		return $this;
	}

	public function update($data){

		$id = $this->get('_id');

		// Lever une exception si on n'a pas d'ID
		if(empty($id)) throw new Exception('Try to update a playlist with no `_id`');

		$url  = '/playlist/'.$id;

		try{
			$data = $this->request->post($url, $data);
		} catch(Exception $e){
			throw $e;
		}

		$this->set($data);

		return $this;
	}

	public function remove(){

		$id = $this->getId();

		// Lever une exception si l'instance de cet objet n'a pas d'ID
		if(!$id){
			throw new Exception('Try to remove a playlist with empty `_id` key');
		}

		$url  = '/playlist/'.$id;

		try{
			$this->request->delete($url);
		} catch(Exception $e){
			throw $e;
		}

		return $this;

	}

	public function pushFilm($id){

		$pid = $this->getId();

		if(!$pid){
			throw new Exception('Try to push a film to a playlist with empty film `id`');
		}

		$url  = '/playlist/'.$pid.'/push';
		$data = ['film' => $id];

		try{
			$data = $this->request->post($url, $data);
		} catch(Exception $e){
			throw $e;
		}

		$this->set($data);

		return $this;
	}

	public function pullFilm($id){

		$pid = $this->getId();

		if(!$pid){
			throw new Exception('Try to pull a film from a playlist with empty film `id`');
		}

		$url  = '/playlist/'.$pid.'/pull';
		$data = ['film' => $id];

		try{
			$data = $this->request->post($url, $data);
		} catch(Exception $e){
			throw $e;
		}

		$this->set($data);

		return $this;
	}

	private function filmMapping(){

		$films = $this->get('films');

		if(!empty($films)){
			$list = [];
			foreach($films as $n => $e){
				$e['film'] = new Film($e['film']);
				$list[] = $e;
			}

			$this->set('films', $list, false);
		}

	}

// HELPERS /////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function permalink($full=false){
		$url = '/fr/playlist/'.$this->getId().'/';
		if($full) $url = self::domain().$url;
		return $url;
	}

	public function displayName(){
		return $this->get('name');
	}

	public function publicPermalink($full=false){
		$url = '/fr/member/'.$this->get('_user._id').'/playlist/'.$this->getId().'/';
		if($full) $url = self::domain().$url;
		return $url;
	}

}
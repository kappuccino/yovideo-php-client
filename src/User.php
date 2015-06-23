<?php

namespace YoVideo;

class User extends Model{

	private $exists = false;
	private $playlists = [];
	private $user_id;
	private $auth_id;

	public function  __construct($data=NULL){
		if(!empty($data)) $this->set($data);
		parent::__construct();
	}

	public function getBy($field, $value){

		$url  = '/user/by';
		$post = ['field' => $field, 'value' => $value];

		try{
			$data = $this->request->post($url, $post);
		} catch(Exception $e){
			throw $e;
		}

		$this->set($data);

		return $this;
	}

	public function login($login, $passwd, $memo=false){

		$url  = '/user/login';
		$post = ['login' => $login, 'passwd' => $passwd];

		try{
			$data = $this->request->post($url, $post);
		} catch(Exception $e){
			throw $e;
		}

		if($data['user'] && $data['auth']){
			$_SESSION['yo']['user'] = $data['user'];
			$_SESSION['yo']['auth'] = $data['auth'];

			if($memo){
				$config = Config::get();
				$Crypto = new Crypto();
				$Crypto->key($config['salt']);

				$crypted = $Crypto->encrypt($data['auth']['_id']);
				setcookie('yau', $Crypto->iv().'__'.$crypted, time() + (15*86400), '/');
			}
		}else{
			self::logout();
		}

		return $this;
	}

	static function logout(){
		unset($_SESSION['yo']['user'], $_SESSION['yo']['auth']);
		setcookie('yau', '', time() - 300, '/');
	}

	public function create($post){

		$url  = '/user';
		$data = [];

		try{
			$data = $this->request->put($url, $post);
		} catch(Exception $e){
			if($e->getName() == 'Exists'){
				$this->exists = true;
			}else{
				throw $e;
			}
		}

		if(!empty($data)){
			$data['user']['auth'] = $data['auth'];
			$this->set($data['user']);
		}

		return $this;
	}

	public function update(Array $data){

		$id = $this->getId();
		if(empty($id)){
			throw new Exception('Try to update a user with no id');
		}

		$url = '/user/'.$id;

		try{
			$data = $this->request->post($url, $data);
		} catch(Exception $e){
			throw $e;
		}

		if(!empty($data)) $this->set($data);

		return $this;
	}

	public function connectFromToken($token){

		if(!$token){
			throw new Exception('Auth Token is empty');
		}

		$url  = '/auth/'.$token;

		try{
			$data = $this->request->get($url);

		} catch(Exception $e){
			throw $e;
		}

		if($data['user'] && $data['auth']){
			$_SESSION['yo']['user'] = $data['user'];
			$_SESSION['yo']['auth'] = $data['auth'];
		}

		return $this;
	}

	public function apiExists($field, $value){

		$url  = '/user/exists';
		$post = ['field' => $field, 'value' => $value];

		try{
			$data = $this->request->post($url, $post);
		} catch(Exception $e){
			throw $e;
		}

		return $data['exists'] ?: false;
	}

	public function checkEmail($email){

		$config = Config::get();
		$sent = false;
		$link = 'http://'.$_SERVER['HTTP_HOST'].'/fr/user/confirm/'.
				'?email='.rawurlencode($email);

		try {
			$mandrill = new \Mandrill($config['mandrill']['key']);

			$message = array(
				'from_email' => 'no-reply@yo-video.com',
				'from_name'  => 'yovideo',
				'tags'       => array('yovideo', 'check-email'),
				'to'         => array(
					array(
						'email' => $email,
						'type'  => 'to'
					)
				),
				'global_merge_vars' => array(
					array('name' => 'link', 'content' => $link),
				)
			);

			$result = $mandrill->messages->sendTemplate('yovideo-check-email', [], $message, false, '', '');

			if($result[0]['status'] == 'sent') $sent = true;


		} catch(\Mandrill_Error $e) {
			// Mandrill errors are thrown as exceptions
			#	echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();

			// A mandrill error occurred: Mandrill_Unknown_Subaccount - No subaccount exists with the id 'customer-123'
			throw $e;
		}

		return $sent;
	}

	public function changePassword($auth, $previous, $passwd){

		$url  = '/auth/'.$auth.'/passwd';
		$post = ['previous' => $previous, 'passwd' => $passwd];

		try{
			$data = $this->request->post($url, $post);
		} catch(Exception $e){
			throw $e;
		}

		return $data['exists'] ?: false;
	}

	public function lostEmail($email, $token){

		$config = Config::get();
		$sent = false;
		$link = 'http://'.$_SERVER['HTTP_HOST'].'/fr/user/lost/?token='.rawurlencode($token);

		try {
			$mandrill = new \Mandrill($config['mandrill']['key']);

			$message = array(
				'from_email' => 'no-reply@yo-video.com',
				'from_name'  => 'yovideo',
				'tags'       => array('yovideo', 'lost'),
				'to'         => array(
					array(
						'email' => $email,
						'type'  => 'to'
					)
				),
				'global_merge_vars' => array(
					array('name' => 'link', 'content' => $link),
				)
			);

			$result = $mandrill->messages->sendTemplate('yovideo-lost', [], $message, false, '', '');

			if($result[0]['status'] == 'sent') $sent = true;


		} catch(\Mandrill_Error $e) {
			// Mandrill errors are thrown as exceptions
			#	echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();

			// A mandrill error occurred: Mandrill_Unknown_Subaccount - No subaccount exists with the id 'customer-123'
			throw $e;
		}

		return $sent;
	}

	public function setCurrentUser(){
		$id = $this->getAuthId();
		if($id){
			$this->user_id = $id;
			$this->auth_id = $this->getAuthId();
		}
	}

	public function authFromLogin($login){

		$url  = '/auth/login';
		$post = ['login' => $login];

		try{
			$data = $this->request->post($url, $post);
		} catch(Exception $e){
			throw $e;
		}

		$this->set($data);

		return $this;
	}

	public function getByEmail($email){

		$url  = '/user/email';
		$data = ['email' => $email];

		try{
			$data = $this->request->post($url, $data);
		} catch(Exception $e){
			throw $e;
		}

		$this->set($data);

		return $this;
	}


// HELPERS /////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function exists(){
		if($this->exists) return $this->exists;
	}

	static function isLogged(){

		// Session
		if(!empty($_SESSION['yo']['user'])) return true;

		// Cookie
		$cookie = $_COOKIE['yau'];
		if(!empty($cookie)){

			$config = Config::get();
			$Crypto = new Crypto();

			list($iv, $crypted) = explode('__', $cookie);

			$Crypto->key($config['salt']);
			$Crypto->iv($iv);

			$decode = $Crypto->decrypt($crypted);

			$User = new User();
			$User->connectFromToken($decode);

			if($_SESSION['yo']['auth']['_id'] == $decode){
				return true;
			}
		}

		return false;
	}

	public function hasPlaylists(){
		$this->getPlaylists();
		return count($this->playlists) > 0;
	}

	public function getPlaylists(){
		if(empty($this->playlists)){
			$this->playlists = (new Playlist())->getByUser($this->user_id);
		}

		return $this->playlists;
	}

	static function sessionReady(){
		return !empty($_SESSION['yo']['user']) && !empty($_SESSION['yo']['auth']);
	}

	public function getUser(){
		return $_SESSION['yo']['user'];
	}

	public function getUserId(){
		return $_SESSION['yo']['user']['_id'];
	}

	public function getAuth(){
		return $_SESSION['yo']['auth'];
	}

	public function getAuthId(){
		return $_SESSION['yo']['auth']['_id'];
	}

}
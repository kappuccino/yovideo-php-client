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

	public function getById($id){
		return $this->getBy('_id', $id);
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

	public function getMe(){

		$this->setCurrentUser();
		if(empty($this->user_id)) return false;

		return $this->getById($this->user_id);
	}

//--

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
				$cookie = base64_encode($Crypto->iv().'__'.$crypted);

				setcookie('yau', $cookie, time() + (15*86400), '/');
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

	public function checkEmail($email, $name){

		$config = Config::get();
		$sent = false;
		$link = 'http://'.$_SERVER['HTTP_HOST'].'/fr/user/confirm/'.
				'?email='.rawurlencode($email).'&name='.$name;

		try {
			$mandrill = new \Mandrill($config['mandrill']['key']);

			$message = [
				'from_email' => 'no-reply@yo-video.com',
				'from_name' => 'yovideo',
				'tags' => ['yovideo', 'check-email'],
				'to' => [['email' => $email, 'type' => 'to']],
				'global_merge_vars' => [
					array('name' => 'link', 'content' => $link),
					array('name' => 'name', 'content' => $name),
					array('name' => 'email', 'content' => $email)
				]
			];

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
			$this->user_id = $this->getUserId();
			$this->auth_id = $id;
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

	public function setName($name){

		$id = $this->getId();
		if(empty($id)){
			throw new Exception('Try to update a user with no id');
		}

		$url = '/user/'.$id;

		try{
			$data = $this->request->post($url, ['name' => $name]);
		} catch(Exception $e){
			throw $e;
		}

		if(!empty($data)) $this->set($data);

		return $this;
	}

//--

	public function getFeeling($light=false){

		$id = $this->getId();

		if(!$id) throw new Exception('Try to get user feeling with no user id');

		$url = '/user/'.$id.'/feeling';

		if($light) $url .= '?light';

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		$this->set('feeling_', $data, false);

		return $this;
	}

	public function feelingFiltered($feeling){

		$feel = $this->get('feeling_');
		if(empty($feel)) $this->getFeeling();

		// Devrait être peuplé ou vide
		$feel = $this->get('feeling_');
		if(!is_array($feel)) $feel = [];

	    $feel = array_filter($feel, function($e) use ($feeling){
		    if(array_key_exists($feeling, $e)) return $e[$feeling];
		    return false;
		});


		return $feel;
	}

	public function feel($type, $id, $feeling){

		$uid = $this->getUserId();
		if(!$uid) throw new Exception('Try to set a feeling with no user id');

		$url = '/user/'.$uid.'/feel/'.$type.'/'.$id.'/'.$feeling;

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		return $data;
	}

	public function unfeel($type, $id, $feeling){

		$uid = $this->getUserId();
		if(!$uid) throw new Exception('Try to set a feeling with no user id');

		$url = '/user/'.$uid.'/unfeel/'.$type.'/'.$id.'/'.$feeling;

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		return $data;
	}

	public function rate($type, $id, $rate){

		$uid = $this->getUserId();
		if(!$uid) throw new Exception('Try to set a rate with no user id');

		$url = '/user/'.$uid.'/rate/'.$type.'/'.$id.'/'.$rate;

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		return $data;
	}

	public function unrate($type, $id){

		$uid = $this->getUserId();
		if(!$uid) throw new Exception('Try to unset a rate with no user id');

		$url = '/user/'.$uid.'/unrate/'.$type.'/'.$id;

		echo $url;

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		return $data;
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

			$cookie = base64_decode($cookie);
			if($cookie === false) return false; // Base64 failed;

			$config = Config::get();
			$Crypto = new Crypto($config['salt']);

			list($iv, $crypted) = explode('__', $cookie);
			$Crypto->iv($iv);

		#	var_dump($Crypto->key());
		#	var_dump($Crypto->iv());
		#	var_dump($crypted);

			$decode = $Crypto->decrypt($crypted);
		#	var_dump($decode);
		#	die();
			if($decode === false) return false; // Erreur de decryptage

			try{
				$User = new User();
				$User->connectFromToken($decode);
			}catch(Exception $e){
				// silentio
			}

			// Si l'AUTH distante a le même ID que mon Cookie décodé (tout va bien)
			if($_SESSION['yo']['auth']['_id'] == $decode) return true;
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

	public function displayName(){
		return '@'.$this->get('name');
	}

	/**
	 * Retourne l'URL complète pour le film
	 *
	 * @param bool $full
	 * @return string
	 */
	public function permalink($full=false){
		$url = '/fr/member/'.$this->getId().'/';
		if($full) $url = 'http://'.$_SERVER['HTTP_HOST'].$url;
		return $url;
	}

	public function subPermalink($sub, $full=false){
		$url = $this->permalink($full).$sub;
		return $url;
	}

	public function htmlLink($label=NULL, $url=NULL, $opt=[]){

		if(empty($label)) $label = $this->displayName();

		$opt = array_merge($opt, [
			'title' => $this->displayName()
		]);

		return parent::htmlLink($label, $url ?: $this->permalink(true), $opt);
	}

	public function avatarURL($size='small', $fallback=false){

		$media = $this->get('media');
		if(empty($media)) return $fallback;

		$media = array_filter($media, function($e){
			if($e['main']) return $e;
		});

		if(empty($media)) return $fallback;

		// Remet l'Array dans le bon ordre
		$media = array_values($media);

	#	print_r($media);
	#	die();

		$image = new Media($media[0], $size);
		$image->fallback($fallback);

		return $image->url();
	}

	static function isNameValid($name){
		$valid = false;

		# Format
		if(preg_match_all('#[a-zA-Z0-9\-\_]+#', $name)) $valid = true;

		# API (email)
		if($valid){
			$User = new User();
			$valid = $User->apiExists('name', $name) === false;
		}

		return $valid;
	}

	static function isEmailValid($email){
		$valid = false;

		# Format
		$valid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

		# API
		if($valid){
			$User = new User();
			$valid = $User->apiExists('email', $email) === false;
		}

		return $valid;
	}

}
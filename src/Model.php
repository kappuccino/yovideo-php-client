<?php

namespace YoVideo;

class Model extends YoVideo{

	private $model;
	private $id;
	private $data = [];
	private $total = 0;

#	private $ttl;
#	private $useCache = false;
#	private $defaultTTL = 86400; // 1j par défaut

	/**
	 * Allow to affect $value to $this->data with $name as key (model verified)
	 * If you want to use a dot notation as key value, @see set()
	 *
	 * @param $name
	 * @param $value
	 *
	 * @return $this
	 */
	public function __set($name, $value){
		if(array_key_exists($name, $this->model)) return $this->set($name, $value);
		$this->$name = $value;
		return $this;
	}

	/**
	 * Allow to get a value from $this->model (model verified)
	 * If you want to use a dot notation as key value, @see get()
	 *
	 * @param $name
	 *
	 * @return array
	 */
	public function __get($name){
		if(array_key_exists($name, $this->model)) return $this->get($name);
		return $this->$name;
	}

	public function setModel($model){
		$this->model = $model;
		return $this;
	}

	public function getModel(){
		return $this->model;
	}

	public function sanitize($key=NULL, $val=NULL){

		// Sanitize toute les données
		if(NULL === $key && NULL === $value){
			foreach($this->get() as $key => $val){
				$sanitized = false;

				try{
					$sanitized = $this->sanitize($key, $val);
				#	Tools::pre("sanitizing", $key, $val, $sanitized);
				} catch (Exception $e){
					if($e->getCode() == 1000) $this->unsetFromPath($key);
				}

				if($sanitized){
					$this->set($key, $sanitized, false);
				}
			}

		#	Tools::pre(get_class($this), '====>', $this->get());
			return $this;
		}

		// Sanitize this KEY
		if(!empty($key) /*&& !empty($val)*/){

			// Key exists ?
			$keys = array_keys(Tools::arrayFlatten($this->model));

		#	var_dump('keyExists: '.$key);
			if($this->keyExists($key)){

				$model = $this->model[$key];
				$type  = $model;

				if(is_array($model) && !empty($model['type'])) $type = $model['type'];

				// Sub schema
				if(is_array($val) && is_array($type) && !empty($type[0]['type'])){
				#	echo '!';
				#	var_dump($type[0]['type']);
					$constructor = $type[0]['type'];
					$safe = [];

					// Chaque entré doit être validé par le model
					foreach($val as $e){
						$tmp = new $constructor($e);
						$tmp->sanitize();
						$safe[] = $tmp->get();
					}

					return $safe;
				}

				// Standard types
				if($type == 'String'){
					return trim((String) $val);
				}

				if($type  == 'Integer'){
					return intval($val);
				}

				return '???';
			#	throw new Exception('Try to sanitize a key/value not found in model: '.$key, 1000);

			}else{
				throw new Exception('Try to sanitize a key/value not found in model: '.$key, 1000);
			}
		}

		return $this;
	}

	private function keyExists($check, $model=NULL){

		if(NULL == $model) $flat = Tools::arrayFlatten($this->model);
		$arrays = [];

		foreach($flat as $key => $type){
			if($key == $check) return true;

			if(strpos($key, '.0') !== false){
				list($start, $end) = explode('.0', $key);
				if($start == $check) return true;
			}
		}

	#	echo $check.' not found in '; var_dump($flat);
		return false;
	}

	/**
	 * Merge $data and $this->data together
	 *
	 * @param $data
	 *
	 * @return $this
	 */
	public function merge($data){
		$this->data = array_merge($this->data, $data);
		if(!empty($this->data['_id'])) $this->setId($this->data['_id']);
		return $this;
	}

	/**
	 * Set data. If $value is not null, $data is handled as dot notation path key
	 *
	 * @param           $data Array of KEY/VAL or KEY (used $value)
	 * @param null      $value
	 * @param boolean   $sanitize perform a sanitize check with those KEY/VAL
	 *
	 * @return $this
	 */
	public function set($data, $value=NULL, $sanitize=true){

		// data = val
		if(func_num_args() > 1){
		#	pre(func_num_args(), var_export($data, true), var_export($value, true));
			$tmp = $this->get();
		#	Tools::pre('****************************', $tmp, $this->get(), '****************************');

			if($sanitize){
				try{
					$sanitized = $this->sanitize($data, $value);
				} catch (Exception $e){
					if($e->getCode() == 1000) $this->unsetFromPath($data);
				}

				if($sanitized) $tmp[$data] = $sanitized;
			}else{
				$tmp[$data] = $value;
			}

			#Tools::arraySet($this->data, $data, $value);
			$this->data = $tmp;

		#	Tools::pre('##############', $tmp, $this->get(), '##############');
		}

		// data = [key: val]
		else{
			$this->data = $data;
		}

		#pre($this->data);
		if(!empty($this->data['_id'])) $this->setId($this->data['_id']);

		return $this;
	}

	/**
	 * Return this->data or a specific value from key (dot notation)
	 *
	 * @param null $path
	 *
	 * @return array|null|string
	 */
	public function get($path=NULL){
		if(!empty($path)) return Tools::arrayGet($this->data, $path);
		return $this->data;
	}

	public function unsetFromPath($path){
		#var_dump($this->data);
		unset($this->data[$path]);
		#var_dump($this->data);
		return $this;
	}

	/**
	 * Return a flatten view of this->data
	 *
	 * @return array
	 */
	public function getFlat(){
		return Tools::arrayFlatten($this->get());
	}

	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
		return $this;
	}

	public function setTotal($total){
		$this->total = intval($total);
		return $this;
	}

	public function getTotal(){
		return $this->total;
	}

	public function htmlLink($label, $url, $opt=[]){

		$html = [];
		$tags = ['href' => $url];

		foreach(['target', 'title', 'alt', 'class', 'id'] as $t){
			if($opt[$t]) $tags[$t] = $opt[$t];
		}

		foreach($tags as $tag => $val){
			$html[] = $tag.'="'.addslashes($val).'"';
		}

		$start = ' '.implode(' ', $html);
		$html = '<a'.$start.'>'.$label.'</a>';

		if($opt['return']) return $html;
		if($opt['props']) return $start;

		echo $html;
	}

	public function useCache($use=NULL, $ttl=NULL){
		if(func_num_args() == 0) return $this->request->useCache();
		$this->request->useCache((bool) $use);
		if($ttl) $this->cacheTTL($ttl);
		return $this;
	}

	public function cacheTTL($ttl=NULL){
		if(func_num_args()) return $this->request->cacheTTL();
		$this->request->cacheTTL($ttl);
		return $this;
	}

	public function requestDebug($debug=NULL){
		return $this->request->debug($debug);
	}

	public function statsCount($stat){
		$stats = $this->get('stats');
		if(empty($stats)) return 0;
		if(!array_key_exists($stat, $stats)) return 0;
		return $stats[$stat];
	}}
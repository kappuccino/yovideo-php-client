<?php

namespace YoVideo;

use GuzzleHttp\Client;
use YoVideo\Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class Request{

	private $rest;
	private $serverUrl;
	private $version;
	private $auth;
	private $ttl;
	private $useCache = false;
	private $defaultTTL = 86400; // 1j par défaut

	public function __construct($data = array()){

		$config = Config::get();

		$this->serverUrl = 'http://'.$config['host'];
		if(!empty($config['port'])) $this->serverUrl .= ':'.$config['port'];

		$this->auth    = $config['auth'];
		$this->version = 'v'.$config['version'];
		$this->rest    = $this->rest();

		return $this;
	}

	/**
	 * Recupère une instance du client REST pour intérogger l'API
	 *
	 * @return Client
	 */
	private function rest(){
		$client = new Client([
			'base_url' => $this->serverUrl,
			'defaults' => [
				'timeout' => 60,
				'headers' => [
					'Accept' => 'application/json',
					'Auth'   => $this->auth
				]
			]
		]);

		return $client;
	}

	/**
	 * Envois une requete via le Client REST, retourne un ARRAY si le résultat est du JSON, si non, RAW
	 *
	 * @param string $verb (get, post, put, delete)
	 * @param string $url
	 * @param array  $opt (request options);
	 * @return array|\GuzzleHttp\Stream\StreamInterface|null|string
	 * @throws Exception
	 * @throws \Exception
	 */
	private function request($verb, $url, $opt=array()){

	#	Tools::pre(Tools::memoryUsage());

		$now = microtime(true);
		$url = '/'.$this->version.$url;
		$options = ['exceptions' => false];
		$options = array_merge($options, $opt);
		$out = false;
		$cacheKey = $this->cacheMakeKey($url, $options);

	#	Tools::pre("REQUEST", $verb, $url, $opt, var_export($this->useCache()));

		// Indiquer à l'API qu'on souhaite que le résultat soit mit en cache
		/*if($this->useCache()){
			if(!is_array($options['headers'])) $options['headers'] = [];
			$options['headers']['X-Cache'] = 'YES';
		}*/

	#	Tools::pre($url, $options);

		// Si l'on veut de la cache, on la demande a WP (redis)
	#	Tools::pre('useCache()', var_export($this->useCache()));
		if($this->useCache()){
		//	$found = false;
	#		Tools::pre("CACHE ", $url, $options, $cacheKey);
			$cached = wp_cache_get($cacheKey, 'yoapi');
			if($cached !== false) $out = $this->cacheUnserialize($cached);
		}

		if(empty($out)){

	#		Tools::pre("NOT IN CACHE", $cacheKey);

			// Si je dois faire le travail
			try {
				$data = $this->rest->$verb($url, $options);

			} catch (\Exception $e) {
				throw $e;
			}

			$code = $data->getStatusCode();
			$out  = $data->getBody();

			// JSON ?
			if (strpos($data->getHeader('content-type'), 'application/json') !== false){
				$out = $data->json();
			}

			if ($code > 200) {

				if (is_array($out) && NULL !== $out['error']['name']) {
					throw new Exception($out['error'], $code);
				}

				throw new Exception('Api Exception', $code);
			}

			// Mettre en cache si tout va bien
	#		var_dump($code);
	#		var_dump($this->useCache());

			if($code == 200 && $this->useCache()){
				$cached = $this->cacheSerialize($out);

				/*Tools::pre('cache', $cacheKey, strlen($cached), 'ttl='.$this->cacheTTL(), 'date='.date("Y-m-d H:i:s", time()+$this->cacheTTL()),
					'now='.date("Y-m-d H:i:s")
				);*/

				wp_cache_set($cacheKey, $cached, 'yoapi', $this->cacheTTL());

	#			Tools::pre("CACHE SET", $cacheKey, microtime(true) - $now);
			}

	#		Tools::pre("CACHE SET", $cacheKey, microtime(true) - $now);
		}else{
	#		Tools::pre("CACHE HITED", $cacheKey, microtime(true)-$now);
		}

	#	Tools::pre(Tools::memoryUsage());

		return $out;
	}

	/**
	 * GET
	 *
	 * @param $url
	 * @param $params
	 *
	 * @return array|\GuzzleHttp\Stream\StreamInterface|null|string
	 */
	public function get($url, $params=NULL, $options=array()){
		if($params) $url .= '?'.http_build_query($params);
		return $this->request('get', $url, $options);
	}

	/**
	 * POST
	 *
	 * @param $url
	 * @param $data
	 * @param $options
	 *
	 * @return array|\GuzzleHttp\Stream\StreamInterface|null|string
	 */
	public function post($url, Array $data = [], Array $options = []){
		$opt = ['body' => $data];
		if(!empty($options)) $opt = $opt + $options;

	#	pre($url, $opt);
	#	die();

		return $this->request('post', $url, $opt);
	}

	/**
	 * PUT
	 *
	 * @param $url
	 * @param $data
	 *
	 * @return array|\GuzzleHttp\Stream\StreamInterface|null|string
	 */
	public function put($url, Array $data){
		return $this->request('put', $url, ['body' => $data]);
	}

	/**
	 * DELETE
	 *
	 * @param $url
	 * @param $data array of POST value
	 *
	 * @return array|\GuzzleHttp\Stream\StreamInterface|null|string
	 */
	public function delete($url, $data=NULL){
		$opt = [];
		if(!empty($data)) $opt = ['body' => $data];

		return $this->request('delete', $url, $opt);
	}

//--

	private function cacheSerialize($in){
		return json_encode($in);
	}

	private function cacheUnserialize($in){
		return json_decode($in, true);
	}

	private function cacheMakeKey($url, $options){
		$url = substr($url, 1);
		return str_replace('/', ':', $url).':'.crc32(json_encode($options));
	#	return md5($url).'_'.crc32(json_encode($options));
	}

	public function useCache($use=NULL, $ttl=false){
		if(func_num_args() == 0) return $this->useCache;
		$this->useCache = (bool) $use;

		if($ttl) $this->cacheTTL($ttl);

	#	Tools::pre('set cache to ', var_export($this->useCache, true));
		return $this;
	}

	public function cacheTTL($ttl=NULL){
		if(empty($ttl)) return $this->ttl ?: $this->defaultTTL;
		$this->ttl = $ttl;
		return $this;
	}

}
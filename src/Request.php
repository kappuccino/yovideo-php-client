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
	private $debug;
	private $cachable = false;
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

		$now = microtime(true);
		$url = '/'.$this->version.$url;
		$options = ['exceptions' => false];
		$options = array_merge($options, $opt);
		$out = false;
		$cacheKey = $this->cacheMakeKey($url, $options);

		if($this->useCache()) $this->isCachable(true);

		if($this->debug()){
			Tools::pre(
				'>> '.strtoupper($verb).' '.$url, $opt,
				'isCachable() = '.var_export($this->isCachable(), true),
				'useCache() = '.var_export($this->useCache(), true),
				'cacheKey = '.$cacheKey
			);
		}

		if($this->debug()) Tools::pre('Memory (before) = '.Tools::memoryUsage());

		// Si l'on veut de la cache, on la demande a WP (redis)
		if($this->useCache()){
			$cached = wp_cache_get($cacheKey, 'yoapi');
			if($cached !== false) $out = $this->cacheUnserialize($cached);
		}

		if(empty($out)){

			if($this->debug()) Tools::pre('NOT IN CACHE, the request will be triggered');

			// Si je dois faire le travail
			try{
				$data = $this->rest->$verb($url, $options);
			} catch (\Exception $e){
				throw $e;
			}

			$code = $data->getStatusCode();
			$out  = $data->getBody();

			// JSON ?
			if(strpos($data->getHeader('content-type'), 'application/json') !== false){
				$out = $data->json();
			}

			if($code > 200){
				if(is_array($out) && NULL !== $out['error']['name']){
					throw new Exception($out['error'], $code);
				}

				throw new Exception('Api Exception', $code);
			}

			if($code == 200){
				$cached = $this->cacheSerialize($out);

				if($this->debug()){
					Tools::pre(
						'now= '.date("Y-m-d H:i:s"),
						'ttl= '.$this->cacheTTL(),
						'date= '.date("Y-m-d H:i:s", time()+$this->cacheTTL())
					);
				}

				if($this->isCachable()){
					wp_cache_set($cacheKey, $cached, 'yoapi', $this->cacheTTL());
					if($this->debug()) Tools::pre('CACHED in '.(microtime(true) - $now).' ms');
				}else{
					if($this->debug()) Tools::pre('NOT CACHED because isCachable() is false');
				}
			}

		}else{
			if($this->debug()) Tools::pre('CACHE HITED in '.(microtime(true)-$now).' ms');
		}

		if($this->debug()){
			Tools::pre('Memory (after) = '.Tools::memoryUsage());
			Tools::pre('Timing is '.(microtime(true)-$now));
		}

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

	public function isCachable($cachable=false){
		if(func_num_args() == 0) return $this->cachable;
		$this->cachable = (bool) $cachable;
		return $this;
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


//--

	public function debug($debug=NULL){
		if(func_num_args() == 0) return $this->debug;
		#Tools::pre(__FILE__.':'.__LINE__, 'debug= '.var_export($debug, true));

		$this->debug = (bool) $debug;
		return $this;
	}
}
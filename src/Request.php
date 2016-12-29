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
			'base_uri' => $this->serverUrl,
			'timeout' => 60,
			'headers' => [
				'Accept' => 'application/json',
				'Auth'   => $this->auth
			]
		]);

		return $client;
	}

	/**
	 * Envois une requete via le Client REST, retourne un ARRAY si le résultat est du JSON, si non, RAW
	 *
	 * @param string $verb (get, post, put, delete)
	 * @param string $url
	 * @param array  $params
	 * @param array  $options
	 * @return array|\GuzzleHttp\Stream\StreamInterface|null|string
	 * @throws \Exception
	 * @throws \YoVideo\Exception
	 * @internal param array $opt (request options);
	 */
	private function request($verb, $url, Array $params=[], Array $options=[]){

		$now = microtime(true);
		$url = '/'.$this->version.$url;
		$options = $options + ['exceptions' => false];
		if(!empty($params)) $options = $options + ['form_params' => $params];

		if(!empty($options['headers'])  &&  $options['headers']['Content-Type'] == 'application/json'){
			$options['json'] = $options['form_params'];
			unset($options['form_params']);

			#	print_r($options);
			#	die();
		}



		$out = false;
		$cacheKey = $this->cacheMakeKey($url, $options);

		if($this->useCache()) $this->isCachable(true);

		if($this->debug()){
			Tools::pre(
				'>> '.strtoupper($verb).' '.$url, $params, $options,
				'isCachable() = '.var_export($this->isCachable(), true),
				'useCache() = '.var_export($this->useCache(), true),
				'cacheKey = '.$cacheKey
			);
			Tools::pre('Memory (before) = '.Tools::memoryUsage());
		}

		// Si l'on veut de la cache, on la demande a WP (redis)
		if($this->useCache()){
			$cached = wp_cache_get($cacheKey, 'yoapi');
			if($cached !== false) $out = $this->cacheUnserialize($cached);
		}

		if(empty($out)){

			if($this->debug()) Tools::pre('NOT IN CACHE, the request will be triggered');

			// Si je dois faire le travail
			try{
			#	pre($verb, $url, $options);
				$data = $this->rest->request($verb, $url, $options);
			} catch (\Exception $e){
				throw $e;
			}

			// http://docs.guzzlephp.org/en/latest/quickstart.html#making-a-request
			$code = $data->getStatusCode();
			$body = $data->getBody();
			$out  = $body->getContents();

			// JSON ?
			if(strpos($data->getHeader('content-type'), 'application/json') !== false){
				try{
					$out = json_decode($out, true);
				} catch (\Exception $e){
				}
			}

			if($code > 200){
				if(is_array($out) && NULL !== $out['error']['name']){
					throw new Exception($out['error'], $code);
				}else
				if(is_array($out)){
					throw new Exception(['name' => 'Error', 'remote' => $out]);
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
	 * Envois une requete via le Client REST, retourne un ARRAY si le résultat est du JSON, si non, RAW
	 *
	 * @param string $verb (get, post, put, delete)
	 * @param string $url
	 * @param array  $opt (request options);
	 * @return array|\GuzzleHttp\Stream\StreamInterface|null|string
	 * @throws Exception
	 * @throws \Exception
	 */
	private function request_____($verb, $url, Array $params=[], Array $options=[]){

		$now = microtime(true);
		$options = $options + ['exceptions' => false];
		if(!empty($params)) $options = $options + ['form_params' => $params];

		if(!empty($options['headers'])  &&  $options['headers']['Content-Type'] == 'application/json'){
			$options['json'] = $options['form_params'];
			unset($options['form_params']);

			#	print_r($options);
			#	die();
		}



		$out = false;
		$cacheKey = $this->cacheMakeKey($url, $params);

		if($this->debug()){
			Tools::pre(
					'>> '.strtoupper($verb).' '.$this->serverUrl.$url,
					'Request parameter', $options,
					'useCache() = '.var_export($this->useCache(), true),
					'cacheKey = '.$cacheKey
			);
			Tools::pre('Memory (before) = '.Tools::memoryUsage());
		}

		// Si l'on veut de la cache, on la demande a WP (redis)
		if($this->useCache()){
			$cached = wp_cache_get($cacheKey, 'yoapi');
			if($cached !== false) $out = $this->cacheUnserialize($cached);
		}

		if(empty($out)){

			if($this->debug()) Tools::pre('NOT IN CACHE, the request will be triggered');

			// Si je dois faire le travail
			try {
				$data = $this->rest->request($verb, $url, $options);
			} catch (\Exception $e) {
				throw $e;
			}

			if($this->debug()){
				#	Tools::pre($data->getStatusCode());
				#	Tools::pre($data->getHeaders());
				#	Tools::pre($data->getBody()->getContents());
				#	die('!!!hjkfdezjgfzegfgkfekz');
			}

			// http://docs.guzzlephp.org/en/latest/quickstart.html#making-a-request
			$code = $data->getStatusCode();
			$body = $data->getBody();
			$out  = $body->getContents();

			// JSON ?
			if (strpos($data->getHeader('content-type'), 'application/json') !== false){
				try{
					$out = json_decode($out, true);
				} catch (\Exception $e){
				}
			}

			#if($this->debug()) Tools::pre($out);

			if($code > 200){
				if(is_array($out) && NULL !== $out['error']['name']){
					throw new Exception($out, $code);
				}else
					if(is_array($out)){
						throw new Exception(['name' => 'Error', 'remote' => $out]);
					}

				throw new Exception('Api Exception', $code);
			}

			if($code == 200 && $this->useCache()){
				$cached = $this->cacheSerialize($out);

				if($this->debug()){
					Tools::pre(
							'now= '.date("Y-m-d H:i:s"),
							'ttl= '.$this->cacheTTL(),
							'date= '.date("Y-m-d H:i:s", time()+$this->cacheTTL())
					);
				}

				wp_cache_set($cacheKey, $cached, 'yoapi', $this->cacheTTL());

				if($this->debug()) Tools::pre('CACHED in '.(microtime(true) - $now).' ms');
			}else{
				if($this->debug()) Tools::pre('NOT CACHED because useCache() is false');
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
		return $this->request('get', $url, [], $options);
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
		return $this->request('post', $url, $data, $options);
		/*$opt = ['body' => $data];
		if(!empty($options)) $opt = $opt + $options;

	#	pre($url, $opt);
	#	die();

		return $this->request('post', $url, $opt);*/
	}

	/**
	 * PUT
	 *
	 * @param $url
	 * @param $data
	 *
	 * @return array|\GuzzleHttp\Stream\StreamInterface|null|string
	 */
	public function put($url, Array $data, Array $options = []){
		//return $this->request('put', $url, ['body' => $data]);
		return $this->request('put', $url, $data, $options);
	}

	/**
	 * DELETE
	 *
	 * @param $url
	 * @param $data array of POST value
	 *
	 * @return array|\GuzzleHttp\Stream\StreamInterface|null|string
	 */
	/*public function delete($url, $data=NULL){
		$opt = [];
		if(!empty($data)) $opt = ['body' => $data];

		return $this->request('delete', $url, $opt);
	}*/

	public function delete($url, Array $data = [], Array $options = []){
		return $this->request('delete', $url, $data, $options);
	}

//--

	/**
	 * GRAPHQL
	 *
	 * @param $query
	 * @param $variables
	 *
	 * @return array|\GuzzleHttp\Stream\StreamInterface|null|string
	 */
	public function graphql($query, Array $variables=[]){

		$post = [
			'query' => $query,
			'variables' => $variables
		];

		$options = [
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			]
		];

		$data = [];

		try{
			$data = $this->post('/graphql', $post, $options);
		} catch (Exception $e){
			print_r($e); // affiche la trace pour aider a voir l'erreur
		}

		return $data['data'];
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
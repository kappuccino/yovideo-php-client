<?php

namespace YoVideo;

use GuzzleHttp\Client;
use YoVideo\Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class Affilinet{

	private $rest;

	public function  __construct(){

		$config = Config::get();
		$options = [
			'base_url' => 'http://'.$config['host'].':'.$config['affilinet'],
			'defaults' => [
				'timeout' => 60,
				'headers' => [
					'Accept' => 'application/json',
				]
			]
		];

	#	pre($options);

		$this->rest = new Client($options);
	}

	private function request($verb, $url, $opt=array()){

		$options = array_merge(['exceptions' => false], $opt);

	#	pre($url);

		try {
			$data = $this->rest->$verb($url, $options);
		} catch (\Exception $e) {
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

		return $out;
	}

	public function post($url, Array $data = [], Array $options = []){
		$opt = ['body' => $data];
		if(!empty($options)) $opt = $opt + $options;
		return $this->request('post', $url, $opt);
	}

	public function program(Array $ean, $program=''){

		$data = ['ean' => $ean];
		if($program) $data['program'] = $program;

		try{
			$result = $this->post('/ean', $data);
		} catch(Exception $e){
			throw $e;
		}

		return $result;
	}
}
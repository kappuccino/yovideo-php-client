<?php

namespace YoVideo;

class Social{

	public $page;
	public $result = [
			'facebook'   => '',
			'twitter'    => '',
			'googleplus' => '',
			'pinterest'  => ''
		];

	public function  __construct($page){
		$this->page = $page;
		return $this;
	}

	public function facebook(){
		$api   = file_get_contents('http://graph.facebook.com/?id=' . $this->page);
		$count = json_decode($api);

		return $count->shares;
	}

	public function tweeter(){
		$api   = file_get_contents('https://cdn.api.twitter.com/1/urls/count.json?url='.$this->page);
		$count = json_decode($api);

		return $count->count;
	}

	public function pinterest(){

		$api = file_get_contents( 'http://api.pinterest.com/v1/urls/count.json?callback%20&url=' . $this->url );

		$body = preg_replace( '/^receiveCount\((.*)\)$/', '\\1', $api );

		$count = json_decode( $body );

		return $count->count;
	}

	public function googleplus($id=NULL){

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, "https://clients6.google.com/rpc" );
		curl_setopt( $curl, CURLOPT_POST, 1 );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $this->page . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]' );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-type: application/json' ) );
		$curl_results = curl_exec( $curl );
		curl_close( $curl );

		$json = json_decode( $curl_results, true );

		return intval( $json[0]['result']['metadata']['globalCounts']['count'] );
	}

	public function getAll(){
		$this->result = [
			'facebook'   => $this->facebook(),
			'twitter'    => $this->tweeter(),
			'googleplus' => $this->googleplus(),
			'pinterest'  => $this->pinterest(),
		];
	}

	public function toJson(){
		return json_encode($this->result);
	}

}
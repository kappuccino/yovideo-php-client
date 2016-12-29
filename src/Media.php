<?php

namespace YoVideo;

use YoVideo\Exception;

class Media{

	public  $url;
	private $etag;
	private $media;
	private $size;
	private $default;
	private $notFound = false;
	private $bucket;
	private $cloudfront;
	private $props = [];

	public function __construct($media, $size=NULL){
		$config = Config::get();

		$this->media      = $media;
		$this->bucket     = $config['aws']['s3']['bucket'];
		$this->cloudfront = $config['aws']['cloudfront']['media'];

		// Memo the size
		if($size) $this->size = $size;

		// If size is known, find the thumbnail
		if($this->size) $this->thumbnail();

		return $this;
	}

	private function thumbnail(){

		// Par défaut on fournit la taille d'origine
		$this->url = $this->media['url'];
		$this->etag = $this->media['etag'];

		$this->prop('width', $this->media['width']);
		$this->prop('height', $this->media['height']);

		// Si on veut autre chose, allons chercher la vignettes
		if($this->size != 'full'){

			$thumbnails = $this->media['thumbnails'];
			if(!is_array($thumbnails)) return $this;

			$thumbnails = array_filter($thumbnails, function ($t) {
				return $t['name'] == $this->size;
			});

			// Utiliser la vignette, ou rester sur la taille d'origine
			if(!empty($thumbnails)){
				$thumbnails = array_values($thumbnails);
				$thumbnail = $thumbnails[0];

				$this->url = $thumbnail['url'];
				$this->etag = $thumbnail['etag'];

				$this->prop('width', $thumbnail['width']);
				$this->prop('height', $thumbnail['height']);
			}
		}

		return $this;
	}

	function fallback($url){
		$this->default = $url;
		return $this;
	}

	function size($size){
		$this->size = $size;
		$this->thumbnail();
		return $this;
	}

	function prop($name, $value=NULL){
		if(is_string($name) && !isset($value)) return $this->props[$name];

		if(is_array($name) && !isset($value)){
			foreach($name as $k => $v){
				$this->props[$k] = $v;
			}
			return $this;
		}

		if(is_string($name) && isset($value)){
			$this->props[$name] = $value;
			return $this;
		}
	}

	public function url(){
		if(empty($this->url) OR $this->notFound) return $this->default;

		$domain = (function_exists('yoCloudfront') && yoCloudfront())
			? $this->cloudfront
			: $this->bucket;

		$etag = ($this->etag)
			? '?etag='.$this->etag
			: NULL;

		return 'http://'.$domain.'/'.$this->url.$etag;
	}

	public function html($return=false){
		$props = [];

		if(!empty($this->props)){
			foreach($this->props as $k => $v){
				array_push($props, $k.'="'.$v.'"');
			}
		}

		$props = empty($props) ? '' : implode(' ', $props);

		$html = '<img src="'.$this->url().'"'.$props.'>';

		if($return) return $html;
		echo $html;
	}

	public function clean($type, $_id, $prefix){

		$request = new Request();

		try{
			$data = $request->post('/media/clean', [
				'type'   => $type,
				'_id'    => $_id,
				'prefix' => $prefix
			]);
		} catch(Exception $e){
		}

	}

	public function inject($type, $_id, $file=NULL){

		$request = new Request();

		$opt = [
			'url'  => 'http://' . $_SERVER['HTTP_HOST'] . $this->media,
			'type' => $type,
			'_id'  => $_id,
			'file' => $file
		];

		try{
			$data = $request->post('/media/inject', $opt);
		} catch(Exception $e){
		}

	}
}
<?php

namespace YoVideo;

use YoVideo\Exception;

class Trailer{

	private $url;
	private $id;
	private $ogUrl;
	private $yturl;
	private $embed;
	private $host;

	public function __construct($url){
		$this->url = trim($url);
		$this->parse();
		return $this;
	}

	private function parse(){

		$url = parse_url($this->url);
		$this->host = $url['host'];

		if ($this->host == 'www.youtube.com') {
			parse_str($url['query'], $arr);
			if ($arr['v'] != '') {
				$this->id    = $arr['v'];
				$this->ogUrl = 'http://gdata.youtube.com/feeds/api/videos/' . $this->id . '?alt=json';
				$this->yturl = 'http://www.youtube.com/?v=' . $this->id;
				$this->embed = 'http://www.youtube.com/embed/' . $this->id . '?autoplay=1&rel=0';
			}
		}
	}

	public function getOg(){

		if(!$this->ogUrl) return false;

		$curlHandle = curl_init();
		curl_setopt_array($curlHandle, array(
			CURLOPT_URL				=> $this->ogUrl,
			CURLOPT_HEADER 			=> false,
			CURLINFO_HEADER_OUT		=> true,
			CURLOPT_VERBOSE 		=> true,
			CURLOPT_RETURNTRANSFER 	=> true,
			CURLOPT_FOLLOWLOCATION 	=> true,
			CURLOPT_CONNECTTIMEOUT	=> 0.2,
		));

		$raw = curl_exec($curlHandle);

		if($raw !== false){
			$contentType	= curl_getinfo($curlHandle, CURLINFO_CONTENT_TYPE);
			$size			= curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);
			$headers		= mb_substr($raw, 0, $size);
			$contents		= mb_substr($raw, $size);
			curl_close($curlHandle);

			# YOU-TUBE.COM
			if($this->host == 'www.youtube.com' && strlen($raw) > 50){
				$json = json_decode($raw, true);

				$opengraph = array(
					'og:site_name' 		=> 'Youtube',
					'og:title'			=> $json['entry']['title']["\$t"],
					'og:url'			=> $yturl,
					'og:image'			=> $json['entry']["media\$group"]["media\$thumbnail"][0]['url'],
					'og:video'			=> $embed,
					'og:video:type'		=> 'application/x-shockwave-flash',
					'og:video:width'	=> $json['entry']["media\$group"]["media\$thumbnail"][0]['width'],
					'og:video:height'	=> $json['entry']["media\$group"]["media\$thumbnail"][0]['height'],
					'yt:id'             => $ytid
				);

			}
		}

		return $opengraph ?: false;
	}

	public function getEmbed(){
		$html = '';

		if($this->host == 'www.youtube.com'){
			$og = $this->getOg();

			$html = '<iframe width="'.$og['og:video:width'].'" height="'.$og['og:video:height'].'" '.
					'src="https://www.youtube.com/embed/'.$this->id.'?rel=0&controls=1&showinfo=1" '.
					'frameborder="0" allowfullscreen></iframe>';
		}

		return $html;
	}

}
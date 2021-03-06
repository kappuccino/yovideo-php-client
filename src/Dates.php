<?php

namespace YoVideo;

class Dates extends Model{

	public function  __construct(){
		parent::__construct();
	}

	public function __destruct(){
		$this->set(NULL);
	}

	public function search($language, $from, $to, $mode=NULL, $limit=NULL){

		$url = '/dates/'.$language;
		$post = ['from' => $from, 'to' => $to, 'limit' => $limit];

		if($mode) $url = '/dates/'.$mode.'/'.$language;

		try{
			$results = $this->request->post($url, $post);
		} catch(Exception $e){
			throw $e;
		}

		// weight sort
		/*if(!empty($results)){
			usort($results, function($a, $b){
				if($a['weight'] == $b['weight']) return 0;
				return $a['weight'] > $b['weight'] ? -1 : 1;
			});
		}*/

		if($mode){
			$out = [];

			foreach($results as $n => $film){
				$film = new Film($film);
				$film->nearestDate('fr', $mode, $from, $to);
				$out[] = $film;
			}

		}else{

			foreach($results as $k => $res){
				foreach($res as $n => $r){
					$film = new Film($r);
					$film->nearestDate('fr', $k, $from, $to);
					$results[$k][$n] = $film;
				}
			}

			$out = $results;
		}

		$this->set($out);

		return $this;
	}

	public function searchQL($language, $from, $to, $mode=NULL, $limit=NULL){

		$template =
		'filmByDate(mode:"%mode%", language:$language, from:$from, to:$to, limit:$limit){
			_id
			title
			weight
			
			locale(language:$language){
				language
				title
			}
			director{
				_id
				name
			}
			support(mode:"%mode%"){ # mode + language utilisé par Film::nearestDate()
				mode
				language    
				date
				date_
			}
			media(main:true, poster:true){
				url
				main
				poster
				thumbnails{
					etag
					name
					height
					width
					url
				}
			}
		}';

		$requests = '';
		foreach($mode as $m){
			 $requests .= PHP_EOL.$m.': '.str_replace('%mode%', $m, $template).PHP_EOL;
		}

		$query = 'query filmByDate($language:String, $from:String, $to:String, $limit:Int){'.
			$requests.
		'}';

		try{
			$results = $this->request->graphql($query,
			[
				'language' => $language,
				'from' => $from,
				'to' => $to,
				'limit' => $limit
			]);
		} catch(Exception $e){
			throw $e;
		}

	#	pre($query);
	#	pre($results);
	#	die();

		// weight sort
		/*if(!empty($results)){
			usort($results, function($a, $b){
				if($a['weight'] == $b['weight']) return 0;
				return $a['weight'] > $b['weight'] ? -1 : 1;
			});
		}*/

	#	pre($results);
	#	die();

		foreach($results as $k => $res){
			foreach($res as $n => $r){
				$film = new Film($r);
				$film->nearestDate('fr', $k, $from, $to);
				$out[$k][$n] = $film;
			}
		}

		#pre($out);
		#die();

		$this->set($out);

		return $this;
	}

	public function searchCineActorQL($language, $from, $to, $limit=NULL){

		$query = 'query filmByDate($language:String, $from:String, $to:String, $limit:Int){
			filmByDate(mode:"cine", language:$language, from:$from, to:$to, limit:$limit){
				_id
				title
				weight
				
				locale(language:$language){
					language
					title
				}

				actor{
					_id
					name
					media(main:true, poster:true){
						etag
						url
						size
						main
						poster
						thumbnails{
							url
							etag
							name
							height
							width
						}
					}
				}

				support(mode:"cine"){ # mode + language utilisé par Film::nearestDate()
					mode
					language    
					date
					date_
				}
			}
		}';

		try{
			$results = $this->request->graphql($query, [
				'language' => $language,
				'from' => $from,
				'to' => $to,
				'limit' => $limit
			]);
		} catch(Exception $e){
			throw $e;
		}

	#	pre($query);
	#	pre($results);
	#	die();

		// weight sort
		/*if(!empty($results)){
			usort($results, function($a, $b){
				if($a['weight'] == $b['weight']) return 0;
				return $a['weight'] > $b['weight'] ? -1 : 1;
			});
		}*/

	#	pre($results);
	#	die();

		foreach($results as $k => $res){
			foreach($res as $n => $r){
				$film = new Film($r);
				$film->nearestDate('fr', $k, $from, $to);
				$out[$k][$n] = $film;
			}
		}

		if(!empty($out['filmByDate'])) $out = $out['filmByDate'];

		#pre($out);
		#die();

		$this->set($out);

		return $this;
	}

// HELPERS /////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function previousWednesday(){
		return date('Y-m-d', strtotime('last Wednesday'));
	}

	public function nextWednesday($more=NULL){
		$str = 'next Wednesday';
		if($more) $str .= ' '.$more;

		return date('Y-m-d', strtotime($str));
	}

	public function nextTuesday($more=NULL){
		$str = 'next Tuesday';
		if($more) $str .= ' '.$more;

		return date('Y-m-d', strtotime($str));
	}

}
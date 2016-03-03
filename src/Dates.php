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
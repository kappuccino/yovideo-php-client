<?php

namespace YoVideo;

class Star extends Model{

	static $imageDefault = '/vendor/yovideo/api/assets/img/star/_generic.jpg';

	public function  __construct($data = array()){
		if(!empty($data)) $this->set($data);
		parent::__construct();
	}

	public function getById($id){

		$url  = '/star/'.$id;

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		$this->set($data);

		return $this;
	}

	public function getFullById($id, Array $opt = []){

		$url = '/star/'.$id.'/full';

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		// Construir des Film d'après la filmo
		/*if(!empty($data['filmo'])){
			foreach($data['filmo'] as $job => $films){
				foreach($films as $n => $film){
					$data['filmo'][$job][$n] = new Film($film);
				}
			}
		}*/

		$this->set($data);

		return $this;
	}

	public function search(Array $post){

		$url  = '/star';

		try {
			$result = $this->request->post($url, $post);
		} catch (Exception $e){
			throw $e;
		}

		$data = $result['data'];

		$this->setTotal($result['total']);

		if(!empty($data)){
			foreach($data as $n => $e){
				$data[$n] = new Star($e);
			}
		}

		$this->set($data);

		return $this;
	}

	public function tournage(){

		$me  = $this->getId();
		$url = '/star/'.$me.'/tournage';

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		if(!empty($data)){
			foreach($data as $n => $e){

				// Isoler les images de tournage ou il n'y a que cet acteur
				$media = array_filter($e['media'], function($medium) use ($me){
					if(!$medium['onset']) return true;
					return is_array($medium['stars']) && in_array($me, $medium['stars']);
				});

				// Réinjecter les medias nettoyés
				$e['media'] = $media;

				$data[$n] = new Film($e);
			}
		}

		$this->set('tournage', $data, false);

		return $this;
	}

	public function stats($type){

		$url  = '/star/'.$this->getId().'/stats/'.$type;

		try{
			$data = $this->request->get($url);
		} catch(ApiException $e){
			throw $e;
		}

		return $data;
	}

	/**
	 * Recupère la liste des relations et les ajouts à la Star courante
	 *
	 * @return $this
	 * @throws Exception
	 * @throws \Exception
	 */
	public function getRelations(){

		$url  = '/star/'.$this->get('_id').'/relations';

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		if(!empty($data['relations'])){
			$rels = $data['relations'];

			foreach($rels as $n => $rel){
				$rel['to'] = new Star($rel['to']);
				$rels[$n] = $rel;
			}

			$this->set('relations', $rels, false);
		}

		return $this;
	}


// HELPERS /////////////////////////////////////////////////////////////////////////////////////////////////////////////


	/**
	 * Retourne le nom formaté
	 *
	 * @return array|null|string
	 */
	public function displayName(){
		return $this->get('name');
	}

	/**
	 * Retourne le nom d'un job au format humain
	 *
	 * @param $job string Le nom du champs
	 *
	 * @return string
	 */
	static public function jobName($job){
		if($job == 'actor')         return 'Acteur';
		if($job == 'director')      return 'Réalisateur';
		if($job == 'scriptwriter')  return 'Scénariste';
		if($job == 'composer')      return 'Compositeur';
		if($job == 'music')         return 'Musique non originale';
		if($job == 'scripthelper')  return 'Aide au scénario';
		if($job == 'featuring')     return 'Figurant';
		if($job == 'photography')   return 'Directeur de la photographie';
		if($job == 'producer')      return 'Producteur';
		if($job == 'author')        return 'Auteur';
		return $job;
	}

	/**
	 * Retourne l'URL complète pour une star
	 *
	 * @param boolean full
	 *
	 * @return string
	 */
	public function permalink($full=false){
		$url = '/fr/star/'.$this->getId().'/';
		if($full) $url = 'http://'.$_SERVER['HTTP_HOST'].$url;
		return $url;
	}

	public function subPermalink($sub, $full=false){
		$url = $this->permalink($full).$sub;
		return $url;
	}

	/**
	 * Retourne un lien <a></a> HTML pointant vers le detail d'une star
	 *
	 * @param null  $label
	 * @param null  $url
	 * @param array $opt
	 *
	 * @return string|void
	 */
	public function htmlLink($label=NULL, $url=NULL, $opt=[]){

		if(empty($label)) $label = $this->get('name');

		$opt = array_merge($opt, [
			'title' => $this->displayName()
		]);

		return parent::htmlLink($label, $url ?: $this->permalink(true), $opt);
	}

	public function htmlLinkProps($label=NULL, $url=NULL, $opt=[]){
		$opt['props'] = true;
		return $this->htmlLink($label, $url, $opt);
	}

	/**
	 * Retourne une filmographie classée par date et par job
	 *
	 * @return array
	 */
	public function filmographie(){

		$raw = $this->get('filmo');
		if(empty($raw)) return [];

		$filmo = [];

		foreach($raw as $job => $films){

			// Order by DATE
			 usort($films, function($a, $b){
				 if(is_a($a, '\YoVideo\Film')){
					 return $a->get('date') < $b->get('date');
				 }else{
					 return $a['date'] < $b['date'];
				 }
			});

			// Transform as FILM object
			foreach($films as $n => $e){
				$films[$n] = is_a($e, '\YoVideo\Film') ? $e : new Film($e);
			}

			$filmo[$job] = $films;
		}

		return $filmo;
	}

	/**
	 * Retourne une filmographie classée par date (sans job = FLAT)
	 *
	 * @return array
	 */
	public function filmographieByDate($limit=-1){

		$filmo = [];
		$raw = $this->get('filmo');
		if(empty($raw)) return [];

		// Flat check
		$keys = array_keys($raw);
		if($keys[0] != 0){
			foreach ($raw as $job => $films) {
				foreach ($films as $film) {
					$filmo[] = new Film($film);
				}
			}
		}

		// Already flat
		else{
			$filmo = $raw;
		}

		// Order by DATE
		usort($filmo, function($a, $b){
			return $a->get('date') > $b->get('date');
		});

		// Transform as FILM object (if not already)
		foreach($filmo as $n => $e){
			$filmo[$n] = is_a($e, '\YoVideo\Film') ? $e : new Film($e);
		}

		if($limit > 0) $filmo = array_splice($filmo, 0, $limit);

		return $filmo;
	}

	public function portraitURL_(){
		$default   = '/data/yovideo/img/_generique.jpg';
		$thumbnail = NULL;
		$media     = $this->get('media');

		$media = array_filter($media, function($e){
			if($e['poster'] && $e['main']) return $e;
		});

		if(empty($media)) return $default;
		$media = $media[0];

		$url = new Media('/'.$media['url']);
		$url = $url->params(['height' => 400])->url();

		return $url;
	}

	public function portraitURL($size='small'){

		$media = $this->get('media');
		if(empty($media)) return self::$imageDefault;

		$media = array_filter($media, function($e){
			if($e['poster'] && $e['main']) return $e;
		});

		if(empty($media)) return self::$imageDefault;

		// Remet l'Array dans le bon ordre
		$media = array_values($media);

		$image = new Media($media[0], $size);
		$image->fallback(self::$imageDefault);

		return $image->url();
	}

	/**
	 * Retourne l'âge ou false si la date est inconnue ou pb de format (todo: API gérer la date en JSON)
	 *
	 * @return bool
	 */
	public function age(){

		$birth = $this->get('birthDate');
		if(empty($birth)) return false;

		try{
			$birth = new \DateTime($birth);
		} catch (\Exception $e){
			// silent failure
			return false;
		}

		try{
			$death = new \DateTime($this->get('deathDate'));
		} catch (\Exception $e){
			// silent failure
		}

		$date = $death ?: new \DateTime();

		$interval = $birth->diff($date);
		return ($interval->y > 0) ? $interval->y : false;
	}

	/**
	 * Retourne depuis combien d'année cette personne est morte
	 *
	 * @return bool
	 */
	public function death(){

		$death = $this->get('deathDate');
		if(empty($death)) return false;

		try{
			$death = new \DateTime($death);
		} catch (\Exception $e){
			// silent failure
			return false;
		}

		$interval = $death->diff(new \DateTime());
		return ($interval->y > 0) ? $interval->y : false;
	}

	public function seo($format){

		$keys = ['name', 'birth', 'type'];

		foreach($keys as $k){
			$k_  = '['.$k.']';
			if(strpos($format, $k_) !== false){
				$new = NULL;

				if($k == 'name'){
					$new = $this->displayName();
				}else
				if($k == 'birth'){
					$birthDate = $this->get('birthDate');
					$new = !empty($birthDate) ? 'né(e) le '.$birthDate : '';
				}else
				if($k == 'type'){
					$type = $this->get('type');

					pre($type);
					die();
				}

				$format = str_replace($k_, $new, $format);
			}
		}

		return $format;
	}

	public function seoTitle(){
		$out = [];

		$out[] = $this->displayName();

		$filmo = $this->get('filmo');
		if(!empty($filmo)){
			$jobs = [];
			foreach($filmo as $job => $films){
				$jobs[] = self::jobName($job);
			}
			if(!empty($jobs)) $out[] = implode(' ', $jobs);
		}

		$Country = new Country();
		$nationality  = $this->get('nationality');
		$birthCountry = $this->get('birthCountry');

		if(!empty($nationality)){
			$tmp = $Country->getByCode($nationality);
			if(!empty($tmp)) $out[] = $tmp['nationality'] ?: $tmp['name'];
		}else
		if(!empty($birthCountry)){

		}

		return implode(' ', $out);
	}

	public function getScored($ids){

		// Demander le SCORE pour toute ces Stars
		try {
			$Search = $this->search(['_id' => $ids, 'score' => true]);
		} catch (Exception $e){
			throw $e;
		}


		// Trier les stars en fonction de la moyenne de leur film
		$Result = $Search->get();
		$field  = 'score.avg';
		usort($Result, function ($a, $b) use ($field) {
			if ($a->get($field) == $b->get($field)) return 0;

			return $a->get($field) < $b->get($field) ? 1 : -1;
		});

		return $Result;
	}
}
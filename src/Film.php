<?php

namespace YoVideo;

class Film extends Model{

	public function  __construct($data = array()){
		if(!empty($data)) $this->set($data);
		parent::__construct();
	}

	public function search(Array $post){

		$url  = '/film';

		try{
			$result = $this->request->post($url, $post);
		} catch(Exception $e){
			throw $e;
		}

		$data = $result['data'];
		$this->setTotal(intval($result['total']));

		if(!empty($data)){
			foreach($data as $n => $e){
				$tmp = new Film($e);
				$tmp->starMapping();

				$data[$n] = $tmp;
			}
		}

		$this->set($data);

		return $this;
	}

	public function getById($id){

		$url  = '/film/'.$id;

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		$this->set($data);
		$this->starMapping();

		return $this;
	}

	public function tournage($star=NULL){

		$media = $this->get('media');
		if(empty($media)) return [];

		$media = array_filter($media, function($e) use ($star){
			if($star){
				$stars = $e['stars'] ?: [];

				if(!empty($stars)){
					$stars = array_map(function($e){
						return is_array($e) ? $e['_id'] : $e;
					}, $stars);
				}

				return $e['onset'] && in_array($star, $stars);
			}

			return $v = $e['onset'];
		});

		$media = array_values($media);
		usort($media, function($a, $b){
			if($a['url'] > $b['url']) return 1;
			if($a['url'] > $b['url']) return -1;
			return 0;
		});

		return $media;
	}

	public function posters(){

		$media = $this->get('media');
		if(empty($media)) return [];

		$media = array_filter($media, function($e){
			return ($v = $e['poster'] && !$e['main']);
		});


		$media = array_values($media);
		usort($media, function($a, $b){
			if($a['url'] > $b['url']) return 1;
			if($a['url'] > $b['url']) return -1;
			return 0;
		});

		return $media;
	}

	public function stats($type){

		$url  = '/film/'.$this->getId().'/stats/'.$type;

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		return $data;
	}

	/**
	 * Retourne les données de la locale courante
	 *
	 * @param null $field
	 * @param null $force
	 * @return array|bool
	 */
	public function locale($field=NULL, $force=NULL){

		$locale = $this->get('locale');
		if(empty($locale)) return false;

		$language = $force ? : 'fr';

		foreach($locale as $e){
			if($e['language'] == $language){
				if($field) return array_key_exists($field, $e) ? $e[$field] : false;
				return $e;
			}
		}

		return false;
	}

	public function supportMedia($support){

		$media = $this->get('media');

		if(empty($media) OR empty($support['ean'])) return false;

		// Filter les media à la recherche de notre support
		$ean = $support['ean'];

		$media = array_filter($media, function($medium) use($ean){
			return $medium['ean'] == $ean;
		});

		// Des media, mais pas celui qu'on souhaite avoir
		if(empty($media)) return false;

		$media = array_values($media);
		$media = $media[0];

		return $media;
	}

	/**
	 * Retourne une liste de Pack qui contient ce Film
	 *
	 * @return $this
	 * @throws Exception
	 * @throws \Exception
	 */
	public function inPack(){

		$url = '/film/'.$this->get('_id').'/pack';

		try{
			$data = $this->request->get($url);
		} catch(Exception $e){
			throw $e;
		}

		if(!empty($data)){

			// Film Object
			$data = array_map(function($e){
				return new Film($e);
			}, $data);

			// Order by date DESC
			usort($data, function($a, $b){
				$a = $a->get('date');
				$b = $b->get('date');
				if ($a == $b) return 0;
				return ($a < $b) ? -1 : 1;
			});

			// Injection
			$this->set('inPack_', $data, false);
		}

		return $this;
	}


// HELPERS /////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Retourne le titre du film en tenant compte de la locale
	 *
	 * @param bool $main
	 * @return array|null|string
	 */
	public function displayTitle($main=true){

		$field = $main ? 'title' : 'subtitle';

		$locale = $this->locale();
		if($locale[$field]){
			$title = $locale[$field];
		}else{
			$title = $this->get($field);
		}

		return $this->formatTitle($title);
	}

	public function formatTitle($title){
		$title = str_replace(['<BR>', '<br>', '<br />', '<br/>'], ' + ', $title);
		return $title;
	}

	/**
	 * Retourne l'URL complète pour le film
	 *
	 * @param bool $full
	 * @return string
	 */
	public function permalink($full=false){
		$url = '/fr/film/'.$this->getId().'/';
		if($full) $url = 'http://'.$_SERVER['HTTP_HOST'].$url;
		return $url;
	}

	public function subPermalink($sub, $full=false){
		$url = $this->permalink($full).$sub;
		return $url;
	}

	public function htmlLink($label=NULL, $url=NULL, $opt=[]){

		if(empty($label)) $label = $this->displayTitle();

		$opt = array_merge($opt, [
			'title' => $this->displayTitle()
		]);

		return parent::htmlLink($label, $url ?: $this->permalink(true), $opt);
	}

	public function htmlLinkProps($label=NULL, $url=NULL, $opt=[]){
		$opt['props'] = true;
		return $this->htmlLink($label, $url, $opt);
	}

	public function jaquetteURL($size='small', $fallback=false){

		$media = $this->get('media');
		if(empty($media)) return $fallback;

		$media = array_filter($media, function($e){
			if($e['poster'] && $e['main']) return $e;
		});

		// Remet l'Array dans le bon ordre
		$media = array_values($media);

		if(empty($media)) return $fallback;

		$image = new Media($media[0]);
		$image->fallback($fallback);
		$image->size($size);

		return $image->url();
	}

	public function imageUrl($image){
		$default = '/data/yovideo/img/_generique.jpg';
		if(!$image OR !is_array($image)) return $default;
		if(!$image['url']) return $default;
		return $image['url'];
	}

	/**
	 * Est-ce que le film a une bannde annonce (youtube + local)
	 *
	 * @return bool
	 */
	public function hasTrailer(){
		$has = false;

		// Youtube
		$trailers = $this->get('trailers');
		if(!empty($trailers)) $has = !empty($trailers);

		if($has) return true;

		// Media
		$url = $this->trailerMediaUrl();
		$has = !empty($url);

		return $has;
	}

	public function hasWeb(){
		$raw = trim($this->get('web'));
		return !empty($raw);
	}

	public function hasOnset(){
		$media = $this->get('media');
		if(empty($media)) return false;

		$media = array_filter($media, function($medium){
			return $medium['onset'];
		});

		return !empty($media);
	}

	public function hasPoster(){
		$media = $this->get('media');
		if(empty($media)) return false;

		$media = array_filter($media, function($medium){
			return $medium['poster'] && $medium['main'];
		});

		return !empty($media);
	}

	public function hasPosters(){
		$media = $this->get('media');
		if(empty($media)) return false;


		$media = array_filter($media, function($medium){
			return $medium['poster'] && !$medium['main'];
		});

		return (empty($media) OR count($media) == 0) ? false : true;
	}

	public function hasSupport($mode=NULL){
		$support = $this->get('support');

		if(!empty($mode)){
			$support = array_filter($support, function($s) use ($mode){
				return $s['mode'] == $mode;
			});
		}

		return !empty($support);
	}

	public function isSerie(){
		$isSerie = $this->get('isSerie');
		return (bool)$isSerie;
	}

	/**
	 * Rertourne l'URL de la bande annonce pour ce film (trailer principal)
	 *
	 * @return bool|string|Media
	 */
	public function trailerUrl($index=0){

		$trailers = $this->get('trailers');
		if(empty($trailers)) return false;

		$trailers = explode("\n", trim($trailers));

		foreach($trailers as $n => $e){
			$trailers[$n] = new Trailer($e);
		}

		return (func_num_args() == 0) ? $trailers : $trailers[$index];
	}

	public function trailerMediaUrl(){

		$media = $this->get('media');
		$media = array_filter($media, function($medium){
			return $medium['trailer'] == true;
		});

		if(empty($media)) return false;
		$media = array_values($media);

		$config = Config::get();
		$bucket = 'http://'.$config['aws']['s3']['bucket'].'/';
		$url = $bucket.$media[0]['url'];

		return $url;
	}

	public function nearestDate($language, $mode, $from, $to){

		$times = [];
		$support = $this->get('support');

		$from = is_a($from, 'DateTime') ? $from : new \DateTime($from);
		$from = $from->getTimestamp();

		$to = is_a($to, 'DateTime') ? $to : new \DateTime($to);
		$to = $to->getTimestamp();


		foreach($support as $e){
			$time = 0;

			try{
				$date = new \DateTime($e['date']);
				$time = $date->gettimestamp();
			} catch(\Exception $ex){
				// silence
			}

			if($e['language'] == $language && $e['mode'] == $mode && $time >= $from && $to <= $to){
				$times[$time] = $e;
			}
		}

		// On veut la date la plus proche = le timestamp le plus petit
		sort($times);
		$time = $times[0];

		// Noter le numéro de la semaien au passage
		list($y, $m, $d) = explode('-', $time['date']);

	#	pre($this->getId(), $support, $time, $y, $m, $d, $times);

		$time['week'] = date("Y-W", mktime(0,0,0, $m, $d, $y));
		$this->set('support_nearest_date', $time, false);
	}

	/**
	 * Rertourne le premier support du `mode` a être sortie (date ASC)
	 *
	 * @param $mode
	 * @return array|bool|null|string
	 */
	public function supportFirstByDate($mode){

		$support = $this->get('support');
		if(empty($support)) return false;

		$support = array_filter($support, function($a) use ($mode){
			return $mode === $a['mode'] && !empty($a['date']);
		});

		if(empty($support)) return false;

		usort($support, function($a, $b){
			try{
				$a = new \DateTime($a['date']);
				$a = $a->getTimestamp();
			} catch(\Exception $e){
				$a = '';
			}

			try{
				$b = new \DateTime($b['date']);
				$b = $b->getTimestamp();
			} catch(\Exception $e){
				$b = '';
			}

			return $a > $b;
		});

		$support = array_values($support);
		$support = $support[0];

		$support['date_human'] = Tools::date('%e %B %Y', $support['date']);

		return $support;
	}

	/*public function supportWeek(){
		$support = $this->get('support');

		if(!empty($support)){
			foreach($support as $n => $e){
				list($y, $m, $d) = explode('-', $e['date']);
				$e['date_week'] = date("W", mktime(0,0,0, $m, $d, $y));

				$support[$n] = $e;
			}
		}

		$this->set('support', $support, false);
	}*/

	public function resume(){
		$resume = $this->locale('resume') ?: $this->locale('resume', 'ww');

		if($resume){

			if(strpos($resume, '[FILM=') !== false){

				$pattern = "#\[FILM=([0-9]{1,})\](.*?)\[\/FILM\]#";
				preg_match_all($pattern, $resume, $rez, PREG_SET_ORDER);

				if(!empty($rez)){
					foreach($rez as $r){
						$resume = str_replace($r[0], $r[2], $resume);
					}
				}

			}

			# Nettoyage BBCode
			$resume = str_replace("[B]",  "<b>",  $resume);
			$resume = str_replace("[/B]", "</b>", $resume);

			// Retour ligne
			$resume = nl2br($resume);

			return $resume;
		}

		return false;
	}

	/**
	 * Retourne une ou plusieurs Star d'après le job
	 * Permet d'avoir facilement le premier réalisateur, ou les 3 premiers acteurs
	 *
	 * @param     $job
	 * @param int $count
	 * @return array|bool
	 * @throws Exception
	 */
	public function starSubset($job, $count=1){

		if(intval($count) <= 0) throw new Exception('count must be greater than zero');

		$stars = $this->get($job);
		if(empty($stars)) return false;

		foreach($stars as $n => $e){
			$stars[$n] = is_a($e, __NAMESPACE__.'\Star') ? $e : new Star($e);
		}

		if($count === 1) return $stars[0];
		return array_slice($stars, 0, $count);
	}

	/**
	 * Retour un array de star avec le nom et un lien au format HTML [<a>Star 1</a>, <a>Star 2</a>]
	 *
	 * @param $job
	 * @param $count
	 * @return array|string
	 * @throws Exception
	 */
	public function starListSimple($job, $count){
		$list = $this->starSubset($job, $count);

		if(empty($list)) return [];

		// starSubset retourne un objet et non un array quand on ne demande qu'une star
		// c'est pourquoi, on reforme l'array car cette fonction traite des array
		if($count == 1) $list = [$list];

		$out = [];
		foreach($list as $e){
			$out[] = $e->htmlLink(NULL, NULL, ['return' => true]);
		}

		return $out;
	}

	public function genreListSimple(){
		$genres = $this->get('genre');
		if(empty($genres)) return [];

		$out = [];
		$helper = new Genre();
		foreach($genres as $g){
			$g = $helper->getByCode($g);
			$out[] = '<a href="/fr/search/film/?genre="'.$g['code'].'>'.$g['name'].'</a>';
		}

		return $out;
	}

	public function typeListSimple(){
		$types = $this->get('type');
		if(empty($types)) return [];

		$out = [];
		$helper = new TypeFilm();
		foreach($types as $t){
			$t = $helper->getByCode($t);
			$out[] = '<a href="/fr/search/film/?type="'.$t['code'].'"">'.$t['name'].'</a>';
		}

		return $out;
	}

	public function countryListSimple(){
		$countries = $this->get('countries');
		if(empty($countries)) return [];

		$countries = explode(',', trim($countries));
		$countries = array_map('trim', $countries);

		$out = [];
		foreach($countries as $c){
			$out[] = '<a href="/fr/search/film/?country='.$c.'">'.$c.'</a>';
		}

		return $out;
	}

	public function web(){
		$raw = trim($this->get('web'));
		$sites = array_map(function($url){
			return [
				'url' => $url
			];

		}, explode("\n", $raw));

		return $sites;
	}

	public function seo($format){

		$keys = ['title', 'type', 'genre', 'date', 'director'];

		foreach($keys as $k){
			$k_  = '['.$k.']';
			if(strpos($format, $k_) !== false){
				$new = NULL;

				if($k == 'title'){
					$new = $this->displayTitle();
				}else
				if($k == 'type'){
					$type = $this->get('type');
					$new = !empty($type) ? $type[0] : '';
				}else
				if($k == 'genre'){
					$genre = $this->get('genre');
					$new = !empty($genre) ? $genre[0] : '';
				}else
				if($k == 'date'){
					$date = $this->get('date');
					$new = !empty($date) ? 'de '.$date : '';
				}else
				if($k == 'director'){
					$real = $this->get('director');
					$new = !empty($real) ? 'par '.$real[0]->displayName() : '';
				}

				$format = str_replace($k_, $new, $format);
			}
		}

		return $format;
	}

	public function typesGenre(){

		$genres = $this->genreListSimple();
		$types = $this->typeListSimple();

		return array_merge($types, $genres);
	}

	public function cineCount(){

		$count = $this->get('cineCount');

		if(empty($count)) return 'NC';

		$count = intval($count);

		$data = array(
			array(0,  		   9999, 	'Moins de 10.000'),
			array(10000, 	  49999, 	'Plus de 10.000'),
			array(50000, 	  99999, 	'Plus de 50.000'),
			array(100000,	 249999, 	'Plus de 100.000'),
			array(250000,	 499999, 	'Plus de 250.000'),
			array(500000,	 999999, 	'Plus de 500.000'),
			array(1000000, 	1999999, 	'Plus de 1.000.000'),
			array(2000000, 	2999999, 	'Plus de 2.000.000'),
			array(3000000, 	3999999, 	'Plus de 3.000.000'),
			array(4000000, 	4999999, 	'Plus de 4.000.000'),
			array(5000000,  9999999, 	'Plus de 5.000.000')
		);

		foreach($data as $def){
			if($count > $def[0] && $count <= $def[1]) return $def[2];
		}

		return 'Plus de 10.000.000';


	}

	public function opengraphMeta(){

		$resume = strip_tags($this->resume());
		$desc = substr($resume, 0, 200);
		if(strlen($resume) > 200) $desc .= '...';

		$out = [
			'og:title' => $this->displayTitle(),
			'og:type' => 'video.movie',
			'og:url' => $this->permalink(true),
			'og:description' => $desc
		];

		$img = $this->jaquetteURL('small', FILM_FALLBACK);
		if($img != FILM_FALLBACK) $out['og:image'] = $img;

		return $out;
	}

	public function twitterMeta(){
/*
<meta name="twitter:card" content="summary"/>
<meta name="twitter:description" content="sdfsdfsd"/>
<meta name="twitter:title" content="Blabla titre twitter"/>
<meta name="twitter:domain" content="Métiers Presse"/>
*/

		return [];
	}

	public function supportCount(){
		$support = array_filter($this->get('support'), function($s){
			return $s['mode'] != 'cine';
		});

		return count($support);
	}

	public function photoCount(){

		$media = $this->get('media');
		if(empty($media)) return 0;

		$media = array_filter($media, function($medium){
			return $medium['onset'];
		});

		return count($media);
	}

	public function castingCount(){
		$total = 0;

		foreach(\YoVideo\YoVideo::getPeople() as $p){
			$tmp = $this->get($p);
			if(!empty($tmp)) $total += count($tmp);
		}

		return $total;
	}
}
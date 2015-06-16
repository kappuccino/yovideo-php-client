<?php

namespace YoVideo;

class Genre{

	private $data = [];

	public function __construct(){
		$this->setData();
	}

	public function setData(){
		$this->data = [
			['code' => 'action', 'id' => '2', 'name' => 'Action'],
			['code' => 'animalier', 'id' => '140', 'name' => 'Animalier'],
			['code' => 'animation', 'id' => '4', 'name' => 'Animation'],
			['code' => 'art-martiaux', 'id' => '32', 'name' => 'Arts Martiaux'],
			['code' => 'aventure', 'id' => '1', 'name' => 'Aventures'],
			['code' => 'ballet', 'id' => '134', 'name' => 'Ballet'],
			['code' => 'bdvd', 'id' => '129', 'name' => 'BDVD'],
			['code' => 'beaux-arts', 'id' => '147', 'name' => 'Beaux-Arts'],
			['code' => 'biographie', 'id' => '40', 'name' => 'Biographie'],
			['code' => 'burlesque', 'id' => '74', 'name' => 'Burlesque'],
			['code' => 'catastrophe', 'id' => '71', 'name' => 'Catastrophe'],
			['code' => 'chasse-peche', 'id' => '144', 'name' => 'Chasse & Pêche'],
			['code' => 'chronique', 'id' => '35', 'name' => 'Chronique'],
			['code' => 'coffret', 'id' => '114', 'name' => 'Coffret'],
			['code' => 'comedie', 'id' => '8', 'name' => 'Comédie'],
			['code' => 'comedie-moeurs', 'id' => '42', 'name' => 'Comédie de Moeurs'],
			['code' => 'comedie-dramatique', 'id' => '11', 'name' => 'Comédie Dramatique'],
			['code' => 'comedie-fantastique', 'id' => '9', 'name' => 'Comédie Fantastique'],
			['code' => 'comedie-musicale', 'id' => '12', 'name' => 'Comédie Musicale'],
			['code' => 'comedie-policiere', 'id' => '13', 'name' => 'Comédie Policière'],
			['code' => 'comedie-romantique', 'id' => '10', 'name' => 'Comédie Romantique'],
			['code' => 'comique-moeurs', 'id' => '14', 'name' => 'Comique de moeurs'],
			['code' => 'compilation', 'id' => '137', 'name' => 'Compilation'],
			['code' => 'conte', 'id' => '29', 'name' => 'Conte'],
			['code' => 'court-metrage', 'id' => '39', 'name' => 'Court-métrage'],
			['code' => 'cuisine-jardinage-deco', 'id' => '148', 'name' => 'Cuisine, Jardinage & Déco'],
			['code' => 'culture', 'id' => '141', 'name' => 'Culture'],
			['code' => 'culture-gay', 'id' => '149', 'name' => 'Culture Gay'],
			['code' => 'danse', 'id' => '132', 'name' => 'Danse'],
			['code' => 'dessin-anime', 'id' => '3', 'name' => 'Dessin animé'],
			['code' => 'documentaire', 'id' => '27', 'name' => 'Documentaire'],
			['code' => 'drame', 'id' => '15', 'name' => 'Drame'],
			['code' => 'drame-psycho', 'id' => '33', 'name' => 'Drame Psychologique'],
			['code' => 'emission', 'id' => '108', 'name' => 'Emission'],
			['code' => 'enfant', 'id' => '133', 'name' => 'Enfants'],
			['code' => 'episode', 'id' => '43', 'name' => 'Episode'],
			['code' => 'epouvante', 'id' => '19', 'name' => 'Epouvante'],
			['code' => 'erotique', 'id' => '16', 'name' => 'Erotique'],
			['code' => 'espionnage', 'id' => '21', 'name' => 'Espionnage'],
			['code' => 'essai', 'id' => '75', 'name' => 'Essai'],
			['code' => 'familial', 'id' => '131', 'name' => 'Familial'],
			['code' => 'fantastique', 'id' => '24', 'name' => 'Fantastique'],
			['code' => 'gore', 'id' => '23', 'name' => 'Gore'],
			['code' => 'guerre', 'id' => '20', 'name' => 'Guerre'],
			['code' => 'hentai', 'id' => '110', 'name' => 'Hentai'],
			['code' => 'historique', 'id' => '22', 'name' => 'Historique'],
			['code' => 'horreur', 'id' => '25', 'name' => 'Horreur'],
			['code' => 'humour', 'id' => '28', 'name' => 'Humour'],
			['code' => 'jeu', 'id' => '115', 'name' => 'Jeu'],
			['code' => 'karaoke', 'id' => '120', 'name' => 'Karaoké'],
			['code' => 'magazine', 'id' => '130', 'name' => 'Magazine'],
			['code' => 'manga', 'id' => '111', 'name' => 'Manga'],
			['code' => 'melodrame', 'id' => '76', 'name' => 'Mélodrame'],
			['code' => 'methode', 'id' => '113', 'name' => 'Méthode'],
			['code' => 'mini-serie', 'id' => '109', 'name' => 'Mini-Série'],
			['code' => 'moyen-metrage', 'id' => '41', 'name' => 'Moyen-métrage'],
			['code' => 'musical', 'id' => '70', 'name' => 'Musical'],
			['code' => 'musique-classique', 'id' => '118', 'name' => 'Musique Classique'],
			['code' => 'nature', 'id' => '143', 'name' => 'Nature'],
			['code' => 'opera', 'id' => '117', 'name' => 'Opéra'],
			['code' => 'peplum', 'id' => '31', 'name' => 'Péplum'],
			['code' => 'policier', 'id' => '6', 'name' => 'Policier'],
			['code' => 'politique', 'id' => '34', 'name' => 'Politique'],
			['code' => 'porno', 'id' => '36', 'name' => 'Porno'],
			['code' => 'retrospective', 'id' => '46', 'name' => 'Rétrospective'],
			['code' => 'road-movie', 'id' => '30', 'name' => 'Road movie'],
			['code' => 'saison', 'id' => '136', 'name' => 'Saison'],
			['code' => 'sante-bien-etre', 'id' => '146', 'name' => 'Santé & Bien-être'],
			['code' => 'science-decouverte', 'id' => '145', 'name' => 'Science & Découverte'],
			['code' => 'science-fiction', 'id' => '17', 'name' => 'Science-Fiction'],
			['code' => 'segment', 'id' => '116', 'name' => 'Segment'],
			['code' => 'serie', 'id' => '37', 'name' => 'Série'],
			['code' => 'serie-animation', 'id' => '44', 'name' => 'Série d\'Animation'],
			['code' => 'societe-debat', 'id' => '139', 'name' => 'Société et débats'],
			['code' => 'spectacle', 'id' => '112', 'name' => 'Spectacle'],
			['code' => 'sport', 'id' => '77', 'name' => 'Sport'],
			['code' => 'suspens', 'id' => '7', 'name' => 'Suspense'],
			['code' => 'tv', 'id' => '38', 'name' => 'Téléfilm'],
			['code' => 'theatre', 'id' => '73', 'name' => 'Théâtre'],
			['code' => 'thriller', 'id' => '5', 'name' => 'Thriller'],
			['code' => 'variete', 'id' => '119', 'name' => 'Variétés'],
			['code' => 'voyage', 'id' => '142', 'name' => 'Voyages'],
			['code' => 'western', 'id' => '26', 'name' => 'Western']
		];
	}

	public function all(){
		return $this->data;
	}

	public function getByID($id){
		$data = array_filter($this->data, function($e) use ($id){
			return $id == $e['id'];
		});

		if(empty($data)) return false;

		$data = array_values($data);
		return $data[0];
	}

	public function getByCode($id){
		$data = array_filter($this->data, function($e) use ($id){
			return $id == $e['code'];
		});

		if(empty($data)) return false;

		$data = array_values($data);
		return $data[0];
	}

}
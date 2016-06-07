<?php

class Subscriptions extends AppModel {

    public $useTable = 'subscriptions';
    
    public $apps = array(
	    array(
		    'id' => 1,
		    'title' => 'KRS',
		    'slug' => 'krs',
	    ),
	    array(
		    'id' => 2,
		    'title' => 'NGO',
		    'slug' => 'ngo',
	    ),
	    array(
		    'id' => 3,
		    'title' => 'Kultura',
		    'slug' => 'kultura',
	    ),
	    array(
		    'id' => 4,
		    'title' => 'Prawo',
		    'slug' => 'prawo',
	    ),
	    array(
		    'id' => 5,
		    'title' => 'Kto Tu Rządzi?',
		    'slug' => 'kto_tu_rzadzi',
	    ),
	    array(
		    'id' => 6,
		    'title' => 'Media',
		    'slug' => 'media',
	    ),
	    array(
		    'id' => 7,
		    'title' => 'Zamówienia Publiczne',
		    'slug' => 'zamowienia_publiczne',
	    ),
	    array(
		    'id' => 8,
		    'title' => 'Orzecznictwo',
		    'slug' => 'orzecznictwo',
	    ),
	    array(
		    'id' => 9,
		    'title' => 'Sejmometr',
		    'slug' => 'sejmometr',
	    ),
    );

}			        
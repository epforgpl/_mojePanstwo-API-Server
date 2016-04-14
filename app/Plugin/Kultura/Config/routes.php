<?php

	Router::connect('/kultura/data/:id', array(
		'plugin' => 'Kultura', 
		'controller' => 'Kultura', 
		'action' => 'data',
	), array(
		'id' => '[0-9]+',
	));
	
	Router::connect('/kultura/indeksy/:id', array(
		'plugin' => 'Kultura', 
		'controller' => 'Indeksy', 
		'action' => 'view',
	), array(
		'id' => '[0-9]+',
	));

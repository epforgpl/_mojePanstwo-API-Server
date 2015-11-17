<?php

Router::connect('/collections/collections/:id', array(
	'plugin' => 'Collections', 
	'controller' => 'Collections',
	'action' => 'view',
), array(
	'id' => '[0-9]+',
	'pass' => array('id'),
));

Router::connect('/collections/collections/edit/:id', array(
	'plugin' => 'Collections',
	'controller' => 'Collections',
	'action' => 'edit',
), array(
	'id' => '[0-9]+',
	'pass' => array('id'),
));

Router::connect('/collections/collections/delete/:id', array(
	'plugin' => 'Collections',
	'controller' => 'Collections',
	'action' => 'delete',
), array(
	'id' => '[0-9]+',
	'pass' => array('id'),
));

Router::connect('/collections/collections/editObject/:collection_id/:object_id', array(
	'plugin' => 'Collections',
	'controller' => 'Collections',
	'action' => 'editObject',
), array(
	'collection_id' => '([0-9]+)',
	'object_id' => '([0-9]+)',
	'pass' => array('collection_id', 'object_id'),
));
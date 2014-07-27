<?php

Router::parseExtensions();

Configure::write(
    'httpResourceMap',
    array(
	    array('action' => 'index', 'method' => 'GET', 'id' => false),
	    array('action' => 'view', 'method' => 'GET', 'id' => true),
	    array('action' => 'add', 'method' => 'POST', 'id' => false),
	    array('action' => 'edit', 'method' => 'PUT', 'id' => true),
	    array('action' => 'delete', 'method' => 'DELETE', 'id' => true),
	    array('action' => 'edit', 'method' => 'POST', 'id' => true)
	)
);

/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/View/Pages/home.ctp)...
 */
Router::connect('/', array('controller' => 'api'));

// Swagger specs
Router::connect('/swagger/api-docs', array('controller' => 'swagger', 'action' => 'api_docs'));
Router::connect('/swagger/api-docs/:slug', array('controller' => 'swagger', 'action' => 'resource_api_docs'), array('pass' => array('slug'), 'slug' => '[0-9a-zA-Z_]+'));
Router::connect('/swagger/:slug', array('controller' => 'swagger', 'action' => 'resource'), array('pass' => array('slug'), 'slug' => '[0-9a-zA-Z_]+'));

/**
 * ...and connect the rest of 'Pages' controller's URLs.
 */
Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));

Router::connect('/docs/:id', array('controller' => 'docs', 'action' => 'info'));
Router::connect('/docs/:id/html/:package', array('controller' => 'docs', 'action' => 'html'));

/**
 * Load all plugin routes. See the CakePlugin documentation on
 * how to customize the loading of plugin routes.
 */
// load plugin routes using ApiRoute routing
App::uses('ApiRoute', 'Routing/Route');
Router::defaultRouteClass('ApiRoute');

CakePlugin::routes();

/**
 * Load the CakePHP default routes. Only remove this if you do not want to use
 * the built-in default routes.
 */
require CAKE . 'Config' . DS . 'routes.php';

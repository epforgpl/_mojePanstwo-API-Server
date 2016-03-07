<?

Router::connect('/dane/objectFromSlug/:slug', array(
	'plugin' => 'Dane',
	'controller' => 'Dataobjects',
	'action' => 'objectFromSlug'
), array(
	'pass' => array('slug'), 
));

Router::mapResources('Dane.subscriptions', array('prefix' => '/dane/'));
Router::connect('/dane/subscriptions/transfer_anonymous', array(
	'plugin' => 'Dane', 
	'controller' => 'Subscriptions',
	'action' => 'transfer_anonymous'
));

Router::connect('/dane', array(
	'plugin' => 'Dane', 
	'controller' => 'Dataobjects',
	'action' => 'index'
));

// TODO namespace /dane/utils/ ?
Router::connect('/dane/suggest', array(
	'plugin' => 'Dane', 
	'controller' => 'Dataobjects',
	'action' => 'suggest',
));

Router::connect('/dane/:dataset', array(
	'plugin' => 'Dane',
	'controller' => 'Dataobjects',
	'action' => 'index'
), array(
	'dataset' => '[a-zA-Z_]+',
	'pass' => array('dataset'),
));

Router::connect('/dane/:dataset/:id', array(
	'plugin' => 'Dane', 
	'controller' => 'Dataobjects',
	'action' => 'post',
    '[method]' => 'POST'
), array(
	'id' => '[0-9]+',
	'pass' => array('dataset', 'id'),
));

Router::connect('/dane/zbiory/:id', array(
	'plugin' => 'Dane', 
	'controller' => 'Datasets',
	'action' => 'view'
), array(
	'id' => '[a-zA-Z_]+',
	'pass' => array('id'),
));

Router::connect('/dane/:dataset/:id', array(
	'plugin' => 'Dane', 
	'controller' => 'Dataobjects',
	'action' => 'view'
), array(
	'id' => '[0-9]+',
	'pass' => array('dataset', 'id'),
));

Router::connect('/dane/:dataset/feed', array(
	'plugin' => 'Dane', 
	'controller' => 'Dataobjects',
	'action' => 'feed',
), array(
	'id' => '[0-9]+',
	'pass' => array('dataset'),
));

Router::connect('/dane/:dataset/:id/:action', array(
	'plugin' => 'Dane', 
	'controller' => 'Dataobjects',
), array(
	'id' => '[0-9]+',
	'pass' => array('dataset', 'id'),
));

Router::connect('/dane/:dataset/:id/:layer', array(
	'plugin' => 'Dane',
	'controller' => 'Dataobjects',
	'action' => 'view_layer'
), array(
	'id' => '[0-9]+',
	'pass' => array('dataset', 'id', 'layer'),
));

# ObjectUsersManagement
Router::connect('/dane/:dataset/:object_id/users/index', array('plugin' => 'Dane', 'controller' => 'ObjectUsersManagement', 'action' => 'index', '[method]' => 'GET'), array('dataset' => '([a-zA-Z\_]+)', 'object_id' => '[0-9]+'));
Router::connect('/dane/:dataset/:object_id/users/index', array('plugin' => 'Dane', 'controller' => 'ObjectUsersManagement', 'action' => 'add', '[method]' => 'POST'), array('dataset' => '([a-zA-Z\_]+)', 'object_id' => '[0-9]+'));
Router::connect('/dane/:dataset/:object_id/users/:user_id', array('plugin' => 'Dane', 'controller' => 'ObjectUsersManagement', 'action' => 'edit', '[method]' => 'PUT'), array('dataset' => '([a-zA-Z\_]+)', 'object_id' => '[0-9]+', 'user_id' => '[0-9]+'));
Router::connect('/dane/:dataset/:object_id/users/:user_id', array('plugin' => 'Dane', 'controller' => 'ObjectUsersManagement', 'action' => 'delete', '[method]' => 'DELETE'), array('dataset' => '([a-zA-Z\_]+)', 'object_id' => '[0-9]+', 'user_id' => '[0-9]+'));

# ObjectPagesManagement
Router::connect('/dane/:dataset/:object_id/pages/isEditable', array('plugin' => 'Dane', 'controller' => 'ObjectPagesManagement', 'action' => 'isEditable', '[method]' => 'POST'), array('dataset' => '([a-zA-Z\_]+)', 'object_id' => '[0-9]+'));
Router::connect('/dane/:dataset/:object_id/pages/setLogo', array('plugin' => 'Dane', 'controller' => 'ObjectPagesManagement', 'action' => 'setLogo', '[method]' => 'POST'), array('dataset' => '([a-zA-Z\_]+)', 'object_id' => '[0-9]+'));
Router::connect('/dane/:dataset/:object_id/pages/setCover', array('plugin' => 'Dane', 'controller' => 'ObjectPagesManagement', 'action' => 'setCover', '[method]' => 'POST'), array('dataset' => '([a-zA-Z\_]+)', 'object_id' => '[0-9]+'));
Router::connect('/dane/:dataset/:object_id/pages/deleteLogo', array('plugin' => 'Dane', 'controller' => 'ObjectPagesManagement', 'action' => 'deleteLogo', '[method]' => 'DELETE'), array('dataset' => '([a-zA-Z\_]+)', 'object_id' => '[0-9]+'));
Router::connect('/dane/:dataset/:object_id/pages/deleteCover', array('plugin' => 'Dane', 'controller' => 'ObjectPagesManagement', 'action' => 'deleteCover', '[method]' => 'DELETE'), array('dataset' => '([a-zA-Z\_]+)', 'object_id' => '[0-9]+'));



Router::connect('/dane/:controller/:object_id/:action/:id', array('plugin' => 'Dane'), array('dataset' => '([a-zA-Z\_]+)', 'object_id' => '[0-9]+'));




<?

Router::connect('/finanse/:action', array('plugin' => 'Finanse', 'controller' => 'Finanse'));
Router::connect('/finanse/:action/:id', array('plugin' => 'Finanse', 'controller' => 'Finanse'));

Router::connect('/finanse/getCommunePopCount/:id', array(
    'plugin' => 'Finanse',
    'controller' => 'Finanse',
    'action' => 'getCommunePopCount'
), array(
    'id' => '([0-9]+)',
    'pass' => array('id')
));
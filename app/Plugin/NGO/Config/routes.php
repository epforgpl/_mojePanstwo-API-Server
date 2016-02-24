<?

Router::connect('/Ngo/Declarations', array('plugin' => 'NGO', 'controller' => 'Declarations', 'action' => 'add', '[method]' => 'POST',));

Router::connect('/ngo/newsletter', array('plugin' => 'NGO', 'controller' => 'Ngo', 'action' => 'newsletter', '[method]' => 'POST',));
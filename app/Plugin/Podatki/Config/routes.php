<?
Router::connect('/podatki/sendData', array('plugin' => 'Podatki', 'controller' => 'Podatki', 'action' => 'sendData',));
Router::connect('/podatki/stat', array('plugin' => 'Podatki', 'controller' => 'Podatki', 'action' => 'stat',));

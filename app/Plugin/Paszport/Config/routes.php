<?

Router::mapResources('users');
Router::connect('/paszport/info/*', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'info'));
Router::connect('/paszport/login', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'login'));
Router::connect('/paszport/register', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'register'));

Router::connect('/paszport/user/setIsNgo', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'setIsNgo'));
Router::connect('/paszport/user/setUserName', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'setUserName'));
Router::connect('/paszport/user/setEmail', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'setEmail'));
Router::connect('/paszport/user/setPassword', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'setUserPassword'));
Router::connect('/paszport/user/createNewPassword', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'createNewPassword'));
Router::connect('/paszport/user/deletePaszport', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'deletePaszport'));
Router::connect('/paszport/user/registerFromFacebook', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'registerFromFacebook'));
Router::connect('/paszport/user/forgot', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'forgot'));
Router::connect('/paszport/user/forgotToken', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'forgotToken'));
Router::connect('/paszport/user/forgotNewPassword', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'forgotNewPassword'));
Router::connect('/paszport/user/find', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'find'));
Router::connect('/paszport/user/canCreatePassword', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'canCreatePassword'));
Router::connect('/paszport/users/email', array('plugin' => 'paszport', 'controller' => 'Users', 'action' => 'getUsersByEmail', '[method]' => 'POST'));

Router::connect('/paszport/user/getObjects', array('plugin' => 'Paszport', 'controller' => 'Users', 'action' => 'getObjects', '[method]' => 'GET'));

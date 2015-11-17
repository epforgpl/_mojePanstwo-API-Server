<?

$base_slug = "/pisma";
foreach(array('', '_v0') as $version) {

    Router::connect("$base_slug$version/contacts", array('plugin' => 'Pisma', 'controller' => 'contacts', 'action' => 'search'));

    Router::connect("$base_slug$version/templates", array('plugin' => 'Pisma', 'controller' => 'templates', 'action' => 'index'));
    Router::connect("$base_slug$version/templates/:id", array('plugin' => 'Pisma', 'controller' => 'templates', 'action' => 'view'), array('id' => '[0-9]+'));



    Router::connect("$base_slug$version/transfer_anonymous", array('plugin' => 'Pisma', 'controller' => 'documents', 'action' => 'transfer_anonymous', '[method]' => 'GET'));



    Router::connect("$base_slug$version/documents", array('plugin' => 'Pisma', 'controller' => 'documents', 'action' => 'search', '[method]' => 'GET'));
    Router::connect("$base_slug$version/documents/search", array('plugin' => 'Pisma', 'controller' => 'documents', 'action' => 'search', '[method]' => 'GET'));
    
    Router::connect("$base_slug$version/documents", array('plugin' => 'Pisma', 'controller' => 'documents', 'action' => 'save', '[method]' => 'POST'));
    
    Router::connect("$base_slug$version/documents", array('plugin' => 'Pisma', 'controller' => 'documents', 'action' => 'delete', '[method]' => 'DELETE'));
    
    Router::connect("$base_slug$version/documents/:id", array('plugin' => 'Pisma', 'controller' => 'documents', 'action' => 'view', '[method]' => 'GET'), array('id' => '[A-Za-z0-9]{5}'));
    Router::connect("$base_slug$version/documents/:id", array('plugin' => 'Pisma', 'controller' => 'documents', 'action' => 'save', '[method]' => 'POST'), array('id' => '[A-Za-z0-9]{5}', 'pass' => array('id')));
    Router::connect("$base_slug$version/documents/:id", array('plugin' => 'Pisma', 'controller' => 'documents', 'action' => 'update', '[method]' => 'PUT'), array('id' => '[A-Za-z0-9]{5}', 'pass' => array('id')));
    Router::connect("$base_slug$version/documents/:id", array('plugin' => 'Pisma', 'controller' => 'documents', 'action' => 'delete', '[method]' => 'DELETE'), array('id' => '[A-Za-z0-9]{5}', 'pass' => array('id')));
        
    Router::connect("$base_slug$version/documents/:id/send", array('plugin' => 'Pisma', 'controller' => 'documents', 'action' => 'send', '[method]' => 'POST'), array('id' => '[A-Za-z0-9]{5}', 'pass' => array('id')));


    Router::connect("$base_slug$version/:letter_id/responses", array('plugin' => 'Pisma', 'controller' => 'Responses', 'action' => 'save', '[method]' => 'POST'), array('letter_id' => '[A-Za-z0-9]{5}', 'pass' => array('letter_id')));
    Router::connect("$base_slug$version/:letter_id/responses", array('plugin' => 'Pisma', 'controller' => 'Responses', 'action' => 'getByLetter', '[method]' => 'GET'), array('letter_id' => '[A-Za-z0-9]{5}', 'pass' => array('letter_id')));
    Router::connect("$base_slug$version/:letter_id/responses/:response_id", array('plugin' => 'Pisma', 'controller' => 'Responses', 'action' => 'get', '[method]' => 'GET'), array('letter_id' => '[A-Za-z0-9]{5}', 'response_id' => '[0-9]{1,}', 'pass' => array('letter_id', 'response_id')));
    Router::connect("$base_slug$version/getAttachmentURL/:attachment_id", array('plugin' => 'Pisma', 'controller' => 'Responses', 'action' => 'getAttachmentURL', '[method]' => 'GET'), array('attachment_id' => '[0-9]{1,}', 'pass' => array('attachment_id')));

}


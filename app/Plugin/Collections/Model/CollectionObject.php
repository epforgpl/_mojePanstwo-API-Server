<?php

class CollectionObject extends AppModel {

    public $useTable = 'collection_object';

    public $validate = array(
        'collection_id' => array(
            'rule' => 'notEmpty',
            'required' => true
        ),
        'object_id' => array(
            'rule' => 'notEmpty',
            'required' => true
        )
    );
    
    public function afterSave($created, $options) {
	    
	    $this->syncByData($this->data);
	    
    }
    
    public function syncAll() {
	    
	    $ids = $this->find('all', array(
		    'fields' => array('id'),
	    ));
	    
	    foreach( $ids as $id ) {
		    		    
		    $id = $id['CollectionObject']['id'];
		    $this->syncById($id);
		    
	    }
	    	    
    }
    
    public function syncById($id) {
	    
	    $data = $this->find('first', array(
		    'conditions' => array(
			    'id' => $id,
		    ),
	    ));
	    
	    $this->syncByData( $data );
	    
    }
    
    public function syncByData($data) {
	    
	    if( 
	    	empty($data) || 
	    	!isset($data['CollectionObject'])
	    )
	    	return false;
	    		    
	    $data = $data['CollectionObject'];

	    $ES = ConnectionManager::getDataSource('MPSearch');	    
	   	    
	    $params = array();
		$params['index'] = 'mojepanstwo_v1';
		$params['type']  = 'collections-objects';
		$params['id']    = $data['id'];
		$params['parent'] = $data['object_id'];
		$params['refresh'] = true;
		$params['body']  = array(
			'collection_id' => $data['collection_id'],
		);		
		
		$ret = $ES->API->index($params);		
		$this->Collection = ClassRegistry::init('Collections.Collection');	
		$this->Collection->syncById($data['collection_id']);
		return $data['id'];	    
	    
    }

}
<?php

App::uses('AppModel', 'Model');

class BdlPodgrupy extends AppModel {

    public $useTable = 'BDL_podgrupy';

    private $request;


    public function opis($data, $id = false) {
	    
	    var_export( $data ); die();
	    	    
        if(!$id)
            return false;

        $this->read(null, $id);
        $this->set(array(
            'opis' => $data['opis'],
            'nazwa' => $data['tytul']
        ));
        $this->save();

        return true;
    }
    
    public function wymiar($data, $id = false) {
	    
	    if(!$id)
	    	return false;
	    	
	    $this->save(array(
		    'i' => $data['i'],
		    'id' => $id,
	    ));
	    
	    $this->objectIndex(array(
		    'dataset' => 'bdl_wskazniki',
		    'object_id' => $id,
	    ));
	    
	    return true;
	    
    }

}
<?php

class Podatki extends AppModel
{

    public $useTable = false;
	
	public function sendData($data) {
		
		App::import('model','DB');
        $db = new DB();
        
        $db->q("INSERT INTO `podatki_dane` (`data`) VALUES ('" . addslashes( json_encode( $data ) ) . "')");
		
		return true;
			
	}
	
} 
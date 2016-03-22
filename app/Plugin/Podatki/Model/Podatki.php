<?php

class Podatki extends AppModel
{

    public $useTable = false;
	
	public function stat($data) {
		
		App::import('model','DB');
        $db = new DB();
        
        $db->q("INSERT IGNORE INTO `podatki_stats` (`action`, `dzial_id`) VALUES ('" . addslashes($data['action']) . "', '" . addslashes($data['id']) . "')");
		
		return true;
			
	}
	
	public function sendData($data) {
		
		App::import('model','DB');
        $db = new DB();
        
        $db->q("INSERT INTO `podatki_dane` (`data`) VALUES ('" . addslashes( json_encode( $data ) ) . "')");
		
		return true;
			
	}
	
} 
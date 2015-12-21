<?php

class Portal extends AppModel {

    public $useTable = false;

    public function verifyKey($object_id, $key) {
        
        App::import('model','DB');
        $db = new DB();
        
        $res = $db->selectRow("SELECT COUNT(*) FROM `pl_gminy_radni_krakow` WHERE `id`='" . addslashes( $object_id ) . "' AND `editKey`='" . addslashes( $key ) . "' LIMIT 1");
        
        return (boolean) ( $res && isset($res[0]) && ($res[0]=='1') );
        
    }
    
    public function savePromises($object_id, $items) {
        
        App::import('model','DB');
        $db = new DB();
        
        foreach( $items as $item ) {
	        
	        $q = "UPDATE `pl_gminy_radni_krakow_obietnice` SET `odpowiedz`='" . addslashes( $item['content'] ) . "' WHERE `id`='" . addslashes( $item['id'] ) . "' AND `radny_id`='" . addslashes( $object_id ) . "' LIMIT 1";
	        $db->query($q);
	        
        }
        
        return true;
        
    }


}
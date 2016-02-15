<?php

App::uses('AppModel', 'Model');
class BDL extends AppModel {

    public $useTable = false;
    
    public function getTree(){
	    
	    App::import('model', 'DB');
        $this->DB = new DB();
	    
	    $data = $this->DB->selectAssocs("SELECT 
			`BDL_kategorie`.`id` as 'kategoria.id', 
			`BDL_kategorie`.`tytul` as 'kategoria.tytul',
			`BDL_grupy`.`id` as 'grupa.id', 
			`BDL_grupy`.`tytul` as 'grupa.tytul',
			`BDL_podgrupy`.`id` as 'podgrupa.id', 
			`BDL_podgrupy`.`tytul` as 'podgrupa.tytul' 
			FROM `BDL_kategorie` 
			JOIN `BDL_grupy` ON `BDL_grupy`.`kat_id` = `BDL_kategorie`.`id` 
			JOIN `BDL_podgrupy` ON `BDL_podgrupy`.`grupa_id` = `BDL_grupy`.`id` AND `BDL_podgrupy`.`akcept` = '1'
			WHERE `BDL_kategorie`.`okres` = 'R'");
	    
	    $kategorie = array();
	    
	    foreach( $data as $d ) {
		    
		    $kategorie[ $d['kategoria.id'] ]['dane']['tytul'] = $d['kategoria.tytul'];
		    $kategorie[ $d['kategoria.id'] ]['grupy'][ $d['grupa.id'] ]['dane']['tytul'] = $d['grupa.tytul'];
		    $kategorie[ $d['kategoria.id'] ]['grupy'][ $d['grupa.id'] ]['podgrupy'][ $d['podgrupa.id'] ]['dane']['tytul'] = $d['podgrupa.tytul'];
		    
	    }
	    	    
	    return array(
		    'kategorie' => $kategorie,
	    );
	    
    }
    
    public function getLocalData($id, $level, $page = 1)
    {
	    
	    $data = array();
	    	    
	    if( $id && $level && in_array($level, array('wojewodztwa', 'powiaty', 'gminy')) )
	    {
		    
		    App::import('model', 'DB');
	        $this->DB = new DB();
		    
		    
		    
		    $limit = 20;
 			$offset = ($page - 1) * $limit;
		    
		    
		    if( $level == 'wojewodztwa' )
		    {
			    $data_table = 'BDL_data_wojewodztwa';
				$data_table_field = 'wojewodztwo_id';
				$units_table = 'wojewodztwa';
		    }
		    elseif( $level == 'powiaty' )
		    {
			    
			    $data_table = 'BDL_data_powiaty';
				$data_table_field = 'powiat_id';
				$units_table = 'pl_powiaty';
			    
		    }
		    elseif( $level == 'gminy' )
		    {
			    
			    $data_table = 'BDL_data_gminy';
				$data_table_field = 'gmina_id';
				$units_table = 'pl_gminy';
			    
		    }
		 		    
		    		    
					
		
			
			$q_order = '1';
			/*
			if( isset($params['us']) ) {
				if( $params['us']=='vd' ) $q_order = "`$data_table`.`v` DESC";
				elseif( $params['us']=='va' ) $q_order = "`$data_table`.`v` ASC";
				elseif( $params['us']=='na' ) $q_order = "`$units_table`.`nazwa` ASC";		
				elseif( $params['us']=='nd' ) $q_order = "`$units_table`.`nazwa` DESC";		
			}
			*/
			
			$q_q = '1';
			/*
			if( isset($params['uq']) && $params['uq'] )
				$q_q = "`$units_table`.`nazwa` LIKE '" . addslashes( $params['uq'] ) . "'";
			*/
			
			$q = "SELECT `$data_table`.`$data_table_field`, `$units_table`.`nazwa`, GROUP_CONCAT(CONCAT(`$data_table`.`rocznik`, \"\t\", `$data_table`.`v`, \"\t\", `$data_table`.`a`) ORDER BY `$data_table`.`rocznik` ASC SEPARATOR \"\n\") as 'data' FROM `$data_table` JOIN `$units_table` ON `$data_table`.`$data_table_field` = `$units_table`.`id` WHERE `$data_table`.`kombinacja_id`='$id' AND `$data_table`.`zero`='0' AND $q_q GROUP BY `$data_table`.`$data_table_field` ORDER BY $q_order LIMIT 3000";
						
			$data = $this->DB->query($q);
			foreach( $data as &$d ) {
				
				$parts = explode("\n", $d[0]['data']);
				foreach( $parts as &$part ) {
					$p = explode("\t", $part);
					$part = array(
						'rocznik' => $p[0],
						'v' => $p[1],
						'a' => $p[2],
					);
				}
				
				
				$d = array(
					'dim_id' => $id,
					'local_id' => $d[ $data_table ][ $data_table_field ],
					'local_name' => $d[$units_table]['nazwa'],
					// 'data' => $parts,
				);	
				
				if( $part )
					$d = array_merge($d, array(
						'lv' => (float) $parts[ count($parts)-1 ]['v'],
						'ly' => (int) $parts[ count($parts)-1 ]['rocznik'],
					));
				
			}

	
		    
	    } else return false;
	    
		return $data;
	    
    }
    
}
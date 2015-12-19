<?php

class BDLLegacyController extends AppController
{
    
	public function getLocalDataForDimension()
	{
		$types = array(
			'wojewodztwa'	=> array('BDL_data_wojewodztwa', 'wojewodztwo_id'),
			'powiaty'	=> array('BDL_data_powiaty', 'powiat_id'),
			'gminy'		=> array('BDL_data_gminy', 'gmina_id')
		);

		$type = isset($this->request->query['localtype']) ? $this->request->query['localtype'] : 'wojewodztwa';
		$type_id = isset($this->request->query['localid']) ? (int) $this->request->query['localid'] : 0;
		$dim_id = isset($this->request->query['dimid']) ? (int) $this->request->query['dimid'] : 0;

		if(!isset($types[$type]))
			throw new Exception("Undefined localtype param");

		App::import('model', 'DB');
        	$this->DB = new DB();

		$type = $types[$type];
		
		
		$query = "SELECT `rocznik` as 'y', `v` FROM `".$type[0]."` WHERE `".$type[1]."` = $type_id AND `kombinacja_id` = $dim_id";		
		$bdl = $this->DB->selectAssocs($query);

		$data = $bdl;

		/**
			Skrypt js spodziewa się danych typu 
			$data = array(
				array(
					'y' => 2015,	// rok
					'v' => 1000	// wartość	
				),
			);
			
			Na podstawie:
			$dim_id = $this->request->query['dim_id'];		// kombinacja
			$localtype = $this->request->query['localtype'];	// typ danych (woj, pow, gmin)
			$localid = $this->request->query['localid'];		// id typu np. wojewodztwa

		**/
		//$data = $this->request->query;
		$this->set('data', $data);
		$this->set('_serialize', array('data'));
	}

    public function dataForDimmesions()
    {
			
        $data = array();
        $dims = isset( $this->request->query['dims'] ) ? $this->request->query['dims'] : array();
        $podgrupa_id = isset( $this->request->query['podgrupa_id'] ) ? $this->request->query['podgrupa_id'] : 0;
        
        App::import('model', 'DB');
        $this->DB = new DB();
                
        foreach( $dims as $dim )
        {
	        
	        $db_params = array();
	        for( $i=0; $i<5; $i++ )
	        	$db_params[ $i ] = isset( $dim[ $i ] ) ? $dim[ $i ] : 0;
	        
	        $q = "SELECT id, jednostka, ly, lv, ply, dv FROM `BDL_wymiary_kombinacje` WHERE podgrupa_id='$podgrupa_id' AND `w1` = '" . addslashes( $db_params[0] ) . "' AND `w2` = '" . addslashes( $db_params[1] ) . "' AND `w3` = '" . addslashes( $db_params[2] ) . "' AND `w4` = '" . addslashes( $db_params[3] ) . "' AND `w5` = '" . addslashes( $db_params[4] ) . "' LIMIT 1";
	        
	        // echo "\n" . $q;
	        $db_data = $this->DB->selectAssoc($q);
	        
	        
	        if( !empty($db_data) )	        	        	
		        $data[] = array_merge($db_data, array(
		        	'dim_str' => implode(',', $db_params),
		        ));
	        		        
        }
        
        $this->set('data', $data);
        $this->set('_serialize', array('data'));

    }
    
    public function dataForDimmesion()
    {
	    
	    $data = array();
	    
	    App::import('model', 'DB');
        $this->DB = new DB();
	    	    	    
	    $dim_id = isset( $this->request->params['dim_id'] ) ? $this->request->params['dim_id'] : false;
	    if( $dim_id )
	    {
		    
		    $db_data = $this->DB->selectAssoc("SELECT id, podgrupa_id, jednostka, ly, lv, ply, dv, `w1`, `w2`, `w3`, `w4`, `w5` FROM `BDL_wymiary_kombinacje` WHERE id='" . addslashes( $dim_id ) . "' LIMIT 1");
		    if( $db_data )
		    {
		    	$data = array(
		    		'id' => $db_data['id'],
		    		'jednostka' => $db_data['jednostka'],
		    		'ly' => $db_data['ly'],
		    		'lv' => $db_data['lv'],
		    		'ply' => $db_data['ply'],
		    		'dv' => $db_data['dv'],
		    		'dim_str' => $db_data['w1'] . ',' . $db_data['w2'] . ',' . $db_data['w3'] . ',' . $db_data['w4'] . ',' . $db_data['w5'],
		    		'levels' => $this->DB->selectAssocs("SELECT `BDL_ntss`.`tab_id` as 'id', `BDL_ntss`.`tab_name` as 'label' FROM `BDL_podgrupy-ntss` JOIN `BDL_ntss` ON `BDL_podgrupy-ntss`.`nts_id` = `BDL_ntss`.`id` WHERE `BDL_podgrupy-ntss`.`podgrupa_id`='" . $db_data['podgrupa_id'] . "' AND `BDL_podgrupy-ntss`.`deleted`='0' AND `BDL_ntss`.`tab_id`!='' GROUP BY `BDL_ntss`.`tab_id` ORDER BY `BDL_ntss`.`id`"),
		    	);
								
		    }
		    
	    }
	    
	    $this->set('data', $data);
	    $this->set('_serialize', array('data'));
	    
    }
    
    public function chartDataForDimmesions()
    {
		
		
        $data = array();
        $dim_ids = isset( $this->request->query['dims'] ) ? $this->request->query['dims'] : array();
                
        App::import('model', 'DB');
        $this->DB = new DB();
        
        
        $q = "SELECT `kombinacja_id` as 'dim_id', `rocznik` as 'y', `v`, `a` FROM `BDL_data_pl` WHERE (`kombinacja_id`='" . implode("' OR `kombinacja_id`='", $dim_ids) . "') AND `deleted`='0' AND `zero`='0' ORDER BY kombinacja_id ASC, rocznik ASC";
        // echo $q;
        $db_data = $this->DB->selectAssocs($q);
        
                
        $temp = array();
        foreach( $db_data as $d )
		{
			$dim_id = $d['dim_id'];
			unset( $d['dim_id'] );
			$temp[ $dim_id ][] = $d;
		}
        
        if( !empty($temp) )
        	foreach( $temp as $dim_id => $dims_data )
        		$data[] = array(
        			'id' => $dim_id,
        			'data' => $dims_data,
        		);        
        
        
        
        $this->set('data', $data);
        $this->set('_serialize', array('data'));

    }
    
    public function localDataForDimension()
    {
	    
	    $data = array();
	    
	    $dim_id = $this->request->params['dim_id'];
	    $level = $this->request->query['level'];
	    	    
	    if( $dim_id && $level && in_array($level, array('wojewodztwa', 'powiaty', 'gminy')) )
	    {
		    
		    App::import('model', 'DB');
	        $this->DB = new DB();
		    
		    
		    
		    $limit = 20;
			$page = isset( $this->request->query['page'] ) ? 
				(int) $this->request->query['page'] : 
				1;
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
			
			$q = "SELECT `$data_table`.`$data_table_field`, `$units_table`.`nazwa`, GROUP_CONCAT(CONCAT(`$data_table`.`rocznik`, \"\t\", `$data_table`.`v`, \"\t\", `$data_table`.`a`) ORDER BY `$data_table`.`rocznik` ASC SEPARATOR \"\n\") as 'data' FROM `$data_table` JOIN `$units_table` ON `$data_table`.`$data_table_field` = `$units_table`.`id` WHERE `$data_table`.`kombinacja_id`='$dim_id' AND `$data_table`.`deleted`='0' AND `$data_table`.`zero`='0' AND $q_q GROUP BY `$data_table`.`$data_table_field` ORDER BY $q_order LIMIT 3000";
			
						
			
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
					'dim_id' => $dim_id,
					'local_id' => $d[ $data_table ][ $data_table_field ],
					'local_name' => $d[$units_table]['nazwa'],
					// 'data' => $parts,
				);	
				
				if( $part )
					$d = array_merge($d, array(
						'lv' => $parts[ count($parts)-1 ]['v'],
						'ly' => $parts[ count($parts)-1 ]['rocznik'],
					));
				
			}
		    
		    
		    
		        
		    
	
		    
	    } else return false;
	    
	    
	    
	    
	    
	    
	    
	    
		
			
			
			
	    
	    
	    
	    
	    
	    
	    
	    
	    
	    
	    
	    $this->set('data', $data);
        $this->set('_serialize', array('data'));
	    
    }
    
}

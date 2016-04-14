<?php

class KulturaController extends AppController
{
		
	public function data()
    {
				
		if( $id = $this->request->params['id'] ) {
			
			$id = (int) $id;
			
			App::import('model','DB');
	        $db = new DB();
	        
	        $params = array(
		        'sex' => null,
		        'area' => null,
		        'region' => null,
		        'city_size' => null,
		        'household' => null,
		        'education' => null,
		        'age' => null,
	        );
	        
    	    $fields = array('sex', 'age', 'education', 'region', 'city_size', 'household');
			foreach( $fields as $f )
				if( isset($this->request->query[ $f ]) )
					$params[ $f ] = $this->request->query[ $f ];
	        
	        $db_params = array(
		        "`file_id`='$id'",
	        );
	        foreach( $params as $k => $v ) {
		        
		        if( is_null($v) )
			        $db_params[] = '`' . $k . '` IS NULL';
			    else
			        $db_params[] = '`' . $k . '`= \'' . $v . '\'';
			    		        		        
	        }
	        
	        $db_data = $db->selectAssocs("SELECT `caption_id`, `value` FROM `culture_data` WHERE (" . implode(") AND (", $db_params) . ")");
	        $captions = array();
	        
	        foreach( $db_data as $d )
	        	if( !is_null($d['value']) )
		        	$captions[ $d['caption_id'] ] = (float) $d['value'];
	        
	        $data = array(
		        'captions' => $captions,
	        );
	        			
		} else {
			$data = false;
		}
						
        $this->set('data', $data);
        $this->set('_serialize', 'data');
        
    }
    
}

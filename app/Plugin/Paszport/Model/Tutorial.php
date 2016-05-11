<?php

class Tutorial extends PaszportAppModel {

    public $useTable = false;
    
    public $defaultTutorials = array(
	    array(
		    'id' => 1,
		    'slug' => 'obserwuj',
	    ),
	    array(
		    'id' => 2,
		    'slug' => 'ngo_finanse',
	    ),
	    array(
		    'id' => 3,
		    'slug' => 'konkursy_granty',
	    ),
	    array(
		    'id' => 4,
		    'slug' => 'pisma_dostep_do_informacji',
	    ),
    );

	public function index($user_id) {
		
		$output = $this->defaultTutorials;
		
		App::import('model', 'DB');
        $DB = new DB();
        
        $user_data = $DB->selectAssocs("SELECT tutorial_id, completed FROM tutorials_users WHERE user_id='" . addslashes( $user_id ) . "'");
        
        for( $i=0; $i<count($output); $i++ ) {
	        
	        $d = false;
	        
	        for( $j=0; $j<count($user_data); $j++ ) {
		        if( $output[$i]['id'] == $user_data[$j]['tutorial_id'] ) {
			        
			        $d = $user_data[$j];
			        break;
			        
		        }
	        }
	        
	        $output[$i]['completed'] = $d ? (boolean) $d['completed'] : false;
	        
        }  
		
		return $output;
		
	}
	
	public function edit($user_id, $tutorial_id, $data) {
				
		if(
			$user_id && 
			$tutorial_id && 
			$data && 
			array_key_exists('completed', $data)
		) {
			
			App::import('model', 'DB');
	        $DB = new DB();
	        
	        $params = array(
		        'tutorial_id' => $tutorial_id,
		        'user_id' => $user_id,
		        'completed' => ($data['completed'] && ($data['completed']!=='false')) ? '1' : '0',
	        );
	        	        
	        $DB->insertUpdateAssoc('tutorials_users', $params);
			return true;
			
		} else {
			
			return false;
			
		}
		
	}

}
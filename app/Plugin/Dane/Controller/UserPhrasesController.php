<?
		
	class UserPhrasesController extends AppController {
	
	    public $components = array('RequestHandler');
	
	    public function register() {
	        
	        if( $q = trim(@$this->request->data['q']) ) {
		        
		        $db_phrase = $this->UserPhrase->find('first', array(
			        'conditions' => array(
				        'q' => $q,
			        ),
		        ));
		        
		        if( $db_phrase ) {
			        
			        $id = $db_phrase['UserPhrase']['id'];
			        
		        } else {
			        
			        $this->UserPhrase->clear();
			        $this->UserPhrase->save(array(
				        'q' => $q,
			        ), false, array(
				        'q'
			        ));
			        
			        $id = $this->UserPhrase->getId();
			        
		        }
		        
	        } else {
	        
		        $id = false;
	        
	        }
	        
	        $this->set('id', (int) $id);
	        $this->set('_serialize', 'id');
	        
	    }
	    
	}
	
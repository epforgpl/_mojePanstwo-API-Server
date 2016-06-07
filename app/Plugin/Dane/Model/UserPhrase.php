<?

class UserPhrase extends AppModel
{
    
    private $dataset_id = 236;
    private $dataset = 'users_phrases';
    
    private function getGlobalId($phrase_id) {
	    	    	    
	    if( $data = $this->query("SELECT `id` FROM `objects` WHERE `dataset_id`='" . $this->dataset_id . "' AND `object_id`='" . $phrase_id . "' LIMIT 1") ) {
		    
		    return $data[0]['objects']['id'];
		    
	    } else return false;
	    
    }
    
    private function insertGlobalId($phrase_id) {
	    
	    App::import('model', 'DB');
        $db = new DB();
	    $db->insertIgnoreAssoc('objects', array(
		    'dataset_id' => $this->dataset_id,
		    'dataset' => $this->dataset,
		    'object_id' => $phrase_id,
		    'tag' => '0',
		    'a' => '1',
	    ));
	    return $db->_getInsertId();
	    	    
    }
    
	public function afterSave($created) {
		if( $created ) {
			
			$global_id = $this->getGlobalId($this->data['UserPhrase']['id']);
			if( !$global_id )
				$global_id = $this->insertGlobalId($this->data['UserPhrase']['id']);				
				
			
			$ES = ConnectionManager::getDataSource('MPSearch');
						
			$params = array(
				'index' => 'mojepanstwo_v1',
				'type' => 'objects',
				'id' => $global_id,
				'refresh' => true,
				'body' => array(
					'id' => $this->data['UserPhrase']['id'],
					'dataset' => 'users_phrases',
					'slug' => Inflector::slug( $this->data['UserPhrase']['q'] ),
					'title' => $this->data['UserPhrase']['q'],
					'data' => array (
						'users_phrases' => array (
							'id' => $this->data['UserPhrase']['id'],
							'q' => $this->data['UserPhrase']['q'],
						),
					),
					'text' => $this->data['UserPhrase']['q'],
					'weights' => array (
						'main' => array (
							'score' => 1,
							'enabled' => false,
						),
					),
				),
			);
			
			$ret = $ES->API->index($params);
			
		}
	}

}
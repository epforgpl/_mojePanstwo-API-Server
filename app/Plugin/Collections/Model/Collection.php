<?php

class Collection extends AppModel {

    public $useTable = 'collections';
	public $global_id = 0;
	public $collection_id = 0;

    public $validate = array(
        'name' => array(
            'rule' => array('minLength', '3'),
            'required' => true,
            'message' => 'Nazwa kolekcji musi zawierać przynajmniej 3 znaki'
        ),
        'user_id' => array(
            'rule' => 'notEmpty',
            'required' => true
        ),
        'description' => array(
            'rule' => array('maxLength', '16383'),
            'required' => false
        ),
    );
	
	public function load($id, $user_id) {
		
		$ES = ConnectionManager::getDataSource('MPSearch');
		$objects = $this->query("
			SELECT
				`objects`.`id`
			FROM
				`objects-users`
			JOIN
				`objects` ON
					`objects`.`dataset` = `objects-users`.`dataset` AND
					`objects`.`object_id` = `objects-users`.`object_id`
			WHERE
				`objects-users`.`user_id` = ". $user_id ." AND
				`objects-users`.`role` > 0
		");

		$ret = $ES->API->search(array(
			'index' => 'mojepanstwo_v1',
			'type' => 'collections',
			'body' => array(
				'query' => array(
					'bool' => array(
						'must' => array(
							array(
								'term' => array(
									'id' => $id,
								),
							),
							array(
								'term' => array(
									'user_id' => $user_id,
								),
							),
						),
					),
				),
			),
		));
		
		return isset( $ret['hits']['hits'][0] ) ? $ret['hits']['hits'][0] : false;
		
	}
	
	public function publish($id) {
		return $this->syncById($id, true);
	}

	public function unpublish($id) {
		return $this->syncById($id);
	}

	public function syncAll($public = false, $checkPermissions = true) {
		foreach(
			$this->query("SELECT id FROM `collections`")
			as $obj
		)
			$this->syncById($obj['collections']['id'], $public, $checkPermissions);
	}

	public function beforeSave($options = array()) {
		if(isset($this->data['Collection']['user_id'])) {
			$res = $this->query("SELECT username FROM `users` WHERE `id` = '" . $this->data['Collection']['user_id'] . "'");
			$this->data['Collection']['user_username'] = (@$res[0]['users']['username']);
		}
	}
    
    public function afterSave($created, $options) {

        if(isset($this->data['Collection']['id'])) {
            $this->syncById($this->data['Collection']['id']);
        }
	    
    }

	public function afterDelete() {
		$ES = ConnectionManager::getDataSource('MPSearch');
		if($this->collection_id) {
			$ES->API->delete(array(
				'index' => 'mojepanstwo_v1',
				'type' => 'collections',
				'id' => $this->collection_id,
				'refresh' => true,
				'ignore' => 404
			));
			$this->collection_id = 0;
		}

		if($this->global_id) {
			$ES->API->delete(array(
				'index' => 'mojepanstwo_v1',
				'type' => 'objects',
				'id' => $this->global_id,
				'refresh' => true,
				'ignore' => 404
			));
			$this->global_id = 0;
		}
	}
    
    public function syncById($id, $public = false, $checkPermissions = true) {
	    
	    if( !$id )
	    	return false;
	    
	    $data = $this->find('first', array(
		    'conditions' => array(
			    'Collection.id' => $id,
		    ),
	    ));
	    
	    if( $data ) {
		    
	    	return $this->syncByData( $data , $public, $checkPermissions);
	    
	    } else
	    	return false;
	    
    }
    
    public function syncByData($data, $public = false, $checkPermissions = true) {
	    	        
	    if( 
	    	empty($data) || 
	    	!isset($data['Collection'])
	    )
	    	return false;
        
        $data = $data['Collection'];

		$public = (
			$public ||
			(
				isset($data['is_public']) &&
				$data['is_public'] == '1'
			)
		);

		$res = $this->query("SELECT COUNT(*) FROM `collection_object` WHERE `collection_id`='" . $data['id'] . "'");
		$data['items_count'] = (int) (@$res[0][0]['COUNT(*)']);

		if( isset($data['object_id']) && $data['object_id'] ) {
			$r = $this->query("
					SELECT
						objects.dataset, objects.object_id, objects.slug
					FROM
						`objects-users`
					INNER JOIN
						`objects` ON
							`objects`.`dataset` = `objects-users`.`dataset` AND
							`objects`.`object_id` = `objects-users`.`object_id`
					WHERE
						`objects-users`.`user_id` = ". $data['user_id'] ." AND
						`objects-users`.`role` > 0 AND
						`objects`.`id` = ". addslashes($data['object_id']) ."
				");

			if( $checkPermissions && empty($r) )
				throw new ForbiddenException;

			if( $r[0]['objects']['dataset']=='krs_podmioty' ) {
				$t = $this->query("SELECT nazwa FROM krs_pozycje WHERE `id`='" . $r[0]['objects']['object_id'] . "'");
				$data['page_name'] = $t[0]['krs_pozycje']['nazwa'];
			}

			$data['page_dataset'] = $r[0]['objects']['dataset'];
			$data['page_object_id'] = $r[0]['objects']['object_id'];
			$data['page_slug'] = $r[0]['objects']['slug'];
		}

		$ES = ConnectionManager::getDataSource('MPSearch');
		$params = array();
		$params['index'] = 'mojepanstwo_v1';
		$params['type']  = 'collections';
		$params['id']    = $data['id'];
		$params['refresh'] = true;
		$params['body']  = array(
			'title' => $data['name'],
			'text' => $data['name'],
			'dataset' => 'kolekcje',
			'slug' => Inflector::slug($data['name']),
			'date' => date('Y-m-d\TH:i:s\Z', strtotime($data['created_at'])),
			'id' => $data['id'],
			'nazwa' => $data['name'],
			'description' => $data['description'],
			'user_id' => $data['user_id'],
			'user_username' => $data['user_username'],
			'is_public' => $data['is_public'],
			'object_id' => $data['object_id'],
			'items_count' => $data['items_count'],
			'page_name' => isset($data['page_name']) ? $data['page_name'] : '',
			'page_dataset' => isset($data['page_dataset']) ? $data['page_dataset'] : '',
			'page_object_id' => isset($data['page_object_id']) ? $data['page_object_id'] : '',
			'page_slug' => isset($data['page_slug']) ? $data['page_slug'] : '',
		);

		$ES->API->index($params);

		$res = $this->query("SELECT id FROM objects WHERE `dataset_id`='210' AND `object_id`='" . addslashes( $data['id'] ) . "' LIMIT 1");
		$global_id = (int)(@$res[0]['objects']['id']);

		if($public) {

			if(!$global_id) {
				$this->query("INSERT INTO `objects` (`dataset`, `dataset_id`, `object_id`) VALUES ('kolekcje', 210, ".$data['id'].")");
				$res = $this->query('select last_insert_id() as id;');
				$global_id = $res[0][0]['id'];
			}

			foreach(array('date', 'nazwa', 'description', 'user_id', 'user_username', 'is_public', 'object_id', 'items_count') as $f)
				unset($params['body'][$f]);

			$params['id'] = $global_id;
			$params['body']['data'] = array(
				'kolekcje' => array(
					'czas_utworzenia' => $data['created_at'],
					'id' => $data['id'],
					'nazwa' => $data['name'],
					'notatka' => $data['description'],
					'user_id' => $data['user_id'],
					'user_username' => $data['user_username'],
					'is_public' => $data['is_public'],
					'is_promoted' => $data['is_promoted'],
					'object_id' => $data['object_id'],
					'items_count' => $data['items_count'],
					'page_name' => isset($data['page_name']) ? $data['page_name'] : '',
					'page_dataset' => isset($data['page_dataset']) ? $data['page_dataset'] : '',
					'page_object_id' => isset($data['page_object_id']) ? $data['page_object_id'] : '',
					'page_slug' => isset($data['page_slug']) ? $data['page_slug'] : '',
				)
			);
			$params['type'] = 'objects';
			$ES->API->index($params);

		} elseif($global_id) {

			$deleteParams = array();
			$deleteParams['index'] = 'mojepanstwo_v1';
			$deleteParams['type'] = 'objects';
			$deleteParams['id'] = $global_id;
			$deleteParams['refresh'] = true;
			$deleteParams['ignore'] = array(404);
			$ES->API->delete($deleteParams);

		}

		return $data['id'];
    }
    
    public function editObject($collection_id, $object_id, $data) {
	    
	    $response = false;
	    
	    if( isset($data['note']) ) {
		    
		    if( $_id = $this->query("SELECT id FROM `collection_object` WHERE `collection_id`='" . addslashes($collection_id) . "' AND `object_id`='" . addslashes($object_id) . "' LIMIT 1") ) {
			    
			    $_id = $_id[0]['collection_object']['id'];
			    
			    $this->query("UPDATE `collection_object` SET `note`='" . addslashes( $data['note'] ) . "' WHERE `id`='$_id'");
			    
			    $ES = ConnectionManager::getDataSource('MPSearch');
			    $params = array(
				    'index' => 'mojepanstwo_v1',
				    'type'=> 'collections-objects',
				    'id' => $_id,
				    'parent' => $object_id,
				    'refresh' => true,
				    'body' => array(
					    'doc' => array(
						    'note' => $data['note'],
					    ),
				    ),
			    );
			    
			    return $ES->API->update($params);
			    
		    }
		    
	    }
	    
	    return $response;
	    
    }

}

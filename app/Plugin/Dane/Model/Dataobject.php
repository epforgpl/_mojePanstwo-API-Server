<?

App::uses('Dataset', 'Dane.Model');
App::uses('Layer', 'Dane.Model');
App::uses('PageRequest','Dane.Model');

App::uses('OrganizacjeDzialaniaPisma', 'Dane.Model');
App::uses('OrganizacjeDzialaniaTematy', 'Dane.Model');
App::uses('PismoSzablon', 'Dane.Model');
App::uses('OrganizacjeDzialania', 'Dane.Model');
App::uses('Temat', 'Dane.Model');
App::uses('S3Component', 'Controller.Component');

class Dataobject extends AppModel
{
    public $useDbConfig = 'MPSearch';
    public $id;
    public $data = array();

    public static function apiUrl($dataset, $object_id) {
        return Router::url(array('plugin' => 'Dane', 'controller' => 'Dataobjects', 'action' => 'view', 'dataset' => $dataset, 'id' => $object_id), true);
    }

    public static function mpUrl($dataset, $object_id) {
        return 'https://mojepanstwo.pl/dane/' . $dataset .'/' . $object_id;
    }

	public static function schemaUrl($dataset) {
		return 'https://api-v3.mojepanstwo.pl/schemas/dane/' . $dataset .'.json';
	}
	
	/*
    public function setId($id)
    {

        return $this->id = $id;

    }

    public function getObject($dataset, $id, $params = array(), $throw_not_found = false)
    {
        
        $search_field = isset($params['search_field']) ? $params['search_field'] : 'id';
        
		if( $object = $this->getDataSource()->getObject($dataset, $id, $search_field) )
			$this->data =$object;
		else
			return false;     
			        
        if( isset($params['slug']) && $params['slug'] && ( $params['slug']!=$this->data['slug'] ) )	        
	        return $this->data;

        $this->fillIDs($this->data);
        
        // query dataset and its layers
        $mdataset = new Dataset();
        $ds = $mdataset->find('first', array(
            'conditions' => array(
                'Dataset.alias' => $dataset,
            ),
        ));
        
        $layers = array();
        foreach($ds['Layer'] as $layer) {
            $layers[$layer['layer']] = null;
        }
        unset( $ds['Layer'] );
        $layers['dataset'] = null;

        // load queried layers
		if( isset($params['layers']) && !empty($params['layers']) ) {
            
            if ($params['layers'] == '*') {
            
                $params['layers'] = array_keys($layers);
            
            } elseif (!is_array($params['layers'])) {
            
                $params['layers'] = explode(',', $params['layers']);                
            
            }
            
            foreach( $params['layers'] as $layer ) {
                if (empty($layer)) {
                    continue;
                }

                if (!array_key_exists($layer, $layers)) {
                    continue;
                    // TODO dedicated 422 error
                    //throw new BadRequestException("Layer doesn't exist: " . $layer);
                }

                if ($layer == 'dataset') {
                    $layers['dataset'] = $ds;
                } else {
                    $layers[$layer] = $this->getObjectLayer($dataset, $id, $layer);
                }
            }
            
        }
		
		if( isset($params['dataset']) && $params['dataset'] )
			$layers['dataset'] = $ds;
		
        $this->data['layers'] = $layers;

        return $this->data;
    }
    */
    
    public function getRedirect($dataset, $id)
    {
		
		App::import('model', 'DB');
        $this->DB = new DB();
		
        switch( $dataset ) {
	        case 'zamowienia_publiczne': {
	        	
	        	if( $parent_id = $this->DB->selectValue("SELECT `parent_id` FROM `uzp_dokumenty` WHERE `id`='" . addslashes( $id ) . "' LIMIT 1") )
			        return array(
			        	'alias' => 'zamowienia_publiczne',
			        	'object_id' => $parent_id,
			        );
		        
	        }
	        
	        case 'zamowienia_publiczne_wykonawcy': {
	        	
	        	if( $krs_id = $this->DB->selectValue("SELECT `krs_id` FROM `uzp_wykonawcy` WHERE `id`='" . addslashes( $id ) . "' LIMIT 1") )
			        return array(
			        	'alias' => 'krs_podmioty',
			        	'object_id' => $krs_id,
			        );
		        
	        }
        }
        
        return false;

    }

    public function getObjectLayer($dataset, $id, $layer, $params = array())
    {
    	
    	/*
    	debug(array(
	    	'function' => 'getObjectLayer',
	    	'dataset' => $dataset,
	    	'id' => $id,
	    	'layer' => $layer,
	    	'params' => $params,
    	));
    	*/
    	
    	$id = (int) $id;
    	
        $file = ROOT . DS . APP_DIR . DS . 'Plugin' . DS . 'Dane' . DS . 'Model' . DS . 'Dataobject' . DS . $dataset . DS . 'layers' . DS . $layer . '.php';
		
		/*
		debug(array(
			'file' => $file,
			'file_exists' => file_exists($file),
			'data' => $this->data,
		));
		*/
		
        if (!file_exists($file))
            return false;
		
        App::import('model', 'DB');
        $this->DB = new DB();
        
        App::import('model', 'S3Files');
        $this->S3Files = new S3Files();
        
        App::Import('ConnectionManager');
		$this->ES = ConnectionManager::getDataSource('MPSearch');

        $output = include($file);
        return $output;
    }
    
    public function getAlertsQueries( $id, $user_id )
    {
	    
	    App::import('model', 'DB');
        $this->DB = new DB();
	    
	    $q = "SELECT `m_alerts_groups_qs-objects`.q_id, `m_alerts_qs`.`q` , `m_alerts_groups_qs-objects`.hl, COUNT( * ) AS `count`
		FROM `m_alerts_groups_qs-objects`
		JOIN `m_alerts_qs` ON `m_alerts_groups_qs-objects`.`q_id` = `m_alerts_qs`.`id`
		JOIN `m_alerts_groups-objects` ON `m_alerts_groups_qs-objects`.`object_id` = `m_alerts_groups-objects`.`object_id`
		JOIN `m_alerts_groups_qs` ON `m_alerts_groups-objects`.`group_id` = `m_alerts_groups_qs`.`group_id`
		WHERE `m_alerts_groups_qs-objects`.`object_id` = '" . $id . "'
		AND `m_alerts_groups-objects`.`user_id` = '" . $user_id . "'
		AND `m_alerts_groups_qs`.`q_id` = `m_alerts_groups_qs-objects`.q_id
		GROUP BY `m_alerts_groups_qs-objects`.hl
		ORDER BY `count` DESC , `m_alerts_qs`.`q` ASC
		LIMIT 0 , 30";
			    
	    return $this->DB->selectAssocs($q);
		
    }
    
    private function getData( $key = '*' )
    {
	    	    
	    if( $key == '*' )
	    	return $this->data['data'];
	    elseif( array_key_exists($key, $this->data['data']) )
		    return $this->data['data'][ $key ];
		else
			return false;
	    
    }
    
    /*
    public function search($aliases, $queryData = array()) {
	    	    
	   	$filters = array();
	   	
	   	if( !( is_string($aliases) && $aliases=='*' ) )
		    $filters = array(
		    	'dataset' => $aliases,
		    );
		    
		$switchers = array();
		$facets = array();
		$q = false;
		$mode = 'search_main';
		$do_facets = (isset($queryData['facets']) && $queryData['facets']) ? true : false;
		$limit = (isset($queryData['limit']) && $queryData['limit']) ? $queryData['limit'] : 20;
		$order = (isset($queryData['order']) && $queryData['order']) ? $queryData['order'] : array();
		$page = (isset($queryData['page']) && $queryData['page']) ? $queryData['page'] : 1;
		$version = (isset($dataset['Dataset']['version']) && $dataset['Dataset']['version']) ? $dataset['Dataset']['version'] : false;
				
		if( isset($queryData['conditions']) && is_array($queryData['conditions']) ) {
			foreach( $queryData['conditions'] as $key => $value ) {
				
				if( in_array($key, array('page', 'limit')) )
					continue;
					
				if( $key=='q' )
					$q = $value;
				elseif( in_array($key, array('_source', '_app')) )
					$filters[ $key ] = $value;
			
			}
		}
		
		
		if( $do_facets ) {
			
			$facets[] = 'dataset';
			
			
			// $facets_dict = array();
			// if( isset($dataset['filters']) ) {
					
			// 	foreach( $dataset['filters'] as $filter ) 
			// 		if( ( $filter = $filter['filter'] ) && in_array($filter['typ_id'], array(1, 2)) ) {
											
			// 			$facets[] = array($filter['field'], in_array($filter['field'], $virtual_fields));
			// 			$facets_dict[ $filter['field'] ] = $filter;
					
			// 		}
			
			// }
			
		
		}
		
		if( isset($queryData['q']) )
			$q = $queryData['q'];

						
        $search = $this->find('all', array(
        	'q' => $q,
        	'mode' => $mode,
        	'filters' => $filters,
        	'facets' => $facets,
        	'order' => $order,
        	'limit' => $limit,
        	'page' => $page,
        	'version' => $version,
        ));
		
		
		if( isset($search['facets']) ) {
						
			App::import('model', 'DB');
	        $this->DB = new DB();
			
			$facets = array();
			foreach( $search['facets'] as $field => $buckets ) {
				
				
				if( $field == 'dataset' ) {
					
					$buckets = $buckets[ 0 ];
					$options = array();
					
					
					$ids = array();
		            foreach ($buckets as $b)
		                if( $b['key'] && $b['doc_count'] )
		                    $ids[] = $b['key'];
					
					$data = $this->DB->selectAssocs("SELECT `base_alias` as 'id', `name` as 'label' FROM `datasets` WHERE `base_alias`='" . implode("' OR `base_alias`='", $ids) . "'");
					$data = array_column( $data, 'label', 'id' );
					
					
					foreach( $buckets as $b )
						$options[] = array(
							'id' => $b['key'],
							'count' => $b['doc_count'],
							'label' => array_key_exists($b['key'], $data) ? $data[ $b['key'] ] : ' - ',
						);
											
		
			        
			        $facets[] = array(
			        	'field' => 'dataset',
			        	'typ_id' => '5',
			        	'parent_field' => '',
			        	'label' => 'Zbiory danych',
			        	'desc' => '',
			        	'params' => array(
			        		'options' => $options,
			        	),
			        );
		        
		        }
				
			}
			
			$search['facets'] = $facets;
			
		}
				
		return $search;
	    
    }
    */
    
    /*
    public function getFeed($id, $params) {
	    	    	    
	    $feed = $id;
	    
	    if( $params['channel'] )
	    	$feed .= ':' . $params['channel'];
	    
	    $params = array_merge(array(
        	'q' => false,
        	'mode' => 'search_main',
        	'filters' => array(
	        	'_feed' => $feed,
	        	'_date' => '[* TO now]',
        	),
        	'facets' => false,
        	'order' => false,
        	'context' => $id,
        	'limit' => $params['limit'],
        	'page' => $params['page'],
        ), $params);
	    
	    $search = $this->find('all', $params);
        
        return $search;
	    
    }
    */
    
    public function subscribe($params) {
	    
	    App::import('model', 'DB');
        $this->DB = new DB();
        
        if(
	        isset( $params['dataset'] ) && 
	        isset( $params['id'] ) && 
	        is_numeric( $params['id'] ) && 
	        ( $global_id = $this->DB->selectValue("SELECT `id` FROM `objects` WHERE `dataset`='" . addslashes( $params['dataset'] ) . "' AND `object_id`='" . $params['id'] . "'") )
        ) {
	        
	        $this->DB->insertUpdateAssoc('objects_subscriptions', array(
		        'id' => $global_id,
		        'dataset' => $params['dataset'],
		        'object_id' => $params['id'],
		        'user_type' => $params['user_type'],
		        'user_id' => $params['user_id'],
		        'deleted' => '0',
		        'mts' => 'NOW()',
	        ));
	        
	        App::Import('ConnectionManager');
			$this->ES = ConnectionManager::getDataSource('MPSearch');
			
			$es_params = array();
			$es_params['index'] = 'mojepanstwo_v1';
			$es_params['type']  = 'subs';
			$es_params['parent']  = $global_id;
			$es_params['id']  = $global_id . '-' . $params['user_type'] . '-' . $params['user_id'];
			$es_params['body']  = array(
				'user_type' => $params['user_type'],
				'user_id' => $params['user_id'],
			);
			
			$ret = $this->ES->API->index($es_params);
	        return true;
	        
        } else {
	        
	        throw new BadRequestException();
	        
        }
        	    
    }
    
    public function unsubscribe($params) {
	    
	    App::import('model', 'DB');
        $this->DB = new DB();
        
        if(
	        isset( $params['dataset'] ) && 
	        isset( $params['id'] ) && 
	        is_numeric( $params['id'] ) && 
	        ( $global_id = $this->DB->selectValue("SELECT `id` FROM `objects` WHERE `dataset`='" . addslashes( $params['dataset'] ) . "' AND `object_id`='" . $params['id'] . "'") )
        ) {
	        
	        $this->DB->q("UPDATE `objects_subscriptions` SET `deleted`='1' WHERE `id`='" . $global_id . "' AND `user_type`='" . $params['user_type'] . "' AND `user_id`='" . $params['user_id'] . "' AND `deleted`='0'");
	        
	        App::Import('ConnectionManager');
			$this->ES = ConnectionManager::getDataSource('MPSearch');
			
			$deleteParams = array();
			$deleteParams['index'] = 'mojepanstwo_v1';
			$deleteParams['type'] = 'subs';
			$deleteParams['id'] = $global_id . '-' . $params['user_type'] . '-' . $params['user_id'];
		    $deleteParams['ignore'] = array(404);
			
			$ret = $this->ES->API->delete($deleteParams);
	        return true;
	        	        
        } else {
	        
	        throw new BadRequestException();
	        
        }
        	    
    }
    
    public function checkSubscribtion($params) {
	    
	    App::import('model', 'DB');
        $this->DB = new DB();
	    
	    if(
		    isset($params['global_id']) &&  
		    isset($params['user_type']) &&  
		    isset($params['user_id']) && 
		    ( $subscribtion = $this->DB->selectAssoc("SELECT `mts` FROM `objects_subscriptions` WHERE `id`='" . addslashes( $params['global_id'] ) . "' AND `user_type`='" . addslashes( $params['user_type'] ) . "' AND `user_id`='" . addslashes( $params['user_id'] ) . "' AND `deleted`='0'") ) 
	    ) {
		    
		    return true;
		    
	    } else return false;
	    
    }

    public function accept_moderate_request($data, $object_id, $dataset) {
        $dataset = $data['dataset'];
        $object_id = $data['object_id'];
        $user_id = (int) $data['user_id'];
        $username = $data['username'];
        $title = $data['title'];
        $role = (int) $data['role'];

        if(!$role)
            $role = 2;

        $page_request_id = (int) $data['page_request_id'];
        $send_email = (bool) $data['send_email'];
        $success = true;

        App::import('model', 'DB');
        $this->DB = new DB();

        if($user_id == 0) {
            $user_id = $this->DB->selectValue("SELECT id FROM users WHERE username = '$username'");
        }

        if(!$user_id) {
            $success = false;
        } else {

            if ($page_request_id > 0) {
                $this->DB->q("UPDATE pages_requests SET status = 1 WHERE id = $page_request_id");
            }

            $this->DB->q("INSERT INTO `objects-users` (dataset, object_id, user_id, role) VALUES ('$dataset', $object_id, $user_id, $role)");
            
            // pobieramy global_id dla obietku
            // INSERT INGORE dla tabeli objects-pages
            // ObjectPage->syncById( $global_id );
            
            if ($send_email) {
                $email = $this->DB->selectValue("SELECT email FROM users WHERE id = $user_id");
                App::uses('CakeEmail', 'Network/Email');
                $Email = new CakeEmail('noreply');
                $Email->viewVars(array(
                    'username' => $username,
                    'title' => $title
                ));

                if( defined('MODERATE_REQUEST_test_email') ) {
                    $to_email = MODERATE_REQUEST_test_email;
                    $to_name = MODERATE_REQUEST_test_name;
                } else {
                    $to_email = $email;
                    $to_name = $username;
                }

                $status = $Email->template('Dane.moderate_request')
                    ->addHeaders(array('X-Mailer' => 'mojePaństwo'))
                    ->emailFormat('html')
                    ->subject('Udało się! Witam na Moim Państwie!')
                    ->to($to_email, $to_name)
                    ->from('asia.przybylska@epf.org.pl', 'Asia Przybylska')
                    ->replyTo('asia.przybylska@epf.org.pl', 'Asia Przybylska')
                    ->send();

                if(!$status)
                    $success = false;
            }
        }

        return array(
            'success' => $success
        );
    }

    public function before_accept_moderate_request($data, $object_id, $dataset)
    {
        $dataset = $data['dataset'];
        $object_id = $data['object_id'];
        $user_id = (int) $data['user_id'];
        $user_email = $data['user_email'];

        App::import('model', 'DB');
        $this->DB = new DB();

        if($user_id > 0) {
            $username = $this->DB->selectValue("SELECT username FROM users WHERE id = $user_id");
        } else {
            $username = $this->DB->selectValue("SELECT username FROM users WHERE email = '$user_email'");
        }

        $dataobject = (array) $this->find('first', array(
            'conditions' => array(
                'dataset' => $dataset,
                'id' => $object_id
            )
        ));

        return array(
            'username' => $username,
            'title' => $dataobject['data'][$dataset . '.nazwa']
        );
    }

    public function moderate_request($data, $object_id, $dataset) {
        $row = false;

        CakeLog::write('moderate_request', json_encode(array(
            'data' => $data,
            'object_id' => $object_id,
            'dataset' => $dataset,
            'user_id' => (int) $this->getCurrentUser('id')
        )));

        try {

            $request = new PageRequest();
            $user_id = (int) $this->getCurrentUser('id');
            if(!$user_id)
                throw new Exception('Musisz być zalogowany');

            $form = array();
            $form_fields = array('firstname', 'lastname', 'position', 'organization', 'email', 'phone');
            foreach($form_fields as $field)
                if(isset($data[$field]))
                    $form[$field] = $data[$field];

            App::uses('CakeEmail', 'Network/Email');
            $Email = new CakeEmail('noreply');

            if( defined('MODERATE_REQUEST_test_email') ) {
                $to_email = MODERATE_REQUEST_test_email;
                $to_name = MODERATE_REQUEST_test_name;
            } else {
                $to_email = $data['email'];
                $to_name =  $data['firstname'] . ' ' . $data['lastname'];
            }

            try {
                $status = $Email->template('Dane.moderate_request_begin')
                    ->addHeaders(array('X-Mailer' => 'mojePaństwo'))
                    ->emailFormat('html')
                    ->subject('Cześć! Fajnie, że jesteś!')
                    ->to($to_email, $to_name)
                    ->from('asia.przybylska@epf.org.pl', 'Asia Przybylska')
                    ->replyTo('asia.przybylska@epf.org.pl', 'Asia Przybylska')
                    ->send();
            } catch(SocketException $e) {
                throw new Exception('Wystąpił błąd podczas wysyłania wiadomości email (' .
                    $e->getMessage() . '). Spróbuj ponownie później.');
            }

            $row = $request->save(array(
                'PageRequest' => array_merge($form, array(
                    'dataset' => $dataset,
                    'object_id' => $object_id,
                    'user_id' => $user_id
                ))
            ));

            if(!$row)
                throw new Exception('Wystąpił błąd podczas zapisywania danych. Spróbuj ponownie później.');

        } catch(Exception $e) {
            return array(
                'error' => $e->getMessage()
            );
        }

        return array(
            'row' => $row
        );
    }

    public function save_edit_data_form($data, $id, $dataset) {
        App::uses('ObjectPage', 'Dane.Model');
        $this->ObjectPage = new ObjectPage();

        return array(
            'flash_message' => $this->ObjectPage->setData($data, $id, $dataset) ?
                'Dane zostały poprawnie zaktualizowane' :
                'Wystąpił błąd podczas zapisywania danych'
        );
    }

    public function add_activity($data, $id, $dataset) {

        $this->OrganizacjeDzialania = new OrganizacjeDzialania();
        $this->OrganizacjeDzialaniaTematy = new OrganizacjeDzialaniaTematy();
        $this->Temat = new Temat();

        $this->OrganizacjeDzialania->save(array(
            'OrganizacjeDzialania' => array(
                'owner_dataset' => $dataset,
                'owner_name' => $data['owner_name'],
                'owner_object_id' => $id,
                'cts' => date('Y-m-d H:i:s'),
                'user_id' => (int) CakeSession::read('Auth.User.id'),
                'tytul' => $data['tytul'],
                'opis' => $data['opis'],
                'status' => $data['status'],
                'podsumowanie' => $data['podsumowanie'],
                'cover_photo' => $data['cover_photo'] ? '1' : '0',
                'photo_disabled' => isset($data['photo_disabled']) ? '1' : '0',
                'zakonczone' => isset($data['zakonczone']) ? '1' : '0',
                'folder' => isset($data['folder']) ? $data['folder'] : '1',
                'geo_lat' => (float) $data['geo_lat'],
                'geo_lng' => (float) $data['geo_lng']
            )
        ));

        $dzialanie_id = $this->OrganizacjeDzialania->getLastInsertId();
		if(isset($data['files']) && is_array($data['files'])) {
			App::uses('ActivitiesFiles', 'Activities.Model');
			$this->ActivitiesFiles = new ActivitiesFiles();
			foreach($data['files'] as $file) {
				$this->ActivitiesFiles->clear();
				$this->ActivitiesFiles->save(array(
					'ActivitiesFiles' => array(
						'dzialanie_id' => $dzialanie_id,
						'filename' => $file['filename'],
						'src_filename' => $file['src_filename']
					)
				));
			}
		}

        $this->_update_activity_tags($dzialanie_id, @$data['tagi']);
        $this->_update_activity_photo($dzialanie_id, $data);
        $this->update_activity_mail_template($dzialanie_id, $data);

        return array(
            'flash_message' => 'Działanie zostało poprawnie dodane',
            'redirect_url' => '/dane/' . $dataset . '/' . $id . '/dzialania/' . $dzialanie_id,
        );
    }

    public function edit_activity($data, $id, $dataset) {

        $success = false;
        $this->OrganizacjeDzialania = new OrganizacjeDzialania();
        $this->OrganizacjeDzialaniaTematy = new OrganizacjeDzialaniaTematy();
        $this->Temat = new Temat();

        $object = $this->OrganizacjeDzialania->find('first', array(
            'conditions' => array(
                'OrganizacjeDzialania.owner_dataset' => $dataset,
                'OrganizacjeDzialania.owner_object_id' => $id,
                'OrganizacjeDzialania.id' => $data['id']
            )
        ));

        $deleted = isset($data['deleted']) && $data['deleted'] == '1';

        if($object) {

            $toUpdate['mts'] = date('Y-m-d H:i:s');
            $toUpdate['id'] = $object['OrganizacjeDzialania']['id'];

            $fields = array('tytul', 'owner_name', 'opis', 'folder', 'status', 'podsumowanie', 'geo_lat', 'geo_lng', 'photo_disabled', 'zakonczone');
            if($deleted)
                $fields[] = 'deleted';

            foreach($fields as $field) {
                if(isset($data[$field]))
                    $toUpdate[$field] = $data[$field];
            }

            if(strlen($data['cover_photo']) > 100) {
                $toUpdate['cover_photo'] = '1';
            } else {
                $toUpdate['cover_photo'] = '0';
            }

            $this->_update_activity_tags($object['OrganizacjeDzialania']['id'], @$data['tagi']);

            try {
                $this->_update_activity_photo($object['OrganizacjeDzialania']['id'], $data);
            } catch(Exception $e) {
                return array(
                    'flash_message' => $e->getMessage()
                );
            }

            $toUpdateFields = array('mts', 'cover_photo', 'tytul', 'owner_name', 'opis', 'status', 'podsumowanie', 'folder', 'geo_lat', 'geo_lng', 'photo_disabled', 'zakonczone');
            if($deleted)
                $toUpdateFields[] = 'deleted';

            $success = $this->OrganizacjeDzialania->save($toUpdate, false, $toUpdateFields);

        }

        $response = array(
            'flash_message' =>
                $success ?
                    $deleted ?
                        'Działanie zostało poprawnie usunięte'
                        :
                        'Działanie zostało poprawnie zaktualizowane'
                    :
                    'Wystąpił błąd podczas aktualizacji'
        );

        if($deleted)
            $response['redirect_url'] = "/dane/$dataset/$id/dzialania";

        return $response;
    }

    private function _update_activity_photo($id, $data) {
        if($id && strlen($data['cover_photo']) > 100) {

            $this->S3 = new S3Component(new ComponentCollection);

            $image = $data['cover_photo'];
            $ext = 'jpg';
            $x = (int) $data['x'];
            $y = (int) $data['y'];
            $zoom = ((float) $data['zoom']) * 100;
            $width = 885;
            $height = 350;

            $src = 'pages/dzialania/' . $id . '.' . $ext;
            $tmp_src = APP . 'tmp/' . $id . '.' .$ext;
            $tmp_src_zoom = APP . 'tmp/' . $id . '_zoom.' .$ext;
            $tmp_src_crop = APP . 'tmp/' . $id . '_crop.' .$ext;

            $data = explode(',', $image);
            $decoded = base64_decode($data[1]);

            if(!$decoded)
                throw new Exception('base64_decode error');

            $object = $this->S3->putObject(
                $decoded,
                'portal',
                '0/'.$src,
                S3::ACL_PUBLIC_READ,
                array(),
                array('Content-Type' => 'image/' . $ext)
            );

            if(!$object)
                throw new Exception('S3 putObject error');

            $tmp_image = file_put_contents($tmp_src, file_get_contents('http://sds.tiktalik.com/portal/0/' . $src));

            if(!$tmp_image)
                throw new Exception('tmp_image error');

            exec("convert $tmp_src -resize $zoom% $tmp_src_zoom");

            $x = $x >= 0 ? '-' . $x : '+' . (-$x);
            $y = $y >= 0 ? '-' . $y : '+' . (-$y);

            exec("convert $tmp_src_zoom -crop {$width}x{$height}{$x}{$y}\! -background white -flatten $tmp_src_crop");

            $crop_image = file_get_contents($tmp_src_crop);

            $object = $this->S3->putObject(
                $crop_image,
                'portal',
                '1/'.$src,
                S3::ACL_PUBLIC_READ,
                array(),
                array('Content-Type' => 'image/' . $ext)
            );

            exec("convert $tmp_src_crop -resize x200 $tmp_src_crop");

            $crop_image = file_get_contents($tmp_src_crop);

            $object = $this->S3->putObject(
                $crop_image,
                'portal',
                '2/'.$src,
                S3::ACL_PUBLIC_READ,
                array(),
                array('Content-Type' => 'image/' . $ext)
            );

            unlink($tmp_src_crop);
            unlink($tmp_src_zoom);
            unlink($tmp_src);
        }
    }

    private function _update_activity_tags($id, $tags = false) {
        $tags = explode(',', $tags);

        $this->OrganizacjeDzialaniaTematy->deleteAll(array(
            'OrganizacjeDzialaniaTematy.dzialanie_id' => $id
        ), false);

        $update = array();

        if(!$tags)
            return true;

        foreach($tags as $tag) {
            $q = trim($tag);
            $temat = $this->Temat->find('first', array(
                'conditions' => array(
                    'Temat.q' => $q
                )
            ));

            if(!$temat) {
                $this->Temat->clear();
                $this->Temat->save(array(
                    'q' => $q,
                ));

                $update[] = (int) $this->Temat->getLastInsertId();
            } else {
                $update[] = (int) $temat['Temat']['id'];
            }
        }

        $update = array_unique($update);

        foreach($update as $temat_id) {
            $this->OrganizacjeDzialaniaTematy->clear();
            $this->OrganizacjeDzialaniaTematy->save(array(
                'dzialanie_id' => $id,
                'temat_id' => $temat_id
            ));
        }
    }

    private function update_activity_mail_template($activity_id, $data) {
        $this->OrganizacjeDzialaniaPisma = new OrganizacjeDzialaniaPisma();
        $this->PismoSzablon = new PismoSzablon();

        $pismo_dzialania = $this->OrganizacjeDzialaniaPisma->find('first', array(
            'conditions' => array(
                'OrganizacjeDzialaniaPisma.dzialanie_id' => $activity_id
            )
        ));

        if($pismo_dzialania) {
            if(@$data['mail_template'] == '') {
                $this->OrganizacjeDzialaniaPisma->deleteAll(array(
                    'OrganizacjeDzialaniaPisma.dzialanie_id' => $activity_id
                ), false);
            } else {
                $template_id = $pismo_dzialania['OrganizacjeDzialaniaPisma']['pismo_szablon_id'];
                $this->Template->save(array(
                    'Template' => array(
                        'id' => $template_id,
                        'name' => $data['tytul'],
                        'content' => $data['mail_template']
                    )
                ));
            }
        } elseif(@$data['mail_template'] != '') {
            $this->PismoSzablon->create();
            $this->PismoSzablon->save(array(
                'nazwa' => $data['tytul'],
                'tresc' => $data['mail_template']
            ));

            $template_id = $this->PismoSzablon->getLastInsertId();
            $this->OrganizacjeDzialaniaPisma->save(array(
                'dzialanie_id' => $activity_id,
                'pismo_szablon_id' => $template_id,
                'target' => $data['mail_target']
            ));
        }
    }

}



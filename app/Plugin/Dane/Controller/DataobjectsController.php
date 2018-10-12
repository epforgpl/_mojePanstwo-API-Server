<?

App::uses('AppController', 'Controller');
App::uses('MPSearch', 'Model/Datasource');
App::uses('MpUtils\Url', 'Lib');

class DataobjectsController extends AppController
{
	// TODO czemu jest Dane.Subscription i Dane.Subscriptions?
    public $uses = array('Dane.Dataobject', 'Dane.DatasetChannel',
		'Dane.Subscription', 'Dane.Subscriptions', 'Dane.ObjectPage', 'MPCache');
	public $components = array('S3');

	const RESULTS_COUNT_DEFAULT = 50;
	const RESULTS_COUNT_MAX = 500;
	
	public function index($dataset = false) {
		// obsługa danych przekazywanych przez POST params, tak jakby to był GET
		if( $this->request->is('post') ) {
			$this->request->query = array_merge($this->request->query, $this->request->data);
		}
						
		$this->_index(array(
			'dataset' => $dataset
		));
	}
	
	public function suggest() {
		
		$hits = array();
		
		if(
			isset( $this->request->query['q'] ) && 
			( $q = trim($this->request->query['q']) )
		) {
			
			$params = array(
				'dataset' => false,
			);
			
			if(
				isset($this->request->query['dataset']) &&
	            ($dataset = $this->request->query['dataset'])
			)
				$params['dataset'] = $dataset;
			
			$hits = $this->Dataobject->getDataSource()->suggest($q, $params);
			
		}
		
		$this->set('hits', $hits);
		$this->set('_serialize', 'hits');
		
	}
	
	public function feed($dataset, $id = false)
    {	    
		
		if( $dataset == 'user' ) {
			
			$feed_params = array(
				'user_type' => $this->Auth->user('type'),
				'user_id' => $this->Auth->user('id'),
			);
						
		} else {
		
			$feed_params = array(
				'dataset' => $dataset,
				'object_id' => $id,
			);
			
			if( isset($this->request->query['channel']) )
				$feed_params['channel'] = $this->request->query['channel'];
		
		}
				
	    $this->_index(array(
		    '_feed' => $feed_params,
	    ));
	    
    }


	private function _index($params = array()){
		$allowed_query_params = array('conditions', 'limit', 'page', 'order', 'highlight', '_type');
		if ($this->isPortalCalling) {
			array_push($allowed_query_params, 'aggs');
		}
		
		if( !isset($this->request->query['_type']) )
			$this->request->query['_type'] = 'objects';
		
		$original_query = $query = array_intersect_key($this->request->query, array_flip($allowed_query_params));
		
		if( $query['_type']=='collections' ) {

			$objects = $this->ObjectPage->query("
				SELECT
					`objects`.`id`
				FROM
					`objects-users`
				JOIN
					`objects` ON
						`objects`.`dataset` = `objects-users`.`dataset` AND
						`objects`.`object_id` = `objects-users`.`object_id`
				WHERE
					`objects-users`.`user_id` = ". $this->Auth->user('id') ."
			");

			$query['conditions']['OR'] = array(
				'user_id' => $this->Auth->user('id'),
				'object_id' => array_map(function($value) {
					return  $value['objects']['id'];
				}, $objects),
			);
			
		} elseif( $query['_type']=='letters' ) {
			
			
			$query['conditions']['from_user_type'] = $this->Auth->user('type');
			$query['conditions']['from_user_id'] = $this->Auth->user('id');
			$query['order'] = array(
				'created_at desc',
			);
						
		} else {
		
			if( isset($params['dataset']) && $params['dataset'] )
				$query['conditions']['dataset'] = $params['dataset'];
	
			if( isset($params['_main']) && $params['_main'] )
				$query['conditions']['_main'] = true;
				
			if( isset($params['_feed']) && $params['_feed'] )
				$query['conditions']['_feed'] = $params['_feed'];		
			
			if( isset( $query['conditions']['subscribtions'] ) && $query['conditions']['subscribtions'] ) {
							
				$query['conditions']['subscribtions'] = array(
					'user_type' => $this->Auth->user('type'),
					'user_id' => $this->Auth->user('id'),
				);
			}
			
			if( isset( $query['conditions']['user-pages'] ) && $query['conditions']['user-pages'] ) {
							
				$query['conditions']['user-pages'] = array(
					'user_type' => $this->Auth->user('type'),
					'user_id' => $this->Auth->user('id'),
				);
			}
		
		}

		// ograniczenie limit
		if (isset($query['limit'])) {
			if ($query['limit'] > DataobjectsController::RESULTS_COUNT_MAX) {
				$query['limit'] = DataobjectsController::RESULTS_COUNT_MAX;
			}
		} else {
			$query['limit'] = DataobjectsController::RESULTS_COUNT_DEFAULT;
		}
		
		if (!isset($query['page'])) {
			$query['page'] = 1;
		}
		
		if ( ($query['page'] * $query['limit']) > 2000000 ) {
			throw new BadRequestException('page * $limit should less or equal to 2000000', 422); // 422 Unprocessable Entity
		}
				
		if( isset($this->request->query['fields']) ) {
			
			if( is_array($this->request->query['fields']) )
				$query['fields'] = $this->request->query['fields'];
			elseif( is_string($this->request->query['fields']) )
				$query['fields'] = array($this->request->query['fields']);
			
		}
		
		$objects = $this->Dataobject->find('all', $query);
		$this->log($objects);

		$lr_stats = @$this->Dataobject->getDataSource()->lastResponseStats;
		$count = @$lr_stats['count'];
		$took = @$lr_stats['took_ms'];

		$_serialize = array('Dataobject', 'Count', 'Took');

		// HATEOS
		if( $this->request->is('get') ) {
			// using post, aggregated arrays are failing on MpUrils/Url.php:145

			$processed_query = $this->Dataobject->buildQuery('all', $query);
			$page = $processed_query['page']; // starts with 1

			$url = new MpUtils\Url(Router::url(null, true));
			$url->setParams($original_query);

			$_links = array(
				'self' => $url->buildUrl()
			);

			$lastPage = (int)(($count - 1) / $processed_query['limit']) + 1;
			if ($page > 1 && $page <= $lastPage) {
				$url->setParam('page', 1);
				$_links['first'] = $url->buildUrl();

				$url->setParam('page', $page - 1);
				$_links['prev'] = $url->buildUrl();
			}

			if ($page < $lastPage) {
				$url->setParam('page', $page + 1);
				$_links['next'] = $url->buildUrl();

				$url->setParam('page', $lastPage);
				$_links['last'] = $url->buildUrl();
			}

			// page out of bounds
			if ($page > $lastPage or $page < 1) {
				$url->setParam('page', 1);
				$_links['first'] = $url->buildUrl();

				$url->setParam('page', $lastPage);
				$_links['last'] = $url->buildUrl();
			}

			array_push($_serialize, 'Links');
			$this->set('Links', $_links);
		}
		
		if( !empty($this->Dataobject->getDataSource()->Aggs) ) {
			$this->set('Aggs', $this->Dataobject->getDataSource()->Aggs);
			$_serialize[] = 'Aggs';
		}
		

		$this->set('Dataobject', $objects);
		$this->set('Count', $count);
		$this->set('Took', $took);
        $this->set('_serialize', $_serialize);
	}

    private $postRequestConfig = array(

        'bdl_wskazniki' => array(
            'name' => 'BdlPodgrupy',
            'roles' => array()
        ),

        'bdl_wariacje' => array(
            'name' => 'BdlWariacje',
            'roles' => array()
        ),

        'prawo_hasla' => array(
            'name' => 'PrawoHasla',
            'roles' => array()
        ),

        'krs_podmioty' => array(
            'name' => 'KrsPodmioty',
            'roles' => array('superuser', 'owner', 'admin')
        )

    );
	
	public function post($dataset, $id)
	{
	
		$output = false;
        $roles = array();
	
		if( 
			isset($this->request->data['_action']) && 
			( $action = $this->request->data['_action'] )
		) {
			
			unset( $this->request->data['_action'] );			
			$datasets = array_keys($this->postRequestConfig);
			
			if( in_array($dataset, $datasets) ) {
	
	            $params = $this->postRequestConfig[$dataset];
	            $name = $params['name'];
                $roles = $params['roles'];
	
			} else {
				
				$name = 'Dataobject';
				
			}

            #if(!$this->hasAccessToDatasetObject($dataset, $id, $roles))
            #    throw new ForbiddenException;
			
			try {
	                
                $this->loadModel('Dane.' . $name);
                	                
                if( method_exists($this->$name, $action) ) {
	                $output = $this->$name->$action($this->data, $id, $dataset);
                } else {
                    $this->loadModel('Dane.Dataobject');
                    $output = $this->Dataobject->$action($this->data, $id, $dataset);
                }

            } catch (MissingModelException $e) {



            }
			
			
		}
		
		$this->set('output', $output);
		$this->set('_serialize', 'output');
	}

    /*
     * @todo przenieść funkcje "wyżej" tak żeby można jej było użyć też w
     * ObjectPagesManagementController oraz ObjectUsersManagementController
     * (usuniecie powielania kodu w 3 miejsach)
     */
    private function hasAccessToDatasetObject($dataset, $id, $roles) {

        if(!count($roles))
            return true;

        if($this->Auth->user('type') != 'account')
            return false;

        $this->loadModel('Paszport.User');

        $this->User->recursive = 2;
        $user = $this->User->findById(
            $this->Auth->user('id')
        );

        if(!$user)
            return false;

        foreach($user['UserRole'] as $role) {
            if(in_array($role['Role']['name'], $roles))
                return true;
        }

        $this->loadModel('Dane.ObjectUser');

        $object = $this->ObjectUser->find('first', array(
            'conditions' => array(
                'ObjectUser.dataset' => $dataset,
                'ObjectUser.object_id' => $id,
                'ObjectUser.user_id' => $user['User']['id']
            )
        ));

        if($object) {

            $names = array(
                '1' => 'owner',
                '2' => 'admin'
            );

            if(
                isset($names[$object['ObjectUser']['role']]) &&
                in_array($names[$object['ObjectUser']['role']], $roles)
            )
                return true;

        }

        return false;
    }
	
    public function view($dataset, $id)
    {
		$allowed_query_params = array('layers');
		if ($this->isPortalCalling) {
			array_push($allowed_query_params, 'aggs');
		}

		$query = array_intersect_key($this->request->query, array_flip($allowed_query_params));

		$dataobject_query = array(
			'conditions' => array(
				'dataset' => $dataset,
				'id' => $id
			),
			'layers' => array(
				'page'
			),
		);
		
		if( isset($this->request->query['fields']) ) {
			
			if( is_array($this->request->query['fields']) )
				$dataobject_query['fields'] = $this->request->query['fields'];
			elseif( is_string($this->request->query['fields']) )
				$dataobject_query['fields'] = array($this->request->query['fields']);
			
		}

		if (isset($query['aggs'])) {
			$dataobject_query['aggs'] = $query['aggs'];
		}
	        
	    $object = $this->Dataobject->find('first', $dataobject_query);
	    	    
	    if( !$object ) {
		    throw new NotFoundException();
	    }
	    
	    $this->Dataobject->data = $object;

		// LAYERS
		// load list of layers
		$object['layers'] = array(
			'dataset' => null,
			'channels' => null,
			'page' => null,
			'subscribers' => null,
		);
		
		if( $dataset_info = $this->MPCache->getDataset($dataset) )
			foreach($dataset_info['Layer'] as $layer)
				$object['layers'][$layer['layer']] = null;
		
		// what should we load?
		$layers_to_load = array();
		if( isset($query['layers']) ) {
			$layers_to_load = $query['layers'];

			if (is_string($layers_to_load)) {
				// load all layers?
				if ($layers_to_load == '*') {
					$layers_to_load = array_keys($object['layers']);

				} else {
					$layers_to_load = array($layers_to_load);
				}
			}

			// load only available layers
			// $layers_to_load = array_intersect($layers_to_load, array_keys($object['layers']));
		}
		
		if( $this->Auth->user('type')=='account') {
			
			if( !in_array('subscription', $layers_to_load) )
				$layers_to_load[] = 'subscription';
			
		}
				
		// load layers
		foreach( $layers_to_load as $layer ) {

			if ( $layer == 'dataset' ) {
				$object['layers']['dataset'] = $dataset_info;

			} elseif ( $layer == 'subscribers' ) {
				$subscribers = array(
					'list',
					'count'
				);

				$params = array(
					'fields' => array(
						'Users.username',
						'Users.photo_small'
					),
					'conditions' => array(
						'Subscriptions.dataset' => $dataset,
						'Subscriptions.object_id' => $id,
						'Subscriptions.user_type' => 'account'
					),
					'joins' => array(
						array(
							'table' => 'users',
							'alias' => 'Users',
							'type' => 'RIGHT',
							'conditions' => array(
								'Subscriptions.user_id = Users.id',
                                array(
                                    'not' => array(
                                        'Users.username' => ''
                                    )
                                )
							)
						)
					),
					'group' => array(
						'Subscriptions.user_id'
					),
					'order' => 'Subscriptions.cts'
				);

				$subscribers['list'] = $this->Subscriptions->find('all', array_merge($params, array(
					'limit' => 20
				)));

				$subscribers['count'] = $this->Subscriptions->find('count', $params);

				$object['layers']['subscribers'] = $subscribers;

			} elseif( $layer=='page' ) {
				$objectPage = $this->ObjectPage->find('first', array(
					'conditions' => array(
						'ObjectPage.dataset' => $dataset,
						'ObjectPage.object_id' => $id
					)
				));
				
				$page = array(
					'cover' => false,
					'logo' => false,
					'moderated' => false,
					'credits' => null
				);

				if($objectPage) {
					$page = array(
						'cover' => $objectPage['ObjectPage']['cover'] == '1' ? true : false,
						'logo' => $objectPage['ObjectPage']['logo'] == '1' ? true : false,
						'moderated' => $objectPage['ObjectPage']['moderated'] == '1' ? true : false,
						'credits' => $objectPage['ObjectPage']['credits'],
                        'description' => @$objectPage['ObjectPage']['description']
					);
				}

				if( $this->Auth->user('type')=='account' ) {

					$this->loadModel('Dane.ObjectUser');
					$page['roles'] = $this->ObjectUser->find('first', array(
						'fields' => 'role',
						'conditions' => array(
							'ObjectUser.dataset' => $dataset,
							'ObjectUser.object_id' => $id,
							'ObjectUser.user_id' => $this->Auth->user('id'),
						),
					));
				}

				$object['layers']['page'] = $page;

			} elseif( $layer=='channels' ) {
				
				if( $object['dataset'] == 'zbiory' ) {
					
					if( $object['id'] == 226 ) {
						
						$object['layers']['channels'] = array(
							array(
								'DatasetChannel' => array(
									'channel' => '1',
									'title' => 'Działalność charytatywna',
									'subject_dataset' => 'zbiory',
								),
							),
							array(
								'DatasetChannel' => array(
									'channel' => '2',
									'title' => 'Pomoc społeczna',
									'subject_dataset' => 'zbiory',
								),
							),
							array(
								'DatasetChannel' => array(
									'channel' => '3',
									'title' => 'Ochrona praw obywatelskich i praw człowieka',
									'subject_dataset' => 'zbiory',
								),
							),
							array(
								'DatasetChannel' => array(
									'channel' => '4',
									'title' => 'Rozwój przedsiębiorczości',
									'subject_dataset' => 'zbiory',
								),
							),
							array(
								'DatasetChannel' => array(
									'channel' => '5',
									'title' => 'Nauka, kultura, edukacja',
									'subject_dataset' => 'zbiory',
								),
							),
							array(
								'DatasetChannel' => array(
									'channel' => '6',
									'title' => 'Ekologia',
									'subject_dataset' => 'zbiory',
								),
							),
							array(
								'DatasetChannel' => array(
									'channel' => '7',
									'title' => 'Działalność międzynarodowa',
									'subject_dataset' => 'zbiory',
								),
							),
							array(
								'DatasetChannel' => array(
									'channel' => '8',
									'title' => 'Aktywność społeczna',
									'subject_dataset' => 'zbiory',
								),
							),
							array(
								'DatasetChannel' => array(
									'channel' => '9',
									'title' => 'Sport, turystyka',
									'subject_dataset' => 'zbiory',
								),
							),
							array(
								'DatasetChannel' => array(
									'channel' => '10',
									'title' => 'Bezpieczeństwo publiczne',
									'subject_dataset' => 'zbiory',
								),
							),
							array(
								'DatasetChannel' => array(
									'channel' => '11',
									'title' => 'Pozostałe',
									'subject_dataset' => 'zbiory',
								),
							),
							array(
								'DatasetChannel' => array(
									'channel' => '12',
									'title' => 'Uchodźcy',
									'subject_dataset' => 'zbiory',
								),
							),
						);
						
					}
				
				} elseif( $object['dataset'] == 'users_phrases' ) {
					
					$this->loadModel('Dane.Subscription');
					$apps = $this->Subscription->apps;
					
					$object['layers']['channels'] = array();
					
					foreach( $apps as $app ) {
						
						$object['layers']['channels'][] = array(
							'DatasetChannel' => array(
								'channel' => $app['id'],
								'title' => $app['title'],
								'subject_dataset' => 'users_phrases',
							),
						);	
					
					}	
				
				} elseif( $object['dataset'] == 'aplikacje' ) {
					
					$this->loadModel('Dane.Subscription');
					$apps = $this->Subscription->datasets;
					
					$object['layers']['channels'] = array();
					$datasets = $apps[ $object['data']['aplikacje.slug'] ];
										
					foreach( $datasets as $dataset => $dataset_params ) {
						
						if( isset($dataset_params['id']) ) {
							$object['layers']['channels'][] = array(
								'DatasetChannel' => array(
									'channel' => $dataset_params['id'],
									'title' => $dataset_params['label'],
								),
							);	
						}
					
					}	
										
				} else {
				
					$object['layers']['channels'] = $this->DatasetChannel->find('all', array(
						'fields' => array('channel', 'title', 'subject_dataset'),
						'conditions' => array(
							'creator_dataset' => $object['dataset'],
						),
						'order' => 'ord asc',
					));
				
				}

			} elseif( $layer == 'subscription' ) {

                $object['layers']['subscription'] = $this->Subscription->find('first', array(
                    'conditions' => array(
                        'user_type' => $this->Auth->user('type'),
                        'user_id' => $this->Auth->user('id'),
                        'dataset' => $object['dataset'],
                        'object_id' => $object['id'],
                    )
                ));
                
            } else {
				$object['layers'][ $layer ] = $this->Dataobject->getObjectLayer($dataset, $id, $layer);
			}
		}

		// agregacje na obiekcie
		if( !empty($this->Dataobject->getDataSource()->Aggs) ) {
			$object['Aggs'] = $this->Dataobject->getDataSource()->Aggs;
		}
		
		// debug($this->Dataobject->getDataSource()->lastResponseStats);
		
		if( isset($this->Dataobject->getDataSource()->lastResponseStats['took_ms']) )
			header('X-ES-Took: ' . $this->Dataobject->getDataSource()->lastResponseStats['took_ms']);

		
		$this->setSerialized('object', $object);
    }
    
    public function view_layer()
    {
	    $this->loadModel('Dane.Dataset');
        $dataset = $this->Dataset->find('first', array(
            'conditions' => array(
                'Dataset.alias' => $this->request->params['alias'],
            )));

        $layer = $this->request->params['layer'];
        $matching_layers = array_filter($dataset['Layer'], function($l) use($layer) {return $l['layer'] == $layer;});

        if (empty($dataset) || empty($matching_layers)) {
            throw new NotFoundException();
        }

        $layer = $this->Dataobject->getObjectLayer($this->request->params['alias'], $this->request->params['object_id'], $layer);

        $this->setSerialized('layer', $layer);
    }

    public function layer()
    {

        $alias = $this->request->params['alias'];
        $id = $this->request->params['object_id'];
        $layer = $this->request->params['layer'];
        $params = array_merge($this->request->query, $this->data);

        if (!$alias || !$id || !$layer)
            return false;

        $layer = $this->Dataobject->getObjectLayer($alias, $id, $layer, $params);

        $this->set(array(
            'layer' => $layer,
            '_serialize' => 'layer',
        ));
    }
	
	/*
    public function alertsQueries()
    {

        $id = $this->request->params['id'];
        $queries = $this->Dataobject->getAlertsQueries($id, $this->user_id);

        $this->set(array(
            'queries' => $queries,
            '_serialize' => 'queries',
        ));
    }
    */
	
	public function subscribe()
	{
		
		$this->Auth->deny();
		
		$status = $this->Dataobject->subscribe(array(
			'dataset' => $this->request->params['dataset'],
			'id' => $this->request->params['id'],
			'user_type' => $this->Auth->user('type'),
			'user_id' => $this->Auth->user('id'),
		));
		
		$this->set('status', $status);
		$this->set('_serialize', array('status'));
		
	}
	
	public function unsubscribe()
	{
		
		$this->Auth->deny();
		
		$status = $this->Dataobject->unsubscribe(array(
			'dataset' => $this->request->params['dataset'],
			'id' => $this->request->params['id'],
			'user_type' => $this->Auth->user('type'),
			'user_id' => $this->Auth->user('id'),
		));
		
		$this->set('status', $status);
		$this->set('_serialize', array('status'));
		
	}
	
	public function objectFromSlug() {
	    
	    $output = false;
	    	    
	    if( @$this->request->params['slug'] ) {
		    
		    App::import('model','DB');
	        $DB = new DB();
	        
	        $output = $DB->selectAssoc("SELECT `dataset`, `object_id` FROM `objects_slugs` WHERE `slug`='" . addslashes( $this->request->params['slug'] ) . "' ORDER BY id DESC LIMIT 1");
		    		    
	    }
	    	    
	    $this->set('output', $output);
	    $this->set('_serialize', 'output');
	    
    }
    
    public function random()
    {
	    $datasets = array(
		    'krs_podmioty',
		    'krs_osoby', 
		    'science_theses',
		    'science_persons',
		    'ipn_persons',
	    );
	    
	    $i = rand(0, count($datasets)-1);
	    $dataset = $datasets[$i];
	    
	    $source = $this->Dataobject->getDataSource();
	    
	    $params = array(
			'index' => 'mojepanstwo_v1',
			'type' => 'objects',
			'body' => array(
				'from' => 0, 
				'size' => 1,
				'query' => array(
					'function_score' => array(
						'query' => array(
							'term' => array(
								'dataset' => $dataset,
							),
						),
						'functions' => array(
							array(
								'random_score' => new \stdClass(),
							),
						),
					),
				),
			),
		);
		
		$object = array();
				
		if(
			( $res = $source->API->search($params) ) && 
			(!empty($res['hits']['hits']))
		) {
			$hit = $res['hits']['hits'][0];
			switch ($dataset) {
				
				case 'krs_podmioty': {
					$object['title'] = $hit['_source']['data']['krs_podmioty']['nazwa'];
					break;
				}
				
				case 'krs_osoby': {
					$object['title'] = $hit['_source']['data']['krs_osoby']['imiona'] .  ' ' . $hit['_source']['data']['krs_osoby']['nazwisko'];
					break;
				}
				
				case 'science_theses': {
					$object['title'] = $hit['_source']['data']['science_theses']['title'];
					break;
				}
				
				case 'science_persons': {
					$object['title'] = $hit['_source']['data']['science_persons']['name'];
					break;
				}
				
				case 'ipn_persons': {
					$object['title'] = $hit['_source']['data']['ipn_persons']['first_names'] .  ' ' . $hit['_source']['data']['ipn_persons']['last_name'];
					break;
				}
				
			}
		}
		       
	    $this->set('object', $object);
		$this->set('_serialize', array('object'));
    }

}
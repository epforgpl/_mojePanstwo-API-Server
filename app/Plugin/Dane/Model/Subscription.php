<?

class Subscription extends AppModel
{
    
    public $apps = array(
	    array(
		    'id' => 1,
		    'title' => 'KRS',
		    'slug' => 'krs',
	    ),
	    array(
		    'id' => 2,
		    'title' => 'NGO',
		    'slug' => 'ngo',
	    ),
	    array(
		    'id' => 3,
		    'title' => 'Kultura',
		    'slug' => 'kultura',
	    ),
	    array(
		    'id' => 4,
		    'title' => 'Prawo',
		    'slug' => 'prawo',
	    ),
	    array(
		    'id' => 5,
		    'title' => 'Kto Tu Rządzi?',
		    'slug' => 'kto_tu_rzadzi',
	    ),
	    array(
		    'id' => 6,
		    'title' => 'Media',
		    'slug' => 'media',
	    ),
	    array(
		    'id' => 7,
		    'title' => 'Zamówienia Publiczne',
		    'slug' => 'zamowienia_publiczne',
	    ),
	    array(
		    'id' => 8,
		    'title' => 'Orzecznictwo',
		    'slug' => 'orzecznictwo',
	    ),
	    array(
		    'id' => 9,
		    'title' => 'Sejmometr',
		    'slug' => 'sejmometr',
	    ),
    );
    
    public $datasets = array(
	    'krs' => array(
            'krs_podmioty' => array(
                'label' => 'Organizacje',
                'browserTitle' => 'Organizacje w Krajowym Rejestrze Sądowym',
                'searchTitle' => 'Szukaj organizacji...',
                'menu_id' => 'organizacje',
                'autocompletion' => array(
                    'dataset' => 'krs_podmioty',
                ),
                'id' => 122,
            ),
            'krs_osoby' => array(
                'label' => 'Osoby',
                'browserTitle' => 'Osoby w Krajowym Rejestrze Sądowym',
                'searchTitle' => 'Szukaj osób...',
                'menu_id' => 'osoby',
                'id' => 136,
            ),
            'msig' => array(
                'label' => 'Monitor Sądowy i Gospodarczy',
                'browserTitle' => 'Wydania Monitora Sądowego i Gospodarczego',
                'searchTitle' => 'Szukaj w Monitorze Sądowym i Gospodarczym',
                'menu_id' => 'msig',
                'id' => 138,
            ),
        ),
        'srodowisko' => array(
            'srodowisko_stacje_pomiarowe' => array(
                'label' => 'Stacje pomiarowe',
                'menu_id' => '',
                'id' => 228,
           ),
        ),
        'kultura' => array(
            'kultura_ankiety' => array(
                'label' => 'Pytania o kulturę',
                'menu_id' => '',
                'id' => 229,
           ),
        ),
        'bdl' => array(
            'bdl_wskazniki' => array(
                'label' => 'Wskaźniki',
                'id' => 3,
            ),
            'bdl_wskazniki_grupy' => array(
                'label' => 'Grupy wskaźników',
                'id' => 1,
            ),
            'bdl_wskazniki_kategorie' => array(
                'label' => 'Kategorie wskaźników',
                'id' => 2,
            ),
        ),
        'prawo' => array(
            'dziennik_ustaw' => array(
                'label' => 'Dziennik Ustaw',
                'searchTitle' => 'Szukaj w Dzienniku Ustaw...',
                'menu_id' => 'dziennik_ustaw',
                'id' => 237,
            ),
            'monitor_polski' => array(
                'label' => 'Monitor Polski',
                'searchTitle' => 'Szukaj w Monitorze Polskim...',
                'menu_id' => 'monitor_polski',
                'id' => 238,
            ),
            'prawo_wojewodztwa' => array(
                'label' => 'Prawo lokalne',
                'searchTitle' => 'Szukaj w prawie lokalnym...',
                'menu_id' => 'lokalne',
                'id' => 182,
            ),
            'prawo_urzedowe' => array(
                'label' => 'Prawo urzędowe',
                'searchTitle' => 'Szukaj w prawie urzędowym...',
                'menu_id' => 'urzedowe',
                'id' => 181,
            ),
            'prawo_hasla' => array(
                'label' => 'Tematy w prawie',
                'searchTitle' => 'Szukaj w tematach...',
                'menu_id' => 'tematy',
                'autocompletion' => array(
                    'dataset' => 'prawo_hasla',
                ),
                'id' => 151,
            ),
        ),
        'orzecznictwo' => array(
            'sa_orzeczenia' => array(
                'label' => 'Orzeczenia sądów administracyjnych',
                'searchTitle' => 'Szukaj w orzeczeniach sądów administracyjnych...',
                'menu_id' => 'sa',
                'id' => 44,
            ),
            'sp_orzeczenia' => array(
                'label' => 'Orzeczenia sądów powszechnych',
                'searchTitle' => 'Szukaj w orzeczeniach sądów powszechnych...',
                'menu_id' => 'sp',
                'id' => 93,
            ),
            'sn_orzeczenia' => array(
                'label' => 'Orzeczenia Sądu Najwyższego',
                'searchTitle' => 'Szukaj w orzeczeniach Sądu Najwyższego...',
                'menu_id' => 'sn',
                'id' => 85,
            ),
        ),
        'ngo' => array(
            'ngo_tematy' => array(
                'label' => 'Tematy',
                'menu_id' => 'tematy',
                'id' => 220,
            ),
            'ngo_konkursy' => array(
                'label' => 'Konkursy',
                'menu_id' => 'konkursy',
                'id' => 226,
            ),
            'dzialania' => array(
                'label' => 'Działania',
                'menu_id' => 'dzialania',
                'id' => 199,
            ),
            'pisma' => array(
                'label' => 'Pisma',
                'menu_id' => 'pisma',
                'id' => 23,
            ),
            'zbiorki_publiczne' => array(
                'label' => 'Zbiórki publiczne',
                'menu_id' => 'zbiorki',
                'id' => 219,
            ),
            'sprawozdania_opp' => array(
                'label' => 'Sprawozdania Organizacji Pożytku Publicznego',
                'menu_id' => 'sprawozdania_opp',
                'id' => 227,
            ),
        ),
        'zamowienia_publiczne' => array(
            'zamowienia_publiczne' => array(
                'label' => 'Zamówienia publiczne',
                'id' => 126,
            ),
            'zamowienia_publiczne_zamawiajacy' => array(
                'label' => 'Zamawiający',
                'menu_id' => 'zamawiajacy',
                'id' => 198,
            ),
            'zamowienia_publiczne_wykonawcy' => array(
                'label' => 'Wykonawcy',
                'menu_id' => 'wykonawcy',
                'id' => 145,
            ),
        ),
        'media' => array(
            'twitter_accounts' => array(
                'label' => 'Obserwowane konta Twitter',
                'searchTitle' => 'Szukaj w kontach Twitter...',
                'menu_id' => 'twitter_konta',
                'default_order' => 'twitter_accounts.liczba_obserwujacych desc',
                'default_conditions' => array(
                    'twitter_accounts.liczba_tweetow>' => 0,
                ),
                'id' => 137,
            ),
            'twitter' => array(
                'label' => 'Tweety',
                'searchTitle' => 'Szukaj w tweetach...',
                'menu_id' => 'tweety',
                'id' => 101,
            ),
            'fb_accounts' => array(
                'label' => 'Obserwowane konta Facebook',
                'searchTitle' => 'Szukaj w kontach Facebook...',
                'menu_id' => 'fb_accounts',
                'default_order' => 'fb_accounts.likes desc',
                'id' => 230,
            ),
            'fb_posts' => array(
                'label' => 'Posty',
                'searchTitle' => 'Szukaj w postach...',
                'menu_id' => 'posts',
                'id' => 231,
            ),
        ),
        'sejmometr' => array(
            'poslowie' => array(
                'label' => 'Posłowie',
                'menu_id' => 'poslowie',
                'autocompletion' => array(
                    'dataset' => 'poslowie',
                ),
                'id' => 28,
            ),
            'prawo_projekty' => array(
                'label' => 'Projekty aktów prawnych',
                'menu_id' => 'prawo_projekty',
                'id' => 13,
            ),
            'sejm_dezyderaty' => array(
                'label' => 'Dezyderaty komisji',
                'menu_id' => 'dezyderaty',
                'id' => 50,
            ),
            'sejm_druki' => array(
                'label' => 'Druki sejmowe',
                'menu_id' => 'druki',
                'id' => 51,
            ),
            'sejm_glosowania' => array(
                'label' => 'Głosowania',
                'menu_id' => 'glosowania',
                'id' => 53,
            ),
            'sejm_interpelacje' => array(
                'label' => 'Interpelacje',
                'menu_id' => 'interpelacje',
                'id' => 57,
            ),
            'sejm_kluby' => array(
                'label' => 'Kluby sejmowe',
                'menu_id' => 'kluby',
                'id' => 59,
            ),
            'sejm_komisje' => array(
                'label' => 'Komisje sejmowe',
                'menu_id' => 'komisje',
                'id' => 60,
            ),
            'sejm_komunikaty' => array(
                'label' => 'Komunikaty Kancelarii Sejmu',
                'menu_id' => 'komunikaty',
                'id' => 61,
            ),
            'sejm_posiedzenia' => array(
                'label' => 'Posiedzenia Sejmu',
                'menu_id' => 'posiedzenia',
                'id' => 63,
            ),
            'sejm_posiedzenia_punkty' => array(
                'label' => 'Punkty porządku dziennego',
                'menu_id' => 'punkty',
                'id' => 65,
            ),
            'sejm_wystapienia' => array(
                'label' => 'Wystąpienia podczas posiedzeń Sejmu',
                'menu_id' => 'wystapienia',
                'id' => 69,
            ),
            'sejm_komisje_opinie' => array(
                'label' => 'Opinie komisji sejmowych',
                'menu_id' => 'komisje_opinie',
                'id' => 134,
            ),
            'sejm_komisje_uchwaly' => array(
                'label' => 'Uchwały komisji sejmowych',
                'menu_id' => 'komisje_uchwaly',
                'id' => 146,
            ),
            'poslowie_oswiadczenia_majatkowe' => array(
                'label' => 'Oświadczenia majątkowe posłów',
                'menu_id' => 'poslowie_oswiadczenia',
                'id' => 32,
            ),
            'poslowie_rejestr_korzysci' => array(
                'label' => 'Rejestr korzyści posłów',
                'menu_id' => 'poslowie_korzysci',
                'id' => 33,
            ),
            'poslowie_wspolpracownicy' => array(
                'label' => 'Współpracownicy posłów',
                'menu_id' => 'poslowie_wspolpracownicy',
                'id' => 34,
            ),
        ),
        'kto_tu_rzadzi' => array(
            'instytucje' => array(
                'label' => 'Instytucje',
                'menu_id' => 'instytucje',
                'order' => 'weight desc',
                'autocompletion' => array(
                    'dataset' => 'instytucje',
                ),
                'id' => 7,
            ),
            'gminy' => array(
                'label' => 'Gminy',
                'menu_id' => 'gminy',
                'order' => 'weight desc',
                'autocompletion' => array(
                    'dataset' => 'gminy',
                ),
                'id' => 6,
            ),
            'powiaty' => array(
                'label' => 'Powiaty',
                'menu_id' => 'powiaty',
                'order' => 'weight desc',
                'autocompletion' => array(
                    'dataset' => 'powiaty',
                ),
                'id' => 35,
            ),
            'wojewodztwa' => array(
                'label' => 'Województwa',
                'menu_id' => 'wojewodztwa',
                'order' => 'weight desc',
                'autocompletion' => array(
                    'dataset' => 'wojewodztwa',
                ),
                'id' => 104,
            ),
        ),
    );
    
    public $hasMany = array(
	    'SubscriptionChannel' => array(),
	    'SubscriptionQuery' => array(),
    );
    
    public function afterSave($created, $options) {
	    
	    $this->syncByData($this->data);
	    
    }
    
    public function add($data = array()) {
	    	    
	    $this->create();
        
        $sub = array(
	        'dataset' => $data['dataset'],
	        'object_id' => $data['object_id'],
	        'user_type' => $data['user_type'],
	        'user_id' => $data['user_id'],
        );
                	        
        if( $_sub = $this->find('first', array(
	        'fields' => array('id', 'cts'),
	        'conditions' => array(
		        'user_type' => $sub['user_type'],
		        'user_id' => $sub['user_id'],
		        'dataset' => $sub['dataset'],
		        'object_id' => $sub['object_id'],
	        ),
        )) ) {
	    	
	    	$sub['id'] = $_sub['Subscription']['id'];
	    	$sub['cts'] = $_sub['Subscription']['cts'];
	        
	    } else {
		    
	    	$sub['cts'] = date('Y-m-d h:i:j');
		    
	    }
        
        $channels = array();
    	foreach( $data['channel'] as $ch )
    		$channels[] = array(
        		'channel' => $ch,
    		);
    		
    	$queries = array();
    	foreach( $data['qs'] as $q )
    		$queries[] = array(
        		'q' => $q,
    		);
        
		$data = array(
			'Subscription' => $sub,
			'SubscriptionChannel' => $channels,
			'SubscriptionQuery' => $queries,
		);
				
		if( isset($sub['id']) ) {
			$this->query("DELETE FROM `subscription_channels` WHERE `subscription_id`='" . addslashes( $sub['id'] ) . "'");
			$this->query("DELETE FROM `subscription_queries` WHERE `subscription_id`='" . addslashes( $sub['id'] ) . "'");
		}
				
		$return = $this->saveAssociated($data, array('deep' => true));
		return $return;
		
		
		/*
		$_serialize = array('message');
        	        
        
        
        if( isset( $this->request->data['q'] ) && $this->request->data['q'] )
        	$data['q'] = $this->request->data['q'];
        	
        if( isset( $this->request->data['channel'] ) && $this->request->data['channel'] )
        	$data['channel'] = $this->request->data['channel'];
        	
        if( isset( $this->request->data['conditions'] ) && $this->request->data['conditions'] )
        	$data['conditions'] = json_encode( $this->request->data['conditions'] );
        
        
        
        
        if( $sub = $this->Subscription->find('first', array(
	        'conditions' => array(
		        'user_type' => $data['user_type'],
		        'user_id' => $data['user_id'],
		        'hash' => $data['hash'],
	        ),
        )) ) {
	        	
	        	$url = $sub['Subscription']['url'];
	        	$this->set('url', $url);
	        	$_serialize[] = 'url';
	        	$message = 'Already Exists';
	        
        } else {       

        		        			        
	        if ($this->Subscription->save($data)) {
	        	
	        	$data['id'] = $this->Subscription->getInsertID();
	        	$add_data = $this->Subscription->generateData($data);		        	
	        	$data = array_merge($data, $add_data);
	        	$parent_id = $this->Subscription->index($data);
	        	
	        	$this->Subscription->save(array(
		        	'id' => $data['id'],
		        	'url' => $add_data['url'],
		        	'title' => $add_data['title'],
		        	'autotitle' => $add_data['title'],
		        	'parent_id' => $parent_id,
	        	));
	        	
	        	$this->set('url', $add_data['url']);
	        	$_serialize[] = 'url';
	        	
	            $message = 'Saved';
	        
	        } else {
	            $message = 'Error';
	        }
	        
	    }
	    */
	    
    }
    
    public function syncAll() {
	    
	    $ids = $this->find('all', array(
		    'fields' => array('id'),
	    ));
	    
	    foreach( $ids as $id ) {
		    
		    $id = $id['Subscription']['id'];
		    $this->syncById($id);
		    
	    }
	    	    
    }
    
    public function syncById($id) {
	    
	    if( $data = $this->find('first', array(
		    'conditions' => array(
			    'id' => $id,
		    ),
	    )) ) {
		    
		    $this->syncByData($data);
		    
	    }
	    	    
    }
    
    public function syncByData($data = array()) {
	   		   	  
	    if( 
	    	empty($data) || 
	    	!isset($data['Subscription'])
	    )
	    	return false;
	    		    
	    $sub = $data['Subscription'];
	    $channels = array();
	    $queries = array();
	    
	    $db = ConnectionManager::getDataSource('default');
	    
	    if( isset($data['SubscriptionChannel']) ) {
		    foreach( $data['SubscriptionChannel'] as &$ch ) {
		    	
		    	$name = $db->query("SELECT `title` FROM `dataset_channels` WHERE `creator_dataset`='" . $sub['dataset'] . "' AND `channel`='" . $ch['channel'] . "' LIMIT 1");
		    			    	
		    	$ch['qs'] = array();
		    	$ch['channel'] = (int) $ch['channel'];
		    	
		    	if( $name )
			    	$ch['name'] = $name[0]['dataset_channels']['title'];
		    	
		    	$channels[] = (int) $ch['channel'];
		    	
		    }
	    }
	    
	    if( isset($data['SubscriptionQuery']) ) {
		    foreach( $data['SubscriptionQuery'] as &$q ) {
		    	$queries[] = $q['q'];
		    }
	    }
	    
	    
	    $channels = array_unique($channels);
	    $queries = array_unique($queries);
	    		    
	    $ES = ConnectionManager::getDataSource('MPSearch');	    
	    
	    $parent_doc = $ES->API->search(array(
		    'index' => 'mojepanstwo_v1',
		    'type' => 'objects',
		    'body' => array(
			    'query' => array(
				    'bool' => array(
					    'must' => array(
						    array(
							    'term' => array(
								    'dataset' => $sub['dataset'],
							    ),
						    ),
						    array(
							    'term' => array(
								    'id' => $sub['object_id'],
							    ),
						    ),
					    ),
				    ),
			    ),
		    ),
	    ));
	    
	    if( 
	    	( $parent_doc['hits']['total'] === 1 ) && 
	    	( $_id = $parent_doc['hits']['hits'][0]['_id'] )
	    ) {
		    		    	    
		    
		    $db = ConnectionManager::getDataSource('default');
		    $db->query("UPDATE `objects` SET `a`='1', `a_ts`=NOW() WHERE id='" . $_id . "'");
		    	    
		    $params = array();
			$params['index'] = 'mojepanstwo_v1';
			$params['type']  = '.percolator';
			$params['id']    = $sub['id'];
			$params['parent'] = $_id;
			$params['refresh'] = true;
						
			$cts = strtotime( $sub['cts'] );
			$mask = "Y-m-d\TH:i:s\Z";
			
			
			/*
			if(
				isset( $data['conditions'] ) && 
				$data['conditions'] && 
				( $data['conditions'] = json_decode($data['conditions'], true) )
			) {
				$es_conditions = $data['conditions'];
			} else {
				$es_conditions = array();
			}
			*/
			
			$es_conditions = array();
			
			if( $sub['dataset'] == 'zbiory' ) {
				
				
				$_dataset = $db->query("SELECT base_alias FROM datasets WHERE `id`='" . addslashes( $sub['object_id'] ) . "' LIMIT 1");
				
				$es_conditions = array(
					'dataset' => $_dataset[0]['datasets']['base_alias'],
				);
				
				if( isset($data['SubscriptionChannel']) ) {
				    $value = array();
				    foreach( $data['SubscriptionChannel'] as $dch ) {
					    $value[] = (string) $dch['channel'];
				    }
				    $es_conditions['ngo_konkursy.area_id'] = $value;
				}
				
			
			} elseif( $sub['dataset'] == 'aplikacje' ) {
				
				$datasets = $this->datasets[ $parent_doc['hits']['hits'][0]['_source']['data']['aplikacje']['slug'] ];
				$value = array();
				
				foreach( $datasets as $dataset => $dataset_params ) {
					
					if( isset($data['SubscriptionChannel']) && !empty($data['SubscriptionChannel']) ) {
						
						foreach( $data['SubscriptionChannel'] as $ach ) {
						    if( $dataset_params['id'] == $ach['channel'] ) {
							    $value[] = $dataset;
							    break;
						    }
						    
					    }
						
					} else {
						$value[] = $dataset;
					}
					
				}
				
				$es_conditions['dataset'] = $value;
				
				
			} elseif( $sub['dataset'] == 'users_phrases' ) {
								
				$es_conditions = array(
					'q' => $parent_doc['hits']['hits'][0]['_source']['data']['users_phrases']['q'],
				);
									
				if( isset($data['SubscriptionChannel']) ) {
				    $value = array();
				    				    
				    for( $c=0; $c<count($data['SubscriptionChannel']); $c++ ) {
					    
					    $_ch = $data['SubscriptionChannel'][ $c ];
					    $value[] = $this->getDatasetsForApp($_ch['channel']);
					    
				    }
					    
				    $es_conditions['dataset'] = $value;
				}				
								
			} else {
			
				$es_conditions['_feed'] = array (
					'dataset' => $sub['dataset'],
					'object_id' => $sub['object_id'],
				);
			
			}
			
			/*
			if( isset($data['q']) && $data['q'] )
				$es_conditions['q'] = $data['q'];
				
			if( isset($data['channel']) && $data['channel'] )
				$es_conditions['_feed']['channel'] = $data['channel'];
			*/
						
			if( !empty($channels) )
				$es_conditions['_feed']['channel'] = $channels;
			
			if( !empty($queries) )
				$es_conditions['qs'] = $queries;
			
			$es_query = $ES->buildESQuery(array(
				'conditions' => $es_conditions,
			));
												
			$query = isset( $es_query['body']['query']['function_score']['query'] ) ? $es_query['body']['query']['function_score']['query'] : $es_query['body']['query'];
									
			$params['body']  = array(
				'id' => $sub['id'],
				'query' => $query,
				'cts' => date($mask, $cts),
				'user_type' => $sub['user_type'],
				'user_id' => $sub['user_id'],
				'channels' => isset($data['SubscriptionChannel']) ? $data['SubscriptionChannel'] : array(),
				'queries' => $queries,
				'deleted' => false,
			);
										
			/*
			if( isset($data['q']) && $data['q'] )
				$params['body']['q'] = $data['q'];
				
			if( isset($data['channel']) && $data['channel'] )
				$params['body']['channel'] = $data['channel'];
			*/
						
			$ret = $ES->API->index($params);	
			return $_id;	    
		    
		}
	    
    }
    
    public function afterDelete() {
		
		if( $this->data['id'] ) {
			
			$ES = ConnectionManager::getDataSource('MPSearch');
			$deleteParams = array();
			$deleteParams['index'] = 'mojepanstwo_v1';
			$deleteParams['type'] = '.percolator';
			$deleteParams['id'] = $this->data['id'];
			$ret = $ES->API->delete($deleteParams);
						
		}
	
	}
    
    public function generateData($data = array()) {
	    
	    $base = '/dane';
	    
	    if( $data['dataset']=='rady_gmin' ) {
		    
		    $base .= '/gminy/903,krakow/rada';
		    
	    } elseif( $data['dataset']=='urzedy_gmin' ) {

		    $base .= '/gminy/903,krakow/urzad';
	    
	    } else {
	    
		    $base .= '/' . $data['dataset'];
		    $base .= '/' . $data['object_id'];
			
			if( $data['dataset']=='prawo' )
				$base .= '/feed';
			
		}
		
		$title_parts = array();
		
	    $query = array('subscription' => $data['id']);
	    
	    if( isset($data['q']) && $data['q'] ) {
	    	$query['q'] = $data['q'];
	    	$title_parts[] = '"' . $query['q'] . '"';
	    }
	    	
	    if( 
	    	isset($data['channel']) && 
	    	$data['channel'] && 
	    	( $query['channel'] = $data['channel'] )
	    ) {
	    	
	    	App::import('model','Dane.DatasetChannel');
			$DatasetChannel = new DatasetChannel();
			if( $channel = $DatasetChannel->find('first', array(
				'fields' => array(
					'title'
				),
				'conditions' => array(
					'creator_dataset' => $data['dataset'],
					'channel' => $data['channel'],
				),
			)) ) {
				
				$title_parts[] = $channel['DatasetChannel']['title'];
				
			}
			
	    }
	    	
	    if( 
	    	isset($data['conditions']) && 
	    	is_string($data['conditions']) && 
	    	( $query['conditions'] = json_decode($data['conditions'], true) ) 
	    ) {
	    	$title_parts[] = 'Dodatkowe filtry';
	    }
	    	    
	    return array(
	    	'url' => $base . '?' . http_build_query($query),
	    	'title' => empty($title_parts) ? 'Wszystkie dane' : implode(' - ', $title_parts),
	    );
	    
    }
    
    public function transfer_anonymous($anonymous_user_id, $user_id) {
		
		if(
			( $db = ConnectionManager::getDataSource('default') ) && 
			( $where = "user_type='anonymous' AND user_id='" . addslashes( $anonymous_user_id ) . "'" ) && 
			( $subs = $db->query("SELECT id, parent_id FROM subscriptions WHERE $where") ) 
		) {
			
			$ES = ConnectionManager::getDataSource('MPSearch');
						
			foreach( $subs as $sub ) {
			    $ES->API->update(array(
				    'index' => 'mojepanstwo_v1',
				    'type' => '.percolator',
				    'id' => $sub['subscriptions']['id'],
				    'parent' => $sub['subscriptions']['parent_id'],
				    'body' => array(
					    'doc' => array(
						    'user_type' => 'account',
					    	'user_id' => $user_id,
					    ),
				    ),
			    ));
			}
			
			$db->query("UPDATE subscriptions SET `user_type`='account', `user_id`='" . addslashes( $user_id ) . "' WHERE $where");
			
			return true;
			
		} else return false;
		
	}
	
	public function getDatasetsForApp($app_id) {
		
		$datasets = array();
		
		$app_slug = false;
		foreach( $this->apps as $app ) {
			if( $app['id'] == $app_id ) {
				$app_slug = $app['slug'];
				break;
			}
		}
				
		if(
			$app_slug && 
			isset( $this->datasets[$app_slug] )
		) {
			
			foreach( $this->datasets[$app_slug] as $dataset => $params )
				$datasets[] = $dataset;
			
		}
				
		return $datasets;
		
	}

}



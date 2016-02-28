<?
class MPSearch {

    public $cacheSources = true;
    public $description = 'Serwer wyszukania platformy mojePaństwo';
	private $_index = 'mojepanstwo_v1';    

	public $API;
    public $lastResponseStats = null;
    
    public $profile = false;
    public $Aggs = array();
    private $aggs_allowed = array(
	    'date_histogram' => array('field', 'interval', 'format'),
	    'terms' => array('field', 'include', 'exclude', 'size'),
	    'range' => array('field', 'ranges'),
	    'sum' => array('field'),
	    'nested' => array('path'),
	    'aggs' => array(),
	    'global' => array(),
	    'filter' => array('term'),
    );
    
    private $layers_requested = array();
    private $layers = array();
    
    public function query(){
	    return null;
    }
    
    public function getSchemaName()
    {
        return null;
    }
	
    public function __construct($config)
    {

        require_once(APP . 'Vendor' . DS . 'autoload.php');
        $this->API = new Elasticsearch\Client(array(
	    	'hosts' => array(
	    		$config['host'] . ':' . $config['port'],
	    	),
	    ));

    }
    
    public function doc2object($doc) {
				
		$dataset = $doc['fields']['dataset'][0];
		$id = $doc['fields']['id'][0];

		if ($dataset == null or $id == null) {
			throw new InternalErrorException("Empty dataset or id: " . $dataset . ' ' . $id);
		}
		
		$data = array();
		foreach( $doc['_source']['data'] as $k => $v ) {
			
			if( is_array($v) ) {
				
				foreach( $v as $_k => $_v )
					$data[ $k . '.' . $_k ] = $_v;
				
			} else {
				
				$data[ $k ] = $v;
				
			}
			
		}
				
	    $output = array(
			'id' => $id,
			'dataset' => $dataset,
			'url' => Dataobject::apiUrl($dataset, $id),
			'mp_url' => Dataobject::mpUrl($dataset, $id),
			'schema_url' => Dataobject::schemaUrl($dataset),
			'global_id' => $doc['_id'],
    		'slug' => $doc['fields']['slug'][0],
            'score' => $doc['_score'],
            'data' => $data,
    	);
		
		
		if(
    		@$doc['inner_hits']['collection']['hits']['total'] && 
    		@$doc['inner_hits']['collection']['hits']['hits'][0]['_source']
    	) {
	    			    	
	    	$output['collection'] = $doc['inner_hits']['collection']['hits']['hits'][0]['_source'];
	    	$output['collection']['id'] = (int) $doc['inner_hits']['collection']['hits']['hits'][0]['_id'];
	    	
	    }
		

    	if( 
	    	isset( $doc['_source']['static'] ) && 
	    	!empty( $doc['_source']['static'] )
    	) {
	    	
			$output['static'] = $doc['_source']['static'];
	    	
	    }
    
    	
    	if( 
	    	isset( $doc['_source']['contexts'] ) && 
	    	!empty( $doc['_source']['contexts'] )
    	) {
	    	
	    	$force_context = false;
	    	
	    	if(
	    		@$doc['inner_hits']['alert-data']['hits']['total'] && 
	    		isset( $doc['inner_hits']['alert-data']['hits']['hits'][0]['fields']['context'][0] )
	    	) {
		    			    	
		    	$force_context = $doc['inner_hits']['alert-data']['hits']['hits'][0]['fields']['context'][0];
		    	
		    }
		    	    		    	
	    	$context = array();
    		foreach( $doc['_source']['contexts'] as $key => $value ) {
	    		
	    		if( 
		    		!$force_context || 
	    			( $force_context && (strpos($key, $force_context)!==false) )
    			) {
	    		
		    		$key_parts = explode('.', $key);
		    		$value_parts = explode("\n\r", $value);
		    		
		    		$context[] = array(
			    		'creator' => array(
				    		'dataset' => $key_parts[0],
				    		'id' => $key_parts[1],
				    		'global_id' => $value_parts[0],
				    		'name' => $value_parts[1],
				    		'slug' => $value_parts[2],
				    		'url' => @$value_parts[5],
			    		),
			    		'action' => $key_parts[2],
			    		'label' => $value_parts[3],
			    		'sentence' => $value_parts[4],
		    		);
	    		
	    		}
	    		
    		}
    		$output['contexts'] = $context;
    	
    	}
    	
    	if( 
    		isset( $doc['highlight']['text'] ) && 
    		is_array( $doc['highlight']['text'] ) && 
    		isset( $doc['highlight']['text'][0] )
    	)
    		$output['highlight'] = array($doc['highlight']['text']);
    	
    	if(
    		isset($doc['inner_hits']) && 
    		isset($doc['inner_hits']['inner']) && 
    		isset($doc['inner_hits']['inner']['hits']) && 
    		isset($doc['inner_hits']['inner']['hits']['hits']) 
    	) {
	    	
	    	foreach( $doc['inner_hits']['inner']['hits']['hits'] as $hit ) {
		    	
		    	$output['inner_hits'][] = array(
			    	'id' => $hit['_id'],
			    	'title' => @$hit['fields']['title'][0],
		    	);
		    	
	    	}	    	
    	}
    	
    	return $output;
	    
    }	
	
	public function buildESQuery( $queryData = array() ) {
					
		if( !isset($queryData['conditions']) )
			$queryData['conditions'] = array();
		
		if( !isset($queryData['page']) )
			$queryData['page'] = 1;
			
		if( !isset($queryData['limit']) )
			$queryData['limit'] = 50;
			
		if( isset($queryData['layers']) )
			$this->layers_requested = $queryData['layers'];
		
		// debug( $this->layers_requested );
		
		$from = ( $queryData['page'] - 1 ) * $queryData['limit'];
		$size = $queryData['limit'];
		$_type = isset( $queryData['_type'] ) ? $queryData['_type'] : 'objects';
				
		$params = array(
			'index' => $this->_index,
			'type' => $_type,
			'body' => array(
				'from' => $from, 
				'size' => $size,
				'query' => array(),
				'sort' => array(
					array(
						'date' => 'desc',
					),
				),
			),
		);
		
		if( !isset( $queryData['_type']) || ($queryData['_type']=='objects') ) {
			
			$params['body'] = array_merge($params['body'], array(
				'fields' => array('dataset', 'id', 'slug'),
				'_source' => array('data', 'static'),
			));
			
			$fields_prefix = 'data.';
			
		} else {
			
			$fields_prefix = '';
			
		}
		
		$_fields_prefix = false;		
		$has_order = false;
		
		if( isset($queryData['order']) && is_array($queryData['order']) ) {
			
			
			$sort = array();
			foreach( $queryData['order'] as $os) {
								
				if( is_array($os) ) {
					foreach( $os as $o ) {
						
						$has_order = true;
						$parts = explode(' ', $o);
						$partsCount = count( $parts );
						
						$field = false;
						$direction = 'desc';
						
						if( $partsCount===1 )
							$field = $o;
						elseif( $partsCount===2 )
							list($field, $direction) = $parts;
						
						if( $field ) {
							
							if( $field=='weight' ) {
								
								$field = 'weights.main.score';
								
							} elseif( $field=='_title' ) {
								
								$field = 'title.raw';
								
							}
							
							$prefix = in_array($field, array('date', 'dataset')) ? '' : 'data.';
							
							if( isset( $queryData['_type']) && ($queryData['_type']=='letters') ) 
								$prefix = '';
							
							$sort[] = array(
								$prefix.$field => $direction,
							);
							
						}
						
					}
				}
			}
						
			if( !empty($sort) )
				$params['body']['sort'] = $sort;
			
		}
		
		
		
		
		// FILTERS
					
		$and_filters = array();
        
        if( !isset( $queryData['_type']) || ($queryData['_type']=='objects') ) {    
	        if(
	        	(
	        		!isset($queryData['conditions']['dataset']) || 
					empty($queryData['conditions']['dataset'])
				) &&
	        	(
	        		!isset($queryData['conditions']['_feed']) || 
		        	empty($queryData['conditions']['_feed']) 
		        ) &&
	        	(
	        		!isset($queryData['conditions']['collection_id']) || 
		        	empty($queryData['conditions']['collection_id']) 
		        )
	        ) {
		        
		        $and_filters[] = array(
		    		'term' => array(
		    			'weights.main.enabled' => true,
		    			'_cache' => true
		    		),
		    	);
		    	
	    	}
    	}
        
        // debug($queryData['conditions']);
        
        foreach( $queryData['conditions'] as $key => $value ) {
        	
        	
        	if( $key[0]=='-' ) {
	        	
	        	$_fields_prefix = $fields_prefix;
	        	$fields_prefix = '';
	        	$key = substr($key, 1);
	        	
        	}
        	
        	
        	$operator = '=';
        	if( $key_length = strlen($key) ) {
	        	
	        	if( @substr($key, -2) === '!=' ) {
	        	
		        	$operator = '!=';
		        	$key = @substr($key, 0, $key_length-2);
	        	
	        	} elseif( @substr($key, -1) === '>' ) {
		        	
		        	$operator = '>';
		        	$key = @substr($key, 0, $key_length-1);
		        			        	
	        	} elseif( @substr($key, -1) === '<' ) {
		        	
		        	$operator = '<';
		        	$key = @substr($key, 0, $key_length-1);
		        			        	
	        	}
	        	
        	}
        	 
        	if( in_array($key, array('dataset', 'id')) ) {
        		
        		if( ($key=='dataset') && is_array($value) && !empty($value) ) {
	        		
	        		$ors = array();
	        		foreach( $value as $v ) {
		        		
		        		if( preg_match('/^(.*?)\{(.*?)\:(.*?)\}$/', $v, $match) ) {
			        		
			        		$ors[] = array(
				        		'bool' => array(
					        		'must' => array(
						        		array(
							        		'term' => array(
								        		$key => $match[1],
							        		),
						        		),
						        		array(
							        		'term' => array(
								        		'data.' . $match[2] => $match[3],
							        		),
						        		),
					        		),
				        		),
			        		);
			        		
		        		} else {
		        		
			        		$ors[] = array(
				        		'term' => array(
					        		$key => $v,
				        		),
			        		);
		        		
		        		}
		        		
	        		}
	        			        		
	        		$and_filters[] = array(
		        		'or' => $ors,
	        		);
	        		
	        	} else {
	        		        		
	        		$filter_type = is_array($value) ? 'terms' : 'term';
	        		$and_filters[] = array(
		        		$filter_type => array(
		        			$key => $value,
		        		),
		        	);
	        	
	        	}
	        
	        } elseif( $key[0]=='>' ) {
	        	
	        	$_path = substr($key, 1);
	        	$_must = array();
	        	
	        	if( is_array($value) ) {
		        	foreach( $value as $_k => $_v ) {
			        	$_must[] = array(
				        	'term' => array(
					        	$_path . '.' . $_k => $_v,
				        	),
			        	);
		        	}
	        	}
	        	
	        	if( !empty($_must) ) {
		        	$and_filters[] = array(
			        	'nested' => array(
				        	'path' => $_path,
				        	'filter' => array(
					        	'bool' => array(
						        	'must' => $_must
					        	),
				        	),
			        	),
		        	);
	        	}
	        	
	        } elseif( $key == 'geohash' ) {
		        
		        $and_filters[] = array(
	        		'term' => array(
		        		'position.geohash' => $value,
	        		),
	        	);
        	        		        		
        	} elseif( $key == 'q' ) {
				
				if( $value ) {
					
					$params['body']['query'] = array(
						'function_score' => array(
			        		'query' => array(
				        		'filtered' => array(
					        		'query' => array(
						        		'multi_match' => array(
							        		'query' => mb_convert_encoding($value, 'UTF-8', 'UTF-8'),
								        	'fields' => array('title', 'text', 'acronym'),
								        	'cutoff_frequency' => 0.001,
										    'type' => "phrase",
											'slop' => 5,
						        		),
					        		),
				        		),
			        		),
			        		'field_value_factor' => array(
								'field' => 'weights.main.score'
					        ),
			        	),
					);
		        	
		        	if( !$has_order )
			        	unset( $params['body']['sort'] );
	        	
	        	}
	        	
	        } elseif( $key == 'qs' ) {
		        
		        if( $value ) {
			        
			        if( !is_array($value) )
			        	$value = array( $value );
			        
			        $should = array();
			        $highlight_query = array();
			        
			        foreach( $value as $v ) {
			        	
			        	$_v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
			        	
			        	$should[] = array(
				        	'query' => array(
						        'multi_match' => array(
									'query' => $_v,
						        	'fields' => array('title', 'text', 'acronym'),
								    // 'type' => "phrase",
								    // 'fields' => array('title', 'title.suggest', 'acronym', 'text'),
									// 'slop' => 5,
						        ),
					        ),
			        	);
			        	
			        	$highlight_query[] = array(
		    				'match' => array(
			    				'text' => array(
				    				"query" => $_v,
                                    // "phrase_slop" => 5,
                                    // "boost" => 10,
			    				),
		    				),
			        	);
			        	
			        }
			        
			        $and_filters[] = array(
				        'bool' => array(
					        'should' => $should,
				        ),
			        );
			        				        
		        }
        	
        	
        	} elseif( $key == 'collection_id' ) {
	        	
	        	$and_filters[] = array(
		        	'has_child' => array(
			        	'type' => 'collections-objects',
			        	'query' => array(
				        	'bool' => array(
					        	'must' => array(
						        	array(
							        	'term' => array(
								        	'collection_id' => $value
							        	),
						        	),
					        	),
				        	),
			        	),
			        	'inner_hits' => array(
		        			'name' => 'collection',
		        			'_source' => true,
		        			'size' => '1',
		        			/*
		        			'sort' => array(
			        			'collections-objects.id' => array(
				        			'order' => 'desc',
			        			),
		        			),
		        			*/
			        		// 'fielddata_fields' => array('collections-objects.collection_id'),
		        		),
		        	),
	        	);
        	
        	} elseif( ($key == 'user-pages') && $value ) {
												
	        	$and_filters[] = array(
		        	'has_child' => array(
	        			'type' => 'objects-pages',
	        			'filter' => array(
		        			'term' => array(
			        			'user_id' => $value['user_id'],
		        			),	        			
	        			),
	        			/*
	        			'inner_hits' => array(
		        			'name' => 'pages',
			        		'fields' => array('objects-pages.users'),
			        	),
			        	*/
        			),
	        	);
	        	
	        								

	        	        	
        	} elseif( $key == '_object' ) {
				
				if( $value && ($parts = explode('.', $value)) && (count($parts)>1) ) {
						
					$and_filters[] = array(
						'nested' => array(
							'path' => 'dataobjects',
							'filter' => array(
								'bool' => array(
									'must' => array(
										array(
											'term' => array('dataobjects.dataset' => $parts[0]),
										),
										array(
											'term' => array('dataobjects.object_id' => $parts[1]),
										),
									),
								),
							),
						),
					);
	        	
	        	}        	
        	
        	} elseif( $key == 'feeds_channels' ) {
        		        		
        		$and_filters[] = array(
	        		'nested' => array(
	        			'path' => 'feeds_channels',
	        			'filter' => array(
		        			'and' => arraY(
			        			'filters' => array(
				        			array(
					        			'term' => array(
						        			'feeds_channels.dataset' => $value['dataset'],
					        			)
				        			),
				        			array(
					        			'term' => array(
						        			'feeds_channels.object_id' => $value['object_id'],
					        			)
				        			),
				        			array(
					        			'term' => array(
						        			'feeds_channels.channel' => $value['channel'],
					        			)
				        			),
			        			),
		        			),
	        			),
        			),
	        	);
	        	        	        	
        	} elseif( $key == '_feed' ) {
        		        		
        		if(
	        		isset($value['user_type']) && 
        			isset($value['user_id']) && 
        			is_numeric($value['user_id'])
        		) {
	        		
	        		$and_filters[] = array(
		        		'range' => array(
			        		'date' => array(
				        		'lte' => 'now',
			        		),
		        		),
	        		);
	        		
	        		$and_filters[] = array(
	        			'has_child' => array(
		        			'type' => 'objects-alerts',
		        			'filter' => array(
			        			'and' => arraY(
				        			'filters' => array(
					        			array(
						        			'term' => array(
							        			'user_type' => $value['user_type'],
						        			),
					        			),
					        			array(
						        			'term' => array(
							        			'user_id' => $value['user_id'],
						        			),
					        			),
				        			),
			        			),
		        			),
		        			'inner_hits' => array(
			        			'name' => 'alert-data',
				        		'fields' => array('objects-alerts.sub_id', 'objects-alerts.context', 'objects-alerts.read', 'objects-alerts.created'),
			        		),
	        			),
        			);
        			
        			
        			$params['body']['_source'] = array('data', 'static', 'contexts.*');
        			// $params['body']['partial_fields']['source']['include'][] = 'contexts.*';
	        		
	        		
        		} elseif (
        			isset($value['dataset']) && 
        			isset($value['object_id']) && 
        			is_numeric($value['object_id'])
        		) {
	        		
	        		$_and_filters = array(
	        			array(
	        				'term' => array(
		        				'feeds_channels.dataset' => $value['dataset'],
		        			),
	        			),
	        			array(
	        				'term' => array(
		        				'feeds_channels.object_id' => (int) $value['object_id'],
		        			),
	        			),
        			);
        			        			
        			if (
	        			isset($value['channel']) && 
	        			$value['channel'] 
	        		) {
	        			
	        			if( is_numeric($value['channel']) ) {
	        			
		        			$_and_filters[] = array(
			        			'term' => array(
				        			'feeds_channels.channel' => $value['channel'],
			        			),
		        			);
	        			
	        			} elseif( is_array($value['channel']) ) {
		        			
		        			$_and_filters[] = array(
			        			'terms' => array(
				        			'feeds_channels.channel' => $value['channel'],
			        			),
		        			);
		        			
	        			}
	        		
	        		}
	        		
        			$and_filters[] = array(
	        			'nested' => array(
		        			'path' => 'feeds_channels',
		        			'filter' => array(
			        			'and' => arraY(
				        			'filters' => $_and_filters,
			        			),
		        			),
	        			),
        			);
        			
        			$params['body']['_source'] = array('data', 'static', 'contexts.' . $value['dataset'] . '.' . $value['object_id'] . '.*');
		        	// $params['body']['partial_fields']['source']['include'][] = 'contexts.' . $value['dataset'] . '.' . $value['object_id'] . '.*';
		        	
		        	if( $value['dataset']=='rady_druki' ) {
		        	
			        	$params['body']['sort'] = array(
				        	'date' => 'asc',
				        	'feed_dataset_order.rady_druki' => 'asc',
			        	);
		        	
		        	}
		        	
		        	// unset( $params['body']['sort'] );
	        			        			
        		}	        	
	        	
	        } elseif( in_array($key, array('date', '_date')) ) {
	        		        		        	
	        	$_value = strtoupper($value);	        	
	        	
	        	$key = 'date';
	        	
	        	if( ($_value == 'LAST_24H') || ($_value == '1D') ) {   		
						
					$range = array(
						'gte' => 'now-1d',
					);
					
					$and_filters[] = array(
						'range' => array(
							'date' => $range,
						),
					);
				
				} elseif( ($_value == 'LAST_1D') || ($_value == '1D') ) {   		
					
					$range = array(
						'gte' => 'now-1d',
					);
					
					$and_filters[] = array(
						'range' => array(
							$key => $range,
						),
					);
				
				} elseif( ($_value == 'LAST_3D') || ($_value == '3D') ) {   		
					
					$range = array(
						'gte' => 'now-3d',
					);
					
					$and_filters[] = array(
						'range' => array(
							$key => $range,
						),
					);
				
				} elseif( ($_value == 'LAST_7D') || ($_value == '1W') ) {   		
					
					$range = array(
						'gte' => 'now-7d',
					);
					
					$and_filters[] = array(
						'range' => array(
							$key => $range,
						),
					);
				
				} elseif( ($_value == 'LAST_1M') || ($_value == '1M') ) {   		
					
					$range = array(
						'gte' => 'now-1M',
					);
					
					$and_filters[] = array(
						'range' => array(
							$key => $range,
						),
					);
				
				} elseif( ($_value == 'LAST_1Y') || ($_value == '1Y') ) {   		
					
					$range = array(
						'gte' => 'now-1y',
					);
					
					$and_filters[] = array(
						'range' => array(
							$key => $range,
						),
					);
				
				} elseif( preg_match('^\[(.*?) TO (.*?)\]^i', $_value, $match) ) {
					
					$range = array();
					
					if( ($gte = $this->formatDate( $match[1] )) && ($gte != '*') )
						$range['gte'] = strtolower( $gte );
					if( ($lte = $this->formatDate( $match[2] )) && ($lte != '*') )
						$range['lte'] = strtolower( $lte );

					
					$and_filters[] = array(
						'range' => array(
							$key => $range,
						),
					);
					
				} else {
					
					$and_filters[] = array(
		        		'term' => array(
		        			$key => $value,
		        		),
		        	);
		        	
		        }
        	
        	} elseif( $key == 'subscribtions' ) {
	        	
	        	$and_filters[] = array(
	        		'has_child' => array(
	        			'type' => '.percolator',
	        			'filter' => array(
		        			'and' => array(
			        			'filters' => array(
				        			array(
					        			'term' => array(
						        			'user_type' => $value['user_type'],
					        			),
				        			),
				        			array(
					        			'term' => array(
						        			'user_id' => $value['user_id'],
					        			),
				        			),
			        			),
		        			),
	        			),
	        			'inner_hits' => array(
		        			'name' => 'inner',
			        		'fields' => array('id', 'title', 'url'),
		        		),
	        		),
	        	);
	        	
	        } elseif( $key=='OR' ) {
		        
		        $ors = array();
		        
		        foreach( $value as $k => $v ) {
			        
			        if( !is_array($v) )
			        	$v = array($v);
			        
			        $ors[] = array(
				        'terms' => array(
					        $k => $v,
				        ),
			        );
			        
		        }
		        		        
		        $and_filters[] = array(
			        'or' => $ors,
		        );
        	
        	} else {
	        	
	        	
	        	if( 
		        	is_string($value) && 
	        		preg_match('^\[(.*?)(\s*)TO(\s*)(.*?)\]^i', $value, $match)
	        	) {
		        			        	
		        	$range = array();
					
					if( $match[1]!=='' )
						$range['gte'] = $match[1];
					if( $match[4]!=='' )
						$range['lte'] = $match[4];
					
					$and_filters[] = array(
						'range' => array(
							$fields_prefix . $key => $range,
						),
					);
							        	
	        	} else {
	        		
	        		if( 
	        			( $nested_parts = explode(':', $key) ) && 
	        			( count($nested_parts) > 1 ) 
	        		) {
		        		
		        		$and_filters[] = array(
			        		'nested' => array(
				        		'path' => $nested_parts[0],
				        		'filter' => array(
					        		'term' => array(
						        		$nested_parts[1] => $value,
					        		),
				        		),
			        		),
		        		);
		        		
	        		} else {
	        			        		
		        		if( $operator==='=' ) {
			        		 	
			        		$and_filters[] = array(
					        	'term' => array(
						        	$fields_prefix . $key => $value,
					        	),
				        	);
			        	
			        	} elseif( $operator==='!=' ) {
				        	
				        	$and_filters[] = array(
					        	'not' => array(
						        	'term' => array(
							        	$fields_prefix . $key => $value,
						        	),
					        	),
				        	);
				        	
			        	} elseif( $operator==='>' ) {
				        	
				        	$and_filters[] = array(
					        	'range' => array(
						        	$fields_prefix . $key => array(
							        	'gt' => $value,
						        	),
					        	),
				        	);
				        	
			        	} elseif( $operator==='<' ) {
				        	
				        	$and_filters[] = array(
					        	'range' => array(
						        	$fields_prefix . $key => array(
							        	'lt' => $value,
						        	),
					        	),
				        	);
				        	
			        	}
		        	
		        	}
	        	
	        	}
	        	
        	}
        	
        	if( $_fields_prefix!==false ) {
	        	$fields_prefix = $_fields_prefix;
	        	$_fields_prefix = false;
        	}

        }
		
		
		// var_export( $queryData ); die();
		
		if( 
			isset( $queryData['aggs'] ) || 
			!empty( $this->layers_requested )
		) {
			if (isset( $queryData['aggs'] ) && !is_array($queryData['aggs'])) {
				throw new BadRequestException();
			}
			
			$aggs = array();
			$es_aggs = array();
												
			if( isset($queryData['aggs']) ) {
				
				foreach( $queryData['aggs'] as $agg_id => $agg_data ) {
					
					if( is_array($agg_data) ) {
						array_walk_recursive($agg_data, function(&$value, $key){
							if( 
								$key && 
								is_string($key) && 
								in_array($key, array('size', 'precision')) 
							) {
								$value = (int) $value;
							}
						});
					}
					
					if( 
						( $agg_id === '_channels' ) && 
						isset( $queryData['conditions']['_feed'] )
					) {
															
						$aggs['global'] = array(
		                    'feed_data' => array(
	                            'nested' => array(
	                                'path' => 'feeds_channels',
	                            ),
	                            'aggs' => array(
	                                'feed' => array(
	                                    'filter' => array(
	                                        'and' => array(
	                                            'filters' => array(
	                                                array(
	                                                    'term' => array(
	                                                        'feeds_channels.dataset' => $queryData['conditions']['_feed']['dataset'],
	                                                    ),
	                                                ),
	                                                array(
	                                                    'term' => array(
	                                                        'feeds_channels.object_id' => $queryData['conditions']['_feed']['object_id'],
	                                                    ),
	                                                )
	                                            ),
	                                        ),
	                                    ),
	                                    'aggs' => array(
	                                        'channel' => array(
	                                            'terms' => array(
	                                                'field' => 'feeds_channels.channel',
	                                                'size' => 100,
	                                            ),
	                                        ),
	                                    ),
	                                ),
	                            ),
	                        ),
		                );
		                
		                $this->Aggs['feed_data'] = array();
		                
		            } elseif( $agg_id=='subscribtions' ) {
		            	
		            	$aggs['global'] = array(
		                    'subscribtions' => array(
	                            
	                            'filter' => array(
		                            'has_child' => array(
					        			'type' => '.percolator',
					        			'filter' => array(
						        			'and' => array(
							        			'filters' => array(
								        			array(
									        			'term' => array(
										        			'user_type' => $value['user_type'],
									        			),
								        			),
								        			array(
									        			'term' => array(
										        			'user_id' => $value['user_id'],
									        			),
								        			),
							        			),
						        			),
					        			),
					        			'inner_hits' => array(
						        			'name' => 'inner',
							        		'fields' => array('id', 'title', 'url'),
						        		),
					        		),
	                            ),
	                            'aggs' => new \stdClass(),
	                        ),
		                );
		                
		                $this->Aggs['subscribtions'] = array();
		            	
					} else {
						
						if (!is_array($agg_data)) {
							throw new BadRequestException();
						}
						
						array_walk_recursive($agg_data, function(&$item, $key){
							if( $item === '_empty' )
								$item = new \stdClass();
						});
						
						$scope = 'results';
											
						if( array_key_exists('scope', $agg_data) ) {
							$scope = $agg_data['scope'];
							unset( $agg_data['scope'] );
						}
						
						$filters_excludes = false;
						if( strpos($scope, 'filters_exclude(')===0 ) {
							
							$filters_excludes = substr($scope, 16, strlen($scope)-17);
							$scope = 'filters_exclude';
							
						}
						
						
						foreach( $agg_data as $agg_type => $agg_params ) {
							
							
																							
							$this->Aggs[ $agg_id ][ $agg_type ] = $agg_params;
							$es_params = array();
														
							foreach( $agg_params as $key => $value ) {
										
								if( 
									( $key == 'field' ) && 
									!in_array($value, array('date', 'dataset'))
								)
									$value = $fields_prefix . $value;
								
								$es_params[ $key ] = $value;
								
							}
													
							if( !empty($es_params) )
								$aggs[ $scope ][ $agg_id ][ $agg_type ] = $es_params;
													
						}
						
						if(
							$filters_excludes && 
							isset( $aggs['filters_exclude'][$agg_id] )
						)
							$aggs['filters_exclude'][$agg_id]['filters_exclude'] = $filters_excludes;
					
					}
				}
			
			
			
			
				$es_aggs = array();
				
				// debug($aggs); die();
					
				if( 
					array_key_exists('global', $aggs) || 
					array_key_exists('query', $aggs) || 
					array_key_exists('filters_exclude', $aggs) || 
					array_key_exists('query_main', $aggs) 
				) {
					
					$es_aggs['__global'] = array(
						'global' => new \stdClass(),
						'aggs' => array(),
					);
					
					if( array_key_exists('global', $aggs) )
						$es_aggs['__global']['aggs'] = $aggs['global'];
					
				}
							
	
				
				if( 
					array_key_exists('query', $aggs) || 
					array_key_exists('filters_exclude', $aggs) || 
					array_key_exists('query_main', $aggs) 
				) {
										
					if( isset($params['body']['query']['function_score']['query']['filtered']['query']) )
						$filter = array(
							'query' => $params['body']['query']['function_score']['query']['filtered']['query'],
						);
					else
						$filter = array(
							'match_all' => new \stdClass(),
						);
					
					$es_aggs['__global']['aggs']['__query'] = array(
						'filter' => $filter,
						'aggs' => array(),
					);
					
					if( array_key_exists('query', $aggs) )
						$es_aggs['__global']['aggs']['__query']['aggs'] = $aggs['query'];
						
					if( array_key_exists('filters_exclude', $aggs) ) {
											
						foreach( $aggs['filters_exclude'] as $_k => $_v ) {
							
							$filters_excludes = $_v['filters_exclude'];
							unset( $_v['filters_exclude'] );
							
							$_and_filters = array();
							foreach( $and_filters as $f )
								if( 
									isset( $f['term'] ) && 
									( $keys = array_keys($f['term']) ) && 
									( $key = array_shift($keys) ) && 
									(
										( $key == $fields_prefix . $filters_excludes )
									)
								) {} else {
									$_and_filters[] = $f;
								}
																			
							$es_aggs['__global']['aggs']['__query']['aggs']['__filters_exclude']['aggs'][$_k] = array(
								'filter' => array(
									'bool' => array(
										'must' => $_and_filters,
									),
								),
								'aggs' => array(
									$_k => $_v,
								),
							);
							
						}
						
						$es_aggs['__global']['aggs']['__query']['aggs']['__filters_exclude']['filter']['match_all'] = new \stdClass();
					}
					
				}
				
			}
			
			
			if( array_key_exists('query_main', $aggs) ) {
				
				$es_aggs['__global']['aggs']['__query']['aggs']['__query_main'] = array(
					'filter' => array(
						'term' => array(
			    			'weights.main.enabled' => true,
			    		),
					),
					'aggs' => $aggs['query_main'],
				);
								
			}
			
			
			if( array_key_exists('results', $aggs) ) {
				$es_aggs = array_merge($es_aggs, $aggs['results']);
			}
						
			
			if( 
				!empty( $this->layers_requested ) && 
				in_array('page', $this->layers_requested)
			) {
				
				$es_aggs['_page'] = array(
					'children' => array(
						'type' => 'objects-pages',
					),
					'aggs' => array(
						'page' => array(
							'top_hits' => array(
								'size' => 1,
								'_source' => array(
		                            'include' => '*',
		                        ),
							),
						),
					),
				);
				
				$this->Aggs['_page'] = array();

				
			}
								
			if( !empty($es_aggs) ) {
				$params['body']['aggs'] = $es_aggs;
			}
										
		}
		
		
		if( isset($params['body']['query']['function_score']) ) {
			
			$params['body']['query']['function_score']['query']['filtered']['filter'] = array(
				'bool' => array(
					'must' => $and_filters,
				),
			);
			
		} else {
			
			$params['body']['query'] = array(
				'filtered' => array(
					'filter' => array(
						'bool' => array(
							'must' => $and_filters,
						),
					),
				),
			);
			
		}
		
						
		
		if( 
			isset($queryData['highlight']) && 
			$queryData['highlight'] && 
			isset($queryData['conditions']) && 
			$queryData['conditions']
		) {
			
			$params['body']['highlight'] = array(
	    		'fields' => array(
	    			'text' => array(
	    				'index_options' => 'offsets',
	    				'number_of_fragments' => 1,
	    				'fragment_size' => 200,
	    			),
	    		),
	    		
	    	);
			
		}
		
		if( isset($highlight_query) && $highlight_query ) {
			$params['body']['highlight'] = array(
	    		'fields' => array(
	    			'text' => array(
	    				'index_options' => 'offsets',
	    				'number_of_fragments' => 1,
	    				'fragment_size' => 200,
	    				'highlight_query' => array(
		    				'bool' => array(
			    				'should' => $highlight_query,
		    				),
	    				),
	    			),
	    		),
	    	);
    	}
		
		return $params;
		
	}
	
	public function suggest($q, $options = array()) {
				
		$params = array(
			'index' => $this->_index,
			'body' => array(
				'suggest' => array(
					'text' => $q,
					'completion' => array(
						'field' => 'suggest_v7',
						'fuzzy' => array(
			                'fuzziness' => 0,
			            ),
						'context' => array(
							'dataset' => '*',
						),
					),
				),
			),
		);
		
		if( isset($options['dataset']) )
			$params['body']['suggest']['completion']['context']['dataset'] = $options['dataset'];
						
		$response = $this->API->suggest($params);
		return $response['suggest'][0];
		
	}
	
    public function read(Model $model, $queryData = array()) {
		
		$params = $this->buildESQuery($queryData);
		// $params['body']['profile'] = true;
		
		// echo "\n\n\nQUERY= "; var_export( $params ); echo "\nEND\n"; die();
		
		$this->lastResponseStats = null;
		$response = $this->API->search( $params );

		// echo "\n\n\nRESPONSE= "; var_export($response); die();
		
		if (isset($response['profile'])) {
			$this->profile = $response['profile'];
		}
		
		$this->lastResponseStats = array();
		if (isset($response['hits']['total'])) {
			$this->lastResponseStats['count'] = $response['hits']['total'];
		}
		if (isset($response['took'])) {
			$this->lastResponseStats['took_ms'] = $response['took'];
		}
        
        // var_export( $response['aggregations'] ); die();
        // var_export( $this->Aggs );
                
        if( !empty($this->layers_requested) ) {
	        
	        if( $page = @$response['aggregations']['__global']['_page']['page']['hits']['hits'][0]['_source'] ) {
		        
		        $this->layers['page'] = $page;
		        
	        }
	        
        }
        
        if(
        	!empty($this->Aggs) && 
        	isset( $response['aggregations'] )
        ) {
	        	        
	        $aggs = array();
	        $_aggs = $response['aggregations'];
	        
	        // debug($_aggs);
	        
	        // copying results aggs
	        
	        if( $temp = @$_aggs['__global'] )
		        unset( $_aggs['__global'] );
	        $aggs = array_merge($aggs, $_aggs);
	        
	        if( $_aggs = $temp ) {
		        
		        // copying global aggs
		        
				if( $temp = @$_aggs['__query'] )
			        unset( $_aggs['__query'] );			        			    
		        $aggs = array_merge($aggs, $_aggs);
		        
		        if( $_aggs = $temp ) {
			        			        
			        // copying query aggs
		        
					if( $temp_main = @$_aggs['__query_main'] )
				        unset( $_aggs['__query_main'] );
				        
				    if( $temp_filters = @$_aggs['__filters_exclude'] )
				        unset( $_aggs['__filters_exclude'] );
				    
			        $aggs = array_merge($aggs, $_aggs);
			        
			        if( $_aggs = $temp_main ) {
				    	
				    	// copying query_main aggs
		        
				        $aggs = array_merge($aggs, $_aggs);
				    	   			        
				    }
				    
				    if( $_aggs = $temp_filters ) {
				    	
				    	// copying filters aggs
		        
				        unset( $_aggs['doc_count'] );
				        foreach( $_aggs as $k => $v )
				        	$aggs[ $k ] = $v[ $k ];
				        				    	   			        
				    }
				    			        			        
			    }
		        
		    }
		    
		    unset( $aggs['doc_count'] );
	        	        
	        foreach( $this->Aggs as $agg_id => &$agg_data ) {
		    	if( isset($aggs[$agg_id]) ) {
		        	$agg_data = $aggs[$agg_id];
		        }
		    }
		    		 			 	       			        				        			        
        }
                         
        $hits = $response['hits']['hits'];
        
        if( !isset( $queryData['_type']) || ($queryData['_type']=='objects') ) {
	        for( $h=0; $h<count($hits); $h++ ) 
	        	$hits[$h] = $this->doc2object( $hits[$h] );
        }
        return $hits;        

    }

	private function formatDate( $inp ) {
		
		if( $inp == '*' ) {
			return false;
		} elseif( in_array($inp, array('NOW/DAY')) ) {
			return 'now';
		} else {
			return $inp;
		}
		
	}

}
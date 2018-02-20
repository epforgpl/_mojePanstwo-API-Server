<?

class Dataset extends AppModel
{

    public $useTable = 'datasets';
    public $belongsTo = array(
        'Datachannel' => array(
            'className' => 'Dane.Datachannel',
            'foreignKey' => 'channel_id',
        ),
		'App' => array(
            'className' => 'Application',
            'foreignKey' => 'app_id',
        ),
    );
    public $hasMany = array(
        'Layer' => array(
            'className' => 'Dane.Layer',
        )
    );

    public $actsAs = array('Containable');
    public $virtualFields = array(
        'alias' => 'base_alias',
        'class' => 'SUBSTRING(results_class, 4)',
    );

    public function find($type = 'first', $queryData = array())
    {
				
		if( 
			( $type == 'first' ) && 
			( ($alias = @$queryData['conditions']['Dataset.alias']) || ($alias = @$queryData['conditions']['Dataset.base_alias']) ) 
		) {
			
			App::import('model', 'MPCache');
	        $this->MPCache = new MPCache();
	 	    
	 	    $output = $this->MPCache->getDataset( $alias, @$queryData['full'] );
	 	    	 	    
            if (!empty($output)) {
	 	        $output['orders'] = isset($output['orders_es']) ? $output['orders_es'] : array();
            }
	 	    
	 	    if( !empty( $output['filters'] ) )
		 	    foreach( $output['filters'] as &$f )
		 	    	if( strpos($f['filter']['field'], '.')===false )
		 	    		$f['filter']['field'] = $alias . '.' . $f['filter']['field'];
	 	    
			return $output;
			
		}
		
        $fields = array();
        if (isset($queryData['fields']))
            $fields = $queryData['fields'];
        else {
            $fields = array(
                'Dataset.id',
                'Dataset.alias',
                'Dataset.name',
                'Dataset.class',
                'Dataset.count',
                'Dataset.channel_id',
                'Dataset.default_sort',
                'Datachannel.id',
                'Datachannel.nazwa',
                'Datachannel.slug',
                'App.id',
                'App.name',
                'App.plugin',
                'App.slug',
            );
        }

        $queryData = array_merge_recursive(array(
            'fields' => $fields,
            // 'order' => array('Dataset.ord' => 'asc'),
            'limit' => 100,
        ), $queryData);

        return parent::find($type, $queryData);
    }

    public function getFilters($alias, $full = true, $exclude_alias = null)
    {

        $fields = array('`filter`.`field`', '`filter`.`typ_id`');

        if ($full)
            $fields = array_merge($fields, array('`filter`.`parent_field`', '`filter`.`label`', '`filter`.`desc`'));


        // $fq = "CONCAT( `field`.`alias` , '.', `field`.`field` )";
        $q = "SELECT " . implode(", ", $fields) . " 
        FROM `datasets_filters` AS filter 
        WHERE `filter`.`dataset`='" . addslashes($alias) . "'";

        $q .= " AND `filter`.`deleted`='0'";

        $q .= " ORDER BY `filter`.`ord` ASC 
        LIMIT 100";

        $result = $this->query($q);

        if (!is_null($exclude_alias)) {
            $exclude = $this->query("SELECT field FROM datasets_filters_datasets WHERE dataset = '$alias' and perspective = '$exclude_alias'");
            $excludefields = array();

            foreach ($exclude as $field)
                array_push($excludefields, $field['datasets_filters_datasets']['field']);

            foreach ($result as $key => $val)
                if (in_array($val['filter']['field'], $excludefields))
                    unset($result[$key]);
        }


        return $result;

    }

    public function getSwitchers($alias, $full = true, $exclude = null)
    {

        $fields = array('`name`', '`label`', '`dataset_search_default`');

        if ($full)
            $fields = array_merge($fields, array('`expression`'));

        return $this->query("SELECT " . implode(", ", $fields) . " 
	    	FROM `datasets_switchers` AS `switcher`
	    	WHERE `dataset`='" . addslashes($alias) . "'
	    	ORDER BY `ord` ASC 
	    	LIMIT 100");

    }
    
    public function getMap($alias, $page)
    {
		
		$size = 10000;
		$from = ($page-1) * $size;
				
		App::Import('ConnectionManager');
		$MPSearch = ConnectionManager::getDataSource('MPSearch');
		
        $response = $MPSearch->search(array(
			"size" => $size,
			"from" => $from, 
			"query" => array(
				"bool" => array(
					"filter" => array(
						array(
							"term" => array(
								"_type" => "objects",
							),
						),
						array(
							"term" => array(
								"dataset" => $alias,
							),
						),
					),
				),
			),
			"aggs" => array(
				"all_weights" => array(
					"global" => new \stdClass(),
					"aggs" => array(
						"max_weight" => array("max" => array("field" => "weights.main.score")),
					),
				),
			),
			"sort" => array(
				array(
					'date' => 'desc',
				),
				array(
					'title.raw' => 'asc',
				),
			),
			"fields" => array('slug', 'id', 'weights.main.score'),
		));
		
		
		$divider = ( 
			isset( $response['aggregations']['all_weights']['max_weight']['value'] ) && 
			$response['aggregations']['all_weights']['max_weight']['value'] 
		) ? $response['aggregations']['all_weights']['max_weight']['value'] : 1;
		
		$map = array();
		
		foreach( $response['hits']['hits'] as $hit ) {
			
			$map[] = array(
				'id' => $hit['fields']['id'][0],
				'slug' => $hit['fields']['slug'][0],
				'weight' => $hit['fields']['weights.main.score'][0] / $divider,
			);
			
		}
		
		return $map;
	    
    }

    public function getSortings($alias)
    {

        return $this->query("SELECT `field`, `label`, `direction`
	    	FROM `datasets_orders` AS sorting
	    	WHERE `dataset`='" . addslashes($alias) . "'
	    	ORDER BY `ord` ASC 
	    	LIMIT 100");

    }

    public function getFields($alias)
    {

        return $this->query("SELECT `fields`.`alias`, `fields`.`field`, `fields`.`multiValue` 
	    	FROM `api_datasets_fields` AS `fields` 
	    	WHERE `fields`.`base_alias`='" . addslashes($alias) . "'
	    	LIMIT 100");

    }


    
    public function search($alias, $queryData = array()) {
	    
	    
		$dataset = $this->find('first', array(
			'conditions' => array(
			    'Dataset.alias' => $alias,
			),
			'full' => 1,
		));
				
		$__fields = array('_date', '_title', '_weight');
		$__orders = $__fields;
		$_fields = $__fields;
				
		
		foreach( array_column($dataset['fields'], 'fields') as $field ) {
			
			$_field = $field['field'];
			if( $field['alias']==$alias )				
				$_fields[] = $_field;
				
			$_fields[] = $field['alias'] . '.' . $_field;
			
		}
		
		reset( $field );
				
		foreach( array_column($dataset['orders_es'], 'sorting') as $field ) {
						
			$_field = $field['field'];
			
			$__orders[] = $_field;
			$__orders[] = $alias . '.' . $_field;
			
		}
		
				
		
		
		
		$requested_fields = array();
		// debug( $_fields ); die();
		
		if(
			isset( $queryData['fields'] ) && 
			!empty( $queryData['fields'] )
		) {
			
			foreach( $queryData['fields'] as $field ) {
				
				if( strpos($field, '.')===false )
					$field = $alias . '.' . $field;
					
				if( in_array($field, $_fields) )
					$requested_fields[] = 'data.' . $field;
				elseif( $field=='sejm_wystapienia.data' ) // TEMP HACK
					$requested_fields[] = 'data.' . $field;
				
			}
			
		}

		// debug( $requested_fields ); die();
		
		
		
		$virtual_fields = $dataset['virtual_fields'];		
		$filters = array(
			'dataset' => $alias,
		);
		$switchers = array();
		$facets = array();
		$order = array();
		$q = false;
		$mode = (isset($queryData['mode']) && $queryData['mode']) ? $queryData['mode'] : null;
		$do_facets = (isset($queryData['facets']) && $queryData['facets']) ? true : false;
		$limit = (isset($queryData['limit']) && $queryData['limit']) ? $queryData['limit'] : 20;
		$page = (isset($queryData['page']) && $queryData['page']) ? $queryData['page'] : 1;
		$version = (isset($dataset['Dataset']['version']) && $dataset['Dataset']['version']) ? $dataset['Dataset']['version'] : false;
		



		if( isset($queryData['conditions']) && is_array($queryData['conditions']) ) {
			foreach( $queryData['conditions'] as $key => $value ) {
				
				if( in_array($key, array('page', 'limit')) )
					continue;
					
				if( $key[0]=='!' )
					$switchers[ substr($key, 1) ] = $value;
				elseif( $key=='q' )
					$q = $value;
				elseif( in_array($key, $_fields) ) {
				
					if( 
						( !in_array($key, $__fields) ) && 
						( strpos($key, '.')===false )
					)
						$key = $alias . '.' . $key;
					
					$filters[ $key ] = $value;
				
				} elseif( $key=='_source' )
					$filters[ $key ] = $value;
			
			}
		}
		
		
		
		
		if( isset($queryData['q']) )
			$q = $queryData['q'];
			
		
		
		if (!empty($switchers)) {
                               
            $dataset_switchers_exp_dict = array_column($dataset['switchers'], 'switcher');
            $dataset_switchers_exp_dict = array_column($dataset_switchers_exp_dict, 'filters', 'name');
			
			foreach( $switchers as $key => $value ) {
                if( $_filters = $dataset_switchers_exp_dict[ $key ] ) {
	                	                
					if( !empty($_filters[0]) ) {
						foreach( $_filters[0] as $_k => $_v ) {
												
							if( strpos($_k, '.')===false )
								$_k = $alias . '.' . $_k;
							
							$filters[ $_k ] = $_v;							
						}
					}
					
					/*
					if( $parts = explode(':', $exp) ) {
						
						if( stripos($parts[0], '_data_') === 0 )
							$parts[0] = substr($parts[0] , 6);
							
						$filters[ $parts[0] ] = array($parts[1], false);
						
					}
					*/
					
                }
            }
						
        }		
				
		
		if( $do_facets ) {
		
			$facets_dict = array();
			if( isset($dataset['filters']) ) {
					
				foreach( $dataset['filters'] as $filter ) 
					if( ( $filter = $filter['filter'] ) && in_array($filter['typ_id'], array(1, 2)) ) {
						
						if( strpos($filter['field'], '.')===false )
							$filter['field'] = $alias . '.' . $filter['field'];
							
						$facets[] = $filter['field'];
						$facets_dict[ $filter['field'] ] = $filter;
					
					}
			
			}
		
		}
		
		
			
		$_order = array();
		if( isset($queryData['order']) && $queryData['order'] )
			$order = $queryData['order'];
		
		if( is_string($order) )
			$order = array( $order );
		
		
		foreach( $order as $o ) {
						
			$_field = $o;
			if( $p = strpos($o, ' ') )
				$_field = substr($o, 0, $p);
				
			
			
			if( 
				( in_array($_field, $_fields) ) && 
				( in_array($_field, $__orders) || (mpapi_get_field_type($_field)!='string') )
			) {
				
				if( 
					( !in_array($_field, $__fields) ) && 
					( strpos($_field, '.')===false )
				)
					$o = $alias . '.' . $o;
				
				$_order[] = $o;
				
			}
			
		}
								
		App::import('model','Dane.Dataobject');
		$this->Dataobject = new Dataobject();
        $search = $this->Dataobject->find('all', array(
        	'q' => $q,
        	'mode' => $mode,
        	'filters' => $filters,
        	'facets' => $facets,
        	'order' => $_order,
        	'limit' => $limit,
        	'page' => $page,
        	'version' => $version,
        	'fields' => $requested_fields,
        ));

		
				
		
		if( isset($search['facets']) ) {
						
			App::import('model', 'DB');
	        $this->DB = new DB();
			
			$facets = array();
			foreach( $search['facets'] as $field => $buckets ) {
				
				$filter = $facets_dict[ $field ];
				$buckets = $buckets[ 0 ];
				$options = array();
				
				if ($filter['typ_id'] == '1') {

		            $ids = array();
		            foreach ($buckets as $b)
		                if( $b['key'] && $b['doc_count'] )
		                    $ids[] = $b['key'];
		
		
		            if (isset($filter['params']['table'])) {
		
		                $id_field = isset($filter['params']['id_field']) ? $filter['params']['id_field'] : 'id';
		                $title_field = isset($filter['params']['title_field']) ? $filter['params']['title_field'] : 'nazwa';
		                $table = $filter['params']['table'];
		
		                $data = $this->DB->selectAssocs("SELECT `$id_field` as 'id', `$title_field` as 'label' FROM `$table` WHERE `$id_field`='" . implode("' OR `$id_field`='", $ids) . "'");
						$data = array_column( $data, 'label', 'id' );
						
						
						foreach( $buckets as $b )
							$options[] = array(
								'id' => $b['key'],
								'count' => $b['doc_count'],
								'label' => array_key_exists($b['key'], $data) ? $data[ $b['key'] ] : ' - ',
							);
							
		                $filter['params'] = array(
		                	'options' => $options,
		                );
		
		            }
		
		        } elseif ($filter['typ_id'] == '2') {
					
					$data = array_column($buckets, 'doc_count', 'key');
					
		            for ($i = 0; $i < count($filter['params']['options']); $i++)
		                $filter['params']['options'][$i]['count'] = @$data[ strtolower( $filter['params']['options'][$i]['id'] ) ];
		
		        }
		        
		        $facets[] = $filter;
				
			}
			
			$search['facets'] = $facets;
			
		}
				
		return $search;
	    
    }

}
<?php

define('API_BDL_MISMATCHED_DIMS_COUNT', 'API_BDL_MISMATCHED_DIMS_COUNT');
define('API_BDL_INVALID_SLICE', 'API_BDL_INVALID_SLICE');
define('API_BDL_INVALID_INPUT', 'API_BDL_INVALID_INPUT');
define('API_BDL_TOO_MANY_POINTS', 'API_BDL_TOO_MANY_POINTS');
define('API_BDL_LEVEL_DATA_NOTAVAILABLE', 'API_BDL_LEVEL_DATA_NOTAVAILABLE');

define('DATA_POINTS_LIMIT', 10000);
define('BDL_CACHE_TTL_SEC', 3600 * 24); // 1day

App::import('model', 'MPCache');

class BDLController extends AppController
{

    public $uses = array('BDL.BDL', 'Dane.Dataobject', 'BDL.Podgrupa', 'BDL.DataPl',
        'BDL.DataWojewodztwa', 'BDL.DataGminy', 'BDL.DataPowiaty', 'BDL.WymiaryKombinacje',
    );
	
	public function data()
	{
		
		$data = array();
		
		if(
			isset( $this->request->query['dims'] ) && 
			isset( $this->request->query['wskaznik_id'] ) 
		) {
		
			$conditions = array(
				'WymiaryKombinacje.podgrupa_id' => $this->request->query['wskaznik_id'],
			);
						
			for( $i=0; $i<5; $i++ ) {
				if( is_numeric($this->request->query['dims'][ $i ]) ) {
					$j = $i + 1;
					$conditions[ 'WymiaryKombinacje.w' . $j ] = $this->request->query['dims'][ $i ];
				}
			}
							
			$contain = array();
			if( isset( $this->request->query['years'] ) )
				$contain['DataPl'] = array(
					'fields' => array(
						'DataPl.rocznik', 'DataPl.v', 'DataPl.a'
					),
				);
			
			$data = $this->WymiaryKombinacje->find('all', array(
				'fields' => array('WymiaryKombinacje.id', 'WymiaryKombinacje.jednostka', 'WymiaryKombinacje.ly', 'WymiaryKombinacje.lv', 'WymiaryKombinacje.ply', 'WymiaryKombinacje.dv', 'WymiaryKombinacje.w1', 'WymiaryKombinacje.w2', 'WymiaryKombinacje.w3', 'WymiaryKombinacje.w4', 'WymiaryKombinacje.w5'),
				'conditions' => $conditions,
				'contain' => $contain,
			));
					
		}
				
		$this->set('data', $data);
		$this->set('_serialize', 'data');
		
	}
	
	public function combinations()
	{
		
		$data = false;
		
		if( 
			isset($this->request->query['id']) && 
			( $id = $this->request->query['id'] ) && 
			is_numeric($id) 
		) {
			
			$data = $this->WymiaryKombinacje->find('first', array(
				'fields' => array('WymiaryKombinacje.id', 'WymiaryKombinacje.jednostka', 'WymiaryKombinacje.ly', 'WymiaryKombinacje.lv', 'WymiaryKombinacje.ply', 'WymiaryKombinacje.dv', 'WymiaryKombinacje.w1', 'WymiaryKombinacje.w2', 'WymiaryKombinacje.w3', 'WymiaryKombinacje.w4', 'WymiaryKombinacje.w5'),
				'conditions' => array(
					'WymiaryKombinacje.id' => $id,
				),
				'contain' => array(
					'DataPl' => array(
						'fields' => array(
							'DataPl.rocznik', 'DataPl.v', 'DataPl.a'
						),
					),
				),
			));
						
			if(
				isset( $this->request->query['local'] ) &&
				in_array($this->request->query['local'], array('wojewodztwa', 'powiaty', 'gminy'))
			) {
				
				$data['local'] = $this->BDL->getLocalData($id, $this->request->query['local'], @$this->request->query['page']);
				
			}
			
		}
		
		$this->set('data', $data);
		$this->set('_serialize', 'data');
		
	}
	
	
    // TODO cleanup
    /**
     * Pobiera dane dla danej konfiguracji ustawień
     */
    public function getDataForIndicatorSet()
    {
        $options = array(
            'w1', 'w2', 'w3', 'w4', 'w5'
        );

        App::import('model','DB');
        $DB = new DB();

        $types = array(
            'wojewodztwo',
            'powiat',
            'gmina'
        );

        if(isset($this->request->query['type'])) {
            if(in_array($this->request->query['type'], $types))
                $type = $this->request->query['type'];
            else
                $type = $types[0];
        }
        else
            $type = $types[0];

        $tables = array(
            'wojewodztwo'   => array(
                'name'  => 'BDL_data_wojewodztwa',
                'field' => 'wojewodztwo_id'
            ),
            'powiat'    => array(
                'name'  => 'BDL_data_powiaty',
                'field' => 'powiat_id'
            ),
            'gmina'     => array(
                'name'  => 'BDL_data_gminy',
                'field' => 'gmina_id'
            ),
        );

        $table = $tables[$type];

        $where = '';
        foreach($options as $name) {
            $v = 0;
            if(isset($this->request->query[$name]))
                $v = (int) $this->request->query[$name];
            $where .= "`$name` = '$v' AND ";
        }

        $where = substr($where, 0, -4);

        $kombinacja = $DB->selectAssoc("SELECT id, jednostka, ly, lv FROM BDL_wymiary_kombinacje WHERE $where");
        $kombinacja_id = (int) $kombinacja['id'];
        $unit = $kombinacja['jednostka'];
        $value = $kombinacja['lv'];


        /* $y = $DB->selectAssocs("SELECT rocznik FROM ".$table['name']." WHERE kombinacja_id = $kombinacja_id AND deleted='0' GROUP BY rocznik ORDER BY rocznik DESC");
        $years = array();
        foreach($y as $yr)
            $years[] = (int) $yr['rocznik']; */

        $year = (int) $kombinacja['ly'];
        if(isset($this->request->query['year'])) {
            if(in_array($this->request->query['year'], $years)) // TODO undefined years
                $year = (int) $this->request->query['year'];
        }

        $data = $DB->selectAssocs("
            SELECT v, ".$table['field']." FROM ".$table['name']." WHERE kombinacja_id = $kombinacja_id AND rocznik = $year AND deleted='0' ORDER BY ".$table['field']." ASC
        ");

        $this->setSerialized('data', array(
            // 'years' => $years,
            'data'  => $data,
            'unit'  => $unit,
            'year'  => $year,
            'value' => $value
        ));
    }

    // TODO cleanup
	public function getCategory()
	{
		$id = (int) @$this->request->query['id'];
		if(!$id)
			throw new BadRequestException('id parameter is required');
		
        App::import('model','DB');
        $DB = new DB();
		$category = array();
		
		$cache = new MPCache();
        $cacheClient = $cache->getDataSource()->getRedisClient();
        $cacheKey = 'bdl/getCategory/'.$id;
		
		if($cacheClient->exists($cacheKey)) 
            $category = json_decode($cacheClient->get($cacheKey));
		else 
		{
			$category = array(
				'id' => $id,
				'groups' => array()
			);
			
			$category['groups'] = $DB->selectAssocs("
				SELECT id, tytul FROM BDL_grupy WHERE kat_id = ".$category['id']." AND deleted = '0' AND okres = 'R'
			");
			
			foreach($category['groups'] as $i => $group) {
				$category['groups'][$i]['subgroups'] = $DB->selectAssocs("
					SELECT id, tytul FROM BDL_podgrupy WHERE grupa_id = " . $group['id'] . " AND deleted = '0' AND okres = 'R'
				");
			}	
		}
		
		$this->setSerialized('category', $category);
	}

    // TODO cleanup
    public function getCategories()
    {
        App::import('model','DB');
        $DB = new DB();

        $cache = new MPCache();
        $cacheClient = $cache->getDataSource()->getRedisClient();
        $cacheKey = 'bdl/getCategories';

        if($cacheClient->exists($cacheKey)) {
            $categories = json_decode($cacheClient->get($cacheKey));
        } else {
            $categories = $DB->selectAssocs("
                SELECT id, w_tytul, tytul FROM BDL_kategorie WHERE deleted = '0' AND okres = 'R'
            ");

            foreach ($categories as $i => $category) {
                $categories[$i]['groups'] = $DB->selectAssocs("
                    SELECT id, tytul FROM BDL_grupy WHERE kat_id = " . $category['id'] . " AND deleted = '0' AND okres = 'R'
                ");

                foreach ($categories[$i]['groups'] as $m => $group) {
                    $categories[$i]['groups'][$m]['subgroups'] = $DB->selectAssocs("
                        SELECT id, tytul FROM BDL_podgrupy WHERE grupa_id = " . $group['id'] . " AND deleted = '0' AND okres = 'R'
                    ");
                }
            }
        }

        $this->setSerialized('categories', $categories);
    }

    public function categories()
    {
        // Try cache
        $cacheKey = 'bdl/categories';

        $cache = new MPCache();
        $cacheClient = $cache->getDataSource()->getRedisClient();
        if ($cacheClient->exists($cacheKey)) {
            $categories = json_decode($cacheClient->get($cacheKey));

        } else {
            $tree_data = ConnectionManager::getDataSource('default')->query(
    "SELECT pg.tytul AS pg_tytul, pg.id AS pg_id, g.tytul AS g_tytul, g.id AS g_id,
        k.w_tytul AS k_tytul, k.id AS k_id
    FROM epf.BDL_podgrupy pg INNER JOIN BDL_grupy g ON (pg.grupa_id = g.id)
        INNER JOIN BDL_kategorie k ON (pg.kategoria_id = k.id)
    WHERE k.okres = 'R' AND k.deleted = '0' AND g.deleted = '0' AND pg.deleted = '0' AND pg.akcept = '1'
    ORDER BY k_id ASC, g_id ASC, pg_tytul ASC;");

            $categories = array();
            $kategoria = null;
            $grupa = null;

            $last_kategoria = null;
            $last_grupa = null;

            foreach($tree_data as $row) {
                // dodaj nowa kategorie
                if ($row['k']['k_id'] != $last_kategoria) {
                    $k = array(
                        'groups' => array(),
                        'name' => $row['k']['k_tytul']
                    );

                    array_push($categories, $k);
                    $kategoria = &$categories[count($categories) - 1];
                }

                // dodaj nowa grupe
                if ($row['g']['g_id'] != $last_grupa) {
                    $g = array(
                        'subgroups' => array(),
                        'name' => $row['g']['g_tytul']
                    );

                    array_push($kategoria['groups'], $g);
                    $grupa = &$kategoria['groups'][count($kategoria['groups']) - 1];
                }

                array_push($grupa['subgroups'], array(
                    'name' => $row['pg']['pg_tytul'],
                    'url' => Router::url(array('plugin' => 'Dane', 'controller' => 'Dataobjects', 'action' => 'view', 'dataset' => 'bdl_wskazniki', 'id' => $row['pg']['pg_id']), true)
                ));

                $last_grupa = $row['g']['g_id'];
                $last_kategoria = $row['k']['k_id'];
            }

            $cacheClient->setex($cacheKey, BDL_CACHE_TTL_SEC, json_encode($categories));
        }

        $this->setSerialized('tree', $categories);
    }

    public function search() {
        $q = @$this->request->query['q'];
        if( $q )
        {
            $search = $this->Dataobject->find('all', array(
                'conditions' => array(
                    'q' => $q,
                    'dataset' => array('bdl_wskazniki', 'bdl_wskazniki_grupy', 'bdl_wskazniki_kategorie')
                ),
//                'mode' => 'title_prefix',
                'limit' => 10,
            ));

            $this->setSerialized('search', $search);
        } else {
            throw new BadRequestException('Query parameter is required: q');
        }
    }

    public function series() {
        $metric_id = @$this->request->query['metric_id'];
        $slice = @$this->request->query['slice'];
        $time_range = @$this->request->query['time_range'];
        $wojewodztwo_id = @$this->request->query['wojewodztwo_id'];
        $powiat_id = @$this->request->query['powiat_id'];
        $gmina_id = @$this->request->query['gmina_id'];
        $meta = @$this->request->query['meta'];

        if ($metric_id === null) {
            throw new BadRequestException('Query parameter is required: metric_id');
        }

        $metric = $this->Dataobject->find('first', array(
            'conditions' => array(
                'bdl_wskazniki.okres' => 'R',
                'dataset' => 'bdl_wskazniki',
                'id' => $metric_id,
            )
        ));

        if (!$metric) {
            throw new ApiException(API_BDL_INVALID_INPUT, array('param' => 'metric_id'));
        }

        $metric_depths = array(
            '0' => 'pl',
            '2' => 'wojewodztwo',
            '4' => 'powiat',
            '5' => 'gmina',
        );

        $dims = $this->Dataobject->getObjectLayer('bdl_wskazniki', $metric_id, 'dimennsions');

        // check if slices match metrics
        if ($slice != null) {
            $slice = preg_replace('/\\s+/', '', $slice);
            if (!preg_match('/^\\[((\\d+|\\*)(,(\\d+|\\*))*)\\]$/', $slice, $slices)) {
                throw new ApiException(API_BDL_INVALID_INPUT, array('param' => 'slice'));
            }

            $slices = explode(',', $slices[1]);
            if (count($slices) != count($dims)) {
                throw new ApiException(API_BDL_MISMATCHED_DIMS_COUNT, array('expected' => count($dims), 'was' => count($slices)));
            }

        } else {
            $slices = array_fill(0, count($dims), '*');
        }

        $w = array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0);
        for($i = 0; $i < count($slices); $i++) {
            $dim_slices = array_map(function($el) {
                return $el['id'];
            }, $dims[$i]['options']);

            if ($slices[$i] == '*') {
                $w[$i] = $dim_slices;

            } elseif(!in_array($slices[$i], $dim_slices)) {
                throw new ApiException(API_BDL_INVALID_SLICE, $slices[$i]);

            } else {
                $w[$i] = $slices[$i];
            }
        }

        if (($wojewodztwo_id != null) + ($powiat_id != null) + ($gmina_id != null) > 1) {
            throw new ApiException(API_BDL_INVALID_INPUT, array('param' => array('wojewodztwo_id', 'powiat_id', 'gmina_id')), 'Only one of these parameters can be specified');
        }

        $conditions = array();
        $fields = array('w1', 'w2', 'w3', 'w4', 'w5', 'rocznik', 'v', 'kombinacja_id');

        // choose datasource
        $region = null;

        if ($wojewodztwo_id != null) {
            $model = 'DataWojewodztwa';
            $fields[] = 'wojewodztwo_id';

            if ($metric['data']['bdl_wskazniki.poziom_id'] < 2) {
                throw new ApiException(API_BDL_LEVEL_DATA_NOTAVAILABLE, array(
                    'requested' => $metric_depths[2],
                    'available_till' => $metric_depths[$metric['data']['poziom_id']]
                ), 'Data at this regional level is not available');
            }

            if ($wojewodztwo_id != '*') {
                if (!preg_match('/^\d+$/', $wojewodztwo_id)) {
                    throw new ApiException(API_BDL_INVALID_INPUT, array('param' => 'wojewodztwo_id'));
                }

                $wojewodztwo_id = intval($wojewodztwo_id);
                $region = $this->Dataobject->find('first', array(
                    'conditions' => array(
                        'dataset' => 'wojewodztwa',
                        'id' => $wojewodztwo_id
                    )
                ));
                if (!$region) {
                    throw new ApiException(API_BDL_INVALID_INPUT, array('param' => 'wojewodztwo_id'));
                }

                $conditions["wojewodztwo_id"] = $wojewodztwo_id;
            }

        } else if ($powiat_id != null) {
            $model = 'DataPowiaty';
            $fields[] = 'powiat_id';

            if ($metric['data']['bdl_wskazniki.poziom_id'] < 4) {
                throw new ApiException(API_BDL_LEVEL_DATA_NOTAVAILABLE, array(
                    'requested' => $metric_depths[4],
                    'available_till' => $metric_depths[$metric['data']['bdl_wskazniki.poziom_id']]
                ), 'Data at this regional level is not available');
            }

            if ($powiat_id != '*') {
                if (!preg_match('/^\d+$/', $powiat_id)) {
                    throw new ApiException(API_BDL_INVALID_INPUT, array('param' => 'powiat_id'));
                }

                $powiat_id = intval($powiat_id);
                $region = $this->Dataobject->find('first', array(
                    'conditions' => array(
                        'dataset' => 'powiaty',
                        'id' => $powiat_id
                    )
                ));
                if (!$region) {
                    throw new ApiException(API_BDL_INVALID_INPUT, array('param' => 'powiat_id'));
                }

                $conditions["powiat_id"] = $powiat_id;
            }

        } else if ($gmina_id != null) {
            $model = 'DataGminy';
            $fields[] = 'gmina_id';

            if ($metric['data']['bdl_wskazniki.poziom_id'] < 5) {
                throw new ApiException(API_BDL_LEVEL_DATA_NOTAVAILABLE, array(
                    'requested' => $metric_depths[5],
                    'available_till' => $metric_depths[$metric['data']['bdl_wskazniki.poziom_id']]
                ), 'Data at this regional level is not available');
            }

            if ($gmina_id != '*') {
                if (!preg_match('/^\d+$/', $gmina_id)) {
                    throw new ApiException(API_BDL_INVALID_INPUT, array('param' => 'gmina_id'));
                }

                $gmina_id = intval($gmina_id);
                $region = $this->Dataobject->find('first', array(
                    'conditions' => array(
                        'dataset' => 'gminy',
                        'id' => $gmina_id
                    )
                ));
                if (!$region) {
                    throw new ApiException(API_BDL_INVALID_INPUT, array('param' => 'gmina_id'));
                }

                $conditions["gmina_id"] = $gmina_id;
            }

        } else {
            $model = 'DataPl';
        }

        // choose time range
        if ($time_range != null) {
            if (!preg_match('/^(\\d{4}):(\\d{4})$/', $time_range, $time_ranges)) {
                throw new ApiException(API_BDL_INVALID_INPUT, array('param' => 'time_range', 'value' => $time_range, 'expected_format' => 'year_start:year_end'));
            }

            if ($time_ranges[0] <= $time_ranges[1]) {
                $conditions['rocznik >='] = intval($time_ranges[0]);
                $conditions['rocznik <='] = intval($time_ranges[1]);

            } else {
                $conditions['rocznik >='] = intval($time_ranges[1]);
                $conditions['rocznik <='] = intval($time_ranges[2]);
            }
        }

        // choose series
        $series_ids = $this->WymiaryKombinacje->find('all', array(
            'conditions' => array(
                'podgrupa_id' => $metric_id,
                'w1' => $w[0],
                'w2' => $w[1],
                'w3' => $w[2],
                'w4' => $w[3],
                'w5' => $w[4],
            ),
            'fields' => array('id', 'jednostka')
        ));
        $units = array();
        foreach($series_ids as $s) {
            $units[$s['WymiaryKombinacje']['id']] = trim($s['WymiaryKombinacje']['jednostka'], '[]');
        }
        $conditions['kombinacja_id'] = array_keys($units);

        $conditions['deleted'] = 0;

        $data_count = $this->$model->find('count', array(
            'conditions' => $conditions,
            'fields' => $fields
        ));

        if ($data_count > DATA_POINTS_LIMIT) {
            throw new ApiException(API_BDL_TOO_MANY_POINTS, array('limit' => DATA_POINTS_LIMIT, 'found' => $data_count));
        }

        $order = array('kombinacja_id', 'rocznik');
        if ($wojewodztwo_id != null)
            array_splice($order, 1, 0, 'wojewodztwo_id');
        if ($powiat_id != null)
            array_splice($order, 1, 0, 'powiat_id');
        if ($gmina_id != null)
            array_splice($order, 1, 0, 'gmina_id');

        $data = $this->$model->find('all', array(
            'conditions' => $conditions,
            'fields' => $fields,
            'order' => $order,
        ));

        $slice_parts = array_slice(array('w1'=>0, 'w2'=>0, 'w3'=>0, 'w4'=>0, 'w5'=>0), 0, count($slices));
        $response = MpUtils::maptable2tree($data, array(
                   array('name' => 'slices', 'key' => function($r) use($model) {
                           return $r[$model]['kombinacja_id'] . '-' . @$r[$model]['wojewodztwo_id']. @$r[$model]['powiat_id'] . @$r[$model]['gmina_id'];
                       }, 'content' => function($r) use($model, $slice_parts, $units) {
                           $legend = array(
                               'slice' => array_values(array_intersect_key($r[$model], $slice_parts)),
                               'units' => $units[$r[$model]['kombinacja_id']]);

                           if (@$r[$model]['wojewodztwo_id'])
                                $legend['wojewodztwo_id'] = $r[$model]['wojewodztwo_id'];

                           if (@$r[$model]['powiat_id'])
                               $legend['powiat_id'] = $r[$model]['powiat_id'];

                           if (@$r[$model]['gmina_id'])
                               $legend['gmina_id'] = $r[$model]['gmina_id'];

                           return $legend;
                       }),
                   array('content' => function($r) use($model) {
                           return array('year' => intval($r[$model]['rocznik']), 'value' => $r[$model]['v']);
                       })
                ), 'series');

        if ($meta !== '0') {
            // include meta by default
            $response['meta'] = array(
                'metric_id' =>  Dataobject::apiUrl($metric['dataset'], $metric['id']),
                'metric_name' => $metric['data']['bdl_wskazniki.tytul'],
                'metric_depth' => $metric_depths[$metric['data']['bdl_wskazniki.poziom_id']],
                'metric_mpurl' => Dataobject::mpUrl($metric['dataset'], $metric['id']),
                'group_name' => $metric['data']['bdl_wskazniki.grupa_tytul'],
                'category_name' => $metric['data']['bdl_wskazniki.kategoria_tytul'],
                'dimensions' => $dims,
            );
        }

        if (empty($response)) {
            $response = new object();
        }

        $this->setSerialized('response', $response);
    }
    
    // TODO cleanup
    public function tree() {
	    
	    $this->loadModel('BDL.BDL');
	    
	    $tree = $this->BDL->getTree();
	    $this->setSerialized('tree', $tree);
	    
    }
    
}
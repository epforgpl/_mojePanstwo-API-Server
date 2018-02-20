<?php

class ZamowieniaPubliczne extends AppModel
{

    public $useTable = false;

    public function getStats()
    {
		App::import('model','DB');
		$DB = new DB();

		App::Import('ConnectionManager');
		$MPSearch = ConnectionManager::getDataSource('MPSearch');
		    
        $response = $MPSearch->search(array(
		  "size" => 0, 
		  "query" => array(
			"bool" => array(
			  "filter" => array(
			    array(
	              "term" => array(
	                "data.zamowienia_publiczne.status_id" => "2"
	              ),
	            ),
	            array(
	              "range" => array(
	                "date" => array(
	                  "gte" => "now-1M"
	                ),
	              ),
	            ),
			  ),
			),
		  ),
		  "aggs" => array(
		    "suma" => array(
		      "sum" => array(
		        "field" => "data.zamowienia_publiczne.wartosc_cena"
		      ),
		    ),
		    "rodzaje" => array(
		      "terms" => array(
		        "field" => "data.zamowienia_publiczne.rodzaj_id",
		        "exclude" => "0",
		        "order" => array(
		          "suma_zamowien" => "desc"
		        ), 
		        "size" => 3
		      ),
		      "aggs" => array(
		        "suma_zamowien" => array(
		          "sum" => array(
		            "field" => "data.zamowienia_publiczne.wartosc_cena"
		          ),
		        ),
		      ),
		    ),
		    "tryby" => array(
		      "terms" => array(
		        "field" => "data.zamowienia_publiczne.tryb_id",
		        "exclude" => "0",
		        "order" => array(
		          "suma_zamowien" => "desc"
		        ), 
		        "size" => 10
		      ),
		      "aggs" => array(
		        "suma_zamowien" => array(
		          "sum" => array(
		            "field" => "data.zamowienia_publiczne.wartosc_cena"
		          ),
		        ),
		      ),
		    ),
		    "zamawiajacy" => array(
		      "terms" => array(
		        "field" => "data.zamowienia_publiczne.zamawiajacy_id",
		        "order" => array(
		          "suma_zamowien" => "desc"
		        ), 
		        "size" => 10
		      ),
		      "aggs" => array(
		        "suma_zamowien" => array(
		          "sum" => array(
		            "field" => "data.zamowienia_publiczne.wartosc_cena"
		          ),
		        ),
		      ),
		    ),
		  ),
        ));
                
        $aggregations = $response['aggregations'];
        $rodzaje = array();
        $tryby = array();
        $zamawiajacy = array();
        
        
		
		// RODZAJE
		
		if( !empty($aggregations['rodzaje']['buckets']) ) {
			
			$keys = array_column($aggregations['rodzaje']['buckets'], 'key');
			$dictionary = $DB->selectDictionary("SELECT id, nazwa FROM uzp_rodzaje WHERE `id`='" . implode("' OR `id`='", $keys) . "'");
						
			foreach( $aggregations['rodzaje']['buckets'] as $item )
				if( $item['doc_count'] && $item['suma_zamowien']['value'] )
					$rodzaje[] = array(
						'id' => $item['key'],
						'nazwa' => $dictionary[ $item['key'] ],
						'liczba_zamowien' => $item['doc_count'],
						'suma_zamowien' => $item['suma_zamowien']['value'],
					);
				
		}
		
		
		
		// TRYBY
		
		if( !empty($aggregations['tryby']['buckets']) ) {
			
			$keys = array_column($aggregations['tryby']['buckets'], 'key');
			$dictionary = $DB->selectDictionary("SELECT id, nazwa FROM uzp_tryby WHERE `id`='" . implode("' OR `id`='", $keys) . "'");
						
			foreach( $aggregations['tryby']['buckets'] as $item )
				if( $item['doc_count'] && $item['suma_zamowien']['value'] )
					$tryby[] = array(
						'id' => $item['key'],
						'nazwa' => $dictionary[ $item['key'] ],
						'liczba_zamowien' => $item['doc_count'],
						'suma_zamowien' => $item['suma_zamowien']['value'],
					);
				
		}
		
		
		
		// ZAMAWIAJĄCY
		
		if( !empty($aggregations['zamawiajacy']['buckets']) ) {
			
			$keys = array_column($aggregations['zamawiajacy']['buckets'], 'key');
			$dictionary = $DB->selectDictionary("SELECT id, nazwa FROM uzp_zamawiajacy WHERE `id`='" . implode("' OR `id`='", $keys) . "'");
						
			foreach( $aggregations['zamawiajacy']['buckets'] as $item )
				if( $item['doc_count'] && $item['suma_zamowien']['value'] )
					$zamawiajacy[] = array(
						'id' => $item['key'],
						'nazwa' => stripslashes( $dictionary[ $item['key'] ] ),
						'liczba_zamowien' => $item['doc_count'],
						'suma_zamowien' => $item['suma_zamowien']['value'],
					);
				
		}
        

		        
        return array(
        	'suma_zamowien' => $aggregations['suma']['value'],
        	'liczba_zamowien' => $response['hits']['total'],
        	'rodzaje' => $rodzaje,
        	'tryby' => $tryby,
        	'zamawiajacy' => $zamawiajacy,
        );

    }
    
    public function getNewStats($range = 'month')
    {
    	
	    $_allowed_ranges = array('week', 'month', 'year', '3years', '5years');
	    if( !in_array($range, $_allowed_ranges) )
	    	return false;
	    
	    App::import('model', 'DB');
        $this->DB = new DB();
	    
	    $data = $this->DB->selectValue("SELECT `data` FROM `uzp_stats` WHERE `id`='" . addslashes( $range ) . "'");
	    if( !empty($data) && ( $data = unserialize(preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $data)) )) {
		    
		    return $data;
		    
	    } else return false;
	    		    
    }
    
    public function getAggs($params = array())
    {
	    	    
	    App::Import('ConnectionManager');
		$MPSearch = ConnectionManager::getDataSource('MPSearch');
		
		$request = isset($params['request']) ? $params['request'] : array();
		$_aggs = isset($params['aggs']) ? $params['aggs'] : array();
		
		$filters = array(
			array(
				'term' => array(
					'dataset' => 'zamowienia_publiczne_dokumenty',
				),
			),
			array(
				'term' => array(
					'data.zamowienia_publiczne_dokumenty.typ_id' => '3',
				),
			),
		);
		
		if( isset($request['date_min']) )
			$filters[] = array(
				'range' => array(
					'date' => array(
						'gte' => $request['date_min'],
					),
				),
			);
			
		if( isset($request['date_max']) )
			$filters[] = array(
				'range' => array(
					'date' => array(
						'lte' => $request['date_max'],
					),
				),
			);
						
		if( isset($request['instytucja_id']) )
			$filters[] = array(
				'nested' => array(
					'path' => 'feeds_channels',
					'filter' => array(
						'bool' => array(
							'must' => array(
								array(
									'term' => array(
										'feeds_channels.dataset' => 'instytucje',
									),
								),
								array(
									'term' => array(
										'feeds_channels.object_id' => $request['instytucja_id'],
									),
								),
							),
						),
					),
				),
			);
			
		if( isset($request['gmina_id']) )
			$filters[] = array(
				'term' => array(
					'data.zamowienia_publiczne_dokumenty.gmina_id' => $request['gmina_id'],
				),
			);
			
		if( isset($request['zamawiajacy_id']) )
			$filters[] = array(
				'term' => array(
					'data.zamowienia_publiczne_dokumenty.zamawiajacy_id' => $request['zamawiajacy_id'],
				),
			);
					
		if( array_key_exists('dokumenty', $_aggs) ) {
			
			$size = (
				isset( $_aggs['dokumenty']['size'] ) && 
				is_numeric( $_aggs['dokumenty']['size'] )
			) ? $_aggs['dokumenty']['size'] : 3;
						
			$aggs['dokumenty'] = array(
				'top_hits' => array(
					'size' => $size,
					'fielddata_fields' => array('dataset', 'id', 'source'),
                    'sort' => array(
                        'data.zamowienia_publiczne_dokumenty.wartosc_cena' => array(
	                        'order' => 'desc',
                        ),
                    ),
				),
			);
			
		}
		
		if( array_key_exists('stats', $_aggs) ) {
			
			$aggs['stats'] = array(
				'stats' => array(
					'field' => 'data.zamowienia_publiczne_dokumenty.wartosc_cena',
				),
			);
			
		}
		
		if( array_key_exists('wykonawcy', $_aggs) ) {
			
			$size = (
				isset( $_aggs['wykonawcy']['size'] ) && 
				is_numeric( $_aggs['wykonawcy']['size'] )
			) ? $_aggs['wykonawcy']['size'] : 5;
			
			$aggs['wykonawcy'] = array(
				'nested' => array(
					'path' => 'zamowienia_publiczne-wykonawcy',
				),
				'aggs' => array(
					'wykonawca' => array(
						'terms' => array(
							'field' => 'zamowienia_publiczne-wykonawcy.id',
							'order' => array(
								'suma' => 'desc',
							),
							'size' => $size,
						),
						'aggs' => array(
							'nazwa' => array(
								'terms' => array(
									'field' => 'zamowienia_publiczne-wykonawcy.nazwa',
									'size' => 1,
								),
							),
							'krs_id' => array(
								'terms' => array(
									'field' => 'zamowienia_publiczne-wykonawcy.krs_id',
									'size' => 1,
								),
							),
							'waluta' => array(
								'terms' => array(
									'field' => 'zamowienia_publiczne-wykonawcy.waluta',
								),
								'aggs' => array(
									'suma' => array(
										'sum' => array(
											'field' => 'zamowienia_publiczne-wykonawcy.cena',
										),
									),
								),
							),
							'suma' => array(
								'sum' => array(
									'field' => 'zamowienia_publiczne-wykonawcy.cena',
								),
							),
						),
					),
				),
			);
			
		}
		
		$query = array(
			'index' => 'mojepanstwo_v1',
			'type' => 'objects',
			'body' => array(
				'size' => 0, 
				'query' => array(
					'bool' => array(
						'filter' => $filters,
					),
				),
				'aggs' => $aggs,
			),
		);

        $response = $MPSearch->API->search($query);
        
        return $response;
		
		$output = array(
			'aggs' => array(),
			'took' => $response['took'],
			'total' => $response['hits']['total'],
		);
		
		if( isset($response['aggregations']) ) {
			
			if( 
				isset($response['aggregations']['wykonawcy']) && 
				isset($response['aggregations']['wykonawcy']['wykonawca']) && 
				isset($response['aggregations']['wykonawcy']['wykonawca']['buckets']) && 
				( $buckets = $response['aggregations']['wykonawcy']['wykonawca']['buckets'] )
			) {
								
				foreach( $buckets as $b ) {
					
					$waluty = array();
					foreach( @$b['waluta']['buckets'] as $w )
						$waluty[ $w['key'] ] = $w['suma']['value'];
					
					$output['aggs']['wykonawcy'][] = array(
						'id' => $b['key'],
						'nazwa' => @$b['nazwa']['buckets'][0]['key'],
						'krs_id' => @$b['krs_id']['buckets'][0]['key'],
						'waluty' => $waluty,
					);
				
				}
				
			}
			
			if( 
				isset($response['aggregations']['dni']) && 
				isset($response['aggregations']['dni']['buckets']) && 
				( $buckets = $response['aggregations']['dni']['buckets'] )
			) {
												
				foreach( $buckets as $b ) {
										
					if( 
						isset($b['wykonawcy']['waluty']) && 
						isset($b['wykonawcy']['waluty']['buckets']) && 
						!empty($b['wykonawcy']['waluty']['buckets'])
					) {
						
						$waluty = array();
						foreach( @$b['wykonawcy']['waluty']['buckets'] as $w )
							$waluty[ $w['key'] ] = $w['suma']['value'];
						
						if( isset($waluty['PLN']) )						
							$output['aggs']['dni'][] = array(
								$b['key'], $waluty['PLN'],
							);						
						
					}
									
				}
				
			}
			
		}
		
		return $output;	    
			    
    }

} 
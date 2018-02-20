<?
	
	$transport = $this->ES->search(array(
		'query' => array(
			'bool' => array(
				'filter' => array(
					array(
						'term' => array(
							'_type' => 'prawo',
						),
					),
					array(
						'term' => array(
							'data_v3.pierwotny' => '1',
						),
					),
					array(
						'term' => array(
							'data_virtual.haslo_id' => $id,
						),
					),
				),
			),
		),
		'aggregations' => array(
			'hasla' => array(
				'terms' => array(
					'field' => 'data_virtual.haslo_id',
					'exclude' => '(' . $id . ')',
					'size' => 20,
				),
			),
		),
	));
	
	
	$keywords = $transport['aggregations']['hasla']['buckets'];
	$keywords_ids = array_column($keywords, 'key');
	$keywords_dict = $this->DB->selectDictionary("SELECT id, q FROM ISAP_hasla WHERE `id`='" . implode("' OR `id`='", $keywords_ids) . "'");
	
	foreach( $keywords as &$keyword )
		$keyword = array_merge($keyword, array(
			'q' => trim( $keywords_dict[ $keyword['key'] ] ),
		));
		
	$acts = array_column($transport['hits']['hits'], '_source');
	$acts = array_column($acts, 'data_v3');
	
	return array(
		'acts' => $acts,
		'keywords' => $keywords,
	);
	
	
	
	
	
	
	$items = $this->DB->selectAssocs("
		SELECT
			`child`.`id` as 'child.id', 
			`child`.`m_tytul` as 'child.tytul', 
			`child`.`pierwotny` as 'child.pierwotny', 
			`parent`.`id` as 'parent.id', 
			`parent`.`m_tytul` as 'parent.tytul', 
			`parent`.`pierwotny` as 'parent.pierwotny' 
		FROM
			`prawo` as `parent`
		JOIN 
			`prawo-hasla` AS `parent-tags` ON 
				(`parent`.`id` = `parent-tags`.`prawo_id` AND
				`parent-tags`.`haslo_id` = '$id' AND 
				`parent-tags`.`deleted` = '0') 
		JOIN
			`prawo_powiazania` AS `parent-child` ON
				(`parent`.`id` = `parent-child`.`a_id` AND 
				`parent-child`.`typ_id` = '12' AND 
				`parent-child`.`deleted` = '0')
		JOIN 
			 `prawo` AS `child` ON 
				(`parent-child`.`b_id` = `child`.`id` AND 
				`child`.`status_id` = '1' AND 
				`child`.`akcept` = '1')
		JOIN 
			`prawo-hasla` AS `child-tags` ON 
				(`child`.`id` = `child-tags`.`prawo_id` AND 
				`child-tags`.`haslo_id` = '$id' AND 
				`child-tags`.`deleted` = '0') 		
		WHERE 
			`parent`.`akcept` = '1' AND 
			`parent`.`pierwotny` = '1' AND 
			`parent`.`status_id` = '1' 			 
		ORDER BY 
			`parent`.`typ_id` ASC, 
			`parent`.`data_publikacji` DESC, 
			`child`.`typ_id` ASC, 
			`child`.`data_publikacji` DESC
	");
	
	
	$data = array();
	
	foreach( $items as $item ) {
		
		$child = array(
			'id' => $item['child.id'],
			'tytul' => $item['child.tytul'],
			'pierwotny' => $item['child.pierwotny'],
		);
		
		$parent = array(
			'id' => $item['parent.id'],
			'tytul' => $item['parent.tytul'],
			'pierwotny' => $item['parent.pierwotny'],
		);
				
		if( array_key_exists($parent['id'], $data) )
			$data[ $parent['id'] ]['children'][] = array(
				'item' => $child,
			);
		else
			$data[ $parent['id'] ] = array(
				'item' => $parent,
				'children' => array(
					array(
						'item' => $child,
					),
				),
			);
					
	}
	

	$keys = array_keys( $data );
	$data = array_values( $data );
	
	
	
	
	return $data;
	
	for( $i=0; $i<count($data); $i++ ) {
		
		// echo "\n\n\n\nPARENT\n"; var_export($data[$i]['item']);
		
		for( $j=0; $j<count($data[$i]['children']); $j++ ) {
			
			// echo "\n\nCHILD\n"; var_export( $data[$i]['children'][$j] );
			
			$parent_i = array_search($data[$i]['children'][$j]['item']['id'], $keys);
			
			if( $parent_i !== false ) {
				
				// echo "\npodczepiamy " . $parent_i . "\n";
				
				$data[$i]['children'][$j]['item']['children'] = $data[ $parent_i ]['children'];				
				$data[ $parent_i ]['deleted'] = true;
				
			}
			
		}
			
	}
	// return $data;
	
	
	$temp = array();
	foreach( $data as $d )
		if( !isset($d['deleted']) || !$d['deleted'] )
			$temp[] = $d;
	
	
	// echo "\n\n\n\n";
	// var_export( $temp ); die();
	
	// die();
	
	return $temp;
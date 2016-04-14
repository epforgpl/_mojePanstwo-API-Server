<?
	
	$raw_data = $this->DB->selectAssocs("SELECT caption_id, sex, area, region, city_size, household, education, age, value FROM culture_data WHERE survey_id='" . addslashes( $id ) . "'");
	
	$data = array();
	
	foreach( $raw_data as $d ) {
		
		$filter = array(
			'sex' => $d['sex'],
			'area' => $d['area'],
			'region' => $d['region'],
			'city_size' => $d['city_size'],
			'household' => $d['household'],
			'education' => $d['education'],
			'age' => $d['age'],
		);
		
		$filter_query = http_build_query( $filter );
		
		$data[ $filter_query ][ $d['caption_id'] ] = $d['value'];
		
	}
	
	return $data;
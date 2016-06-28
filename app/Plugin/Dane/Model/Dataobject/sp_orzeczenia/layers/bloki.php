<?
	
	$data = array();
	
	if(
		( @$data = S3::getObject('resources', 'SP/orzeczenia/' . $id . '.json') ) && 
		( @$data->body ) && 
		( $data = @json_decode($data->body) )
	) {
		
		
		
	} elseif(
		$data = $this->DB->query("SELECT id, tytul, wartosc FROM orzeczenia_bloki WHERE orzeczenie_id='$id' ORDER BY id ASC")
	) {
		
		foreach( $data as &$d )
			$d = $d['orzeczenia_bloki'];
		
	}
					
	return $data;
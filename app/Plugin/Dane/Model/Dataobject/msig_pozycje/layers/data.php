<?
	
	if(
		( $data = @S3::getObject('resources', 'MSiG/pozycje/json/' . $id . '.json') ) && 
		( @$data->body ) && 
		( $data = json_decode($data->body, true) )
	) {
		return $data;
	} else {
		return false;
	}
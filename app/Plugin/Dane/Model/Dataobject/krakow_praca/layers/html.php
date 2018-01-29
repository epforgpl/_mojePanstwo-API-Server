<?
	if(
		( $data = @S3::getObject('resources', 'krakow/praca/' . $id . '.html') ) && 
		( @$data->body ) 
	) {
		return $data->body;
	} else {
		return false;
	}
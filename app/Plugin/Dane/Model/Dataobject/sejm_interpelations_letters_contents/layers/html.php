<?
	
	$data = @S3::getObject('resources', 'sejm/interpelations/letters_contents/' . $id . '.html');
	return @$data->body;
	
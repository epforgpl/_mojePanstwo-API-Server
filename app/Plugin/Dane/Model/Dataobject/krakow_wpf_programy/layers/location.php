<?
	
	$id = (int) $id;
	return $this->DB->selectAssoc("SELECT lat, lon, zoom FROM krakow_wpf_program WHERE `id`='$id'");
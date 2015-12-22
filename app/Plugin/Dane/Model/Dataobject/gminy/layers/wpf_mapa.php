<?
	
	return $this->DB->selectAssocs("SELECT id, nazwa, laczne_naklady_fin, lat, lon, zoom FROM `krakow_wpf_program` WHERE lat IS NOT NULL AND lon IS NOT NULL");
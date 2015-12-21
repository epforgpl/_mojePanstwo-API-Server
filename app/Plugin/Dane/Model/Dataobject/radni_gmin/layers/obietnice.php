<?
	
	return $this->DB->selectAssocs("SELECT id, text, zrodlo_url, zrzut_url, znaleziono, do_sprawdzenia, odpowiedz FROM pl_gminy_radni_krakow_obietnice WHERE radny_id='" . addslashes( $id ) . "'");
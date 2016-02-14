<?
	
	return $this->DB->selectAssocs("SELECT id, nazwa, data, dokument_id FROM opp_dokumenty WHERE sprawozdanie_id='" . addslashes( $id ) . "'");
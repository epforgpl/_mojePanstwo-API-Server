<?
	
	return $this->DB->selectValue("SELECT html FROM `pl_gminy_krakow_stenogramy_przemowienia` WHERE `id`='" . addslashes( $id ) . "'");
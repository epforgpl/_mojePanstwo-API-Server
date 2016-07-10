<?
	
	if( 
		( $osoba_id = $this->DB->selectValue("SELECT naukapolska_id FROM osoby_publiczne WHERE id='" . addslashes( $id ) . "'") ) && 
		( $sid = $this->DB->selectValue("SELECT sid FROM naukapolska_osoby WHERE id='" . addslashes( $osoba_id ) . "'") ) && 
		( $data = $this->S3Files->getBody('resources/naukapolska/osoby/' . $osoba_id . '.json') ) && 
		( $data = json_decode($data, true) )
	) {
		
		return array(
			'sid' => $sid,
			'fields' => $data,
		);
		
	} else {
		
		return false;
		
	}
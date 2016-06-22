<?

	$features = array(
	    'mailings' => $this->DB->selectAssocs("
	        SELECT organizacje_dzialania_pisma.*, pisma_szablony.tresc
	        FROM organizacje_dzialania_pisma
	          JOIN pisma_szablony ON
	            pisma_szablony.id = organizacje_dzialania_pisma.pismo_szablon_id
	        WHERE dzialanie_id = ".( (int) $id )."
	    "),
	);
	
	return $features;
<?

$data = $this->DB->selectAssocs("
	SELECT
        `krs_wspolnicy`.`id`,
        `krs_wspolnicy`.`nazwa`,
        `krs_wspolnicy`.`imiona`,
        `krs_wspolnicy`.`udzialy_str`,
        `krs_osoby`.`id` as 'osoba_id',
        `krs_pozycje`.`id` as 'krs_id',
        `krs_osoby`.`data_urodzenia`,
        `krs_osoby`.`privacy_level`,
        `krs_osoby`.`deleted`,
        `krs_wspolnicy`.`udzialy_status`,
        `krs_wspolnicy`.`udzialy_liczba`,
        `krs_wspolnicy`.`udzialy_wartosc_jedn`,
        `krs_wspolnicy`.`udzialy_wartosc`,
        `krs_wspolnicy`.`powiat_id`,
        `objects_powiat`.`slug` as 'powiat_slug',
        `krs_wspolnicy`.`gmina_id`,
        `objects_gmina`.`slug` as 'gmina_slug'
	FROM
		`krs_wspolnicy`
	LEFT JOIN
		`krs_osoby`
			ON `krs_wspolnicy`.`osoba_id` = `krs_osoby`.`id`
	LEFT JOIN
		`krs_pozycje`
			ON `krs_wspolnicy`.`krs_id` = `krs_pozycje`.`id`
	LEFT JOIN
		`objects` `objects_powiat`
			ON
				`krs_wspolnicy`.`powiat_id` = `objects_powiat`.`object_id` AND
				`objects_powiat`.`dataset_id` = '35'
	LEFT JOIN
		`objects` `objects_gmina`
			ON
				`krs_wspolnicy`.`gmina_id` = `objects_gmina`.`object_id` AND
				`objects_gmina`.`dataset_id` = '6'
	WHERE
		`krs_wspolnicy`.`pozycja_id` = '" . addslashes($id) . "' AND
		`krs_wspolnicy`.`deleted` = '0'
	ORDER BY
		`krs_wspolnicy`.`ord` ASC
	LIMIT 100
");

$output = array();
foreach ($data as $d) {
	
	if( @$d['deleted'] ) {
		continue;
	}
	
	$nazwa = $d['nazwa'];
	$imiona = $d['imiona'];
	
	if( !trim(str_replace('*', '', $nazwa)) )
		$nazwa = '';
		
	if( !trim(str_replace('*', '', $imiona)) )
		$imiona = '';
	
	
    $o = array(
        'nazwa' => _ucfirst(trim( $nazwa . ' ' . $imiona )),
        'data_urodzenia' => $d['data_urodzenia'],
        'privacy_level' => $d['privacy_level'],
        'osoba_id' => @$d['osoba_id'],
        'krs_id' => @$d['krs_id'],
        'id' => @$d['id'],
        'funkcja' => @$d['udzialy_str'],
    );
    
    if( $d['udzialy_status']=='2' ) {
	    
	    $o = array_merge($o, array(
	        'udzialy_liczba' => @$d['udzialy_liczba'],
	        'udzialy_wartosc_jedn' => @$d['udzialy_wartosc_jedn'],
	        'udzialy_wartosc' => @$d['udzialy_wartosc'],
	    ));
	    
    }

    if($d['gmina_id'] != '0') {
        $o = array_merge($o, array(
            'gmina_id' => $d['gmina_id'],
            'gmina_slug' => $d['gmina_slug']
        ));
    }

    if($d['powiat_id'] != '0') {
        $o = array_merge($o, array(
            'powiat_id' => $d['powiat_id'],
            'powiat_slug' => $d['powiat_slug']
        ));
    }
    
    $output[] = $o;
}


return $output;
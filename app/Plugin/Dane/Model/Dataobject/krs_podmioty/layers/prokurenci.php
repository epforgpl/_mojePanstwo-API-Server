<?

$data = $this->DB->query("SELECT `krs_prokurenci`.nazwa, `krs_prokurenci`.imiona, `krs_prokurenci`.rodzaj, `krs_osoby`.`id`, `krs_osoby`.`data_urodzenia`, `krs_osoby`.`privacy_level` as `wiek`, `krs_osoby`.`deleted` 
		FROM `krs_prokurenci` 
		LEFT JOIN `krs_osoby` 
		ON `krs_prokurenci`.`osoba_id` = `krs_osoby`.`id` 
		WHERE `krs_prokurenci`.`pozycja_id` = '" . addslashes($id) . "' AND `krs_prokurenci`.`deleted`='0'
		ORDER BY `krs_prokurenci`.`ord` ASC LIMIT 100");

$output = array();
foreach ($data as $d) {
	
	if( @$d['krs_osoby']['deleted'] ) {
		continue;
	}
	
    $output[] = array(
        'nazwa' => _ucfirst($d['krs_prokurenci']['nazwa'] . ' ' . $d['krs_prokurenci']['imiona']),
        'funkcja' => _ucfirst($d['krs_prokurenci']['rodzaj']),
        'data_urodzenia' => $d['krs_osoby']['data_urodzenia'],
        'privacy_level' => @$d['krs_osoby']['privacy_level'],
        'osoba_id' => @$d['krs_osoby']['id'],
    );
    
}

return $output;
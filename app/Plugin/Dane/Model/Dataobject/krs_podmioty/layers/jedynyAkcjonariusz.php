<?

$data = $this->DB->query("SELECT `krs_jedyni_akcjonariusze`.nazwa, `krs_jedyni_akcjonariusze`.imiona, `krs_osoby`.`id`, `krs_osoby`.`data_urodzenia`, `krs_osoby`.`privacy_level`, `krs_osoby`.`deleted` 
		FROM `krs_jedyni_akcjonariusze` 
		LEFT JOIN `krs_osoby` 
		ON `krs_jedyni_akcjonariusze`.`osoba_id` = `krs_osoby`.`id` 
		WHERE `krs_jedyni_akcjonariusze`.`pozycja_id` = '" . addslashes($id) . "' AND `krs_jedyni_akcjonariusze`.`deleted`='0'
		ORDER BY `krs_jedyni_akcjonariusze`.`ord` ASC LIMIT 100");

$output = array();
foreach ($data as $d) {
	
	if( @$d['krs_osoby']['deleted'] ) {
		continue;
	}
	
    $output[] = array(
        'nazwa' => _ucfirst($d['krs_jedyni_akcjonariusze']['nazwa'] . ' ' . $d['krs_jedyni_akcjonariusze']['imiona']),
        'data_urodzenia' => $d['krs_osoby']['data_urodzenia'],
        'privacy_level' => $d['krs_osoby']['privacy_level'],
        'osoba_id' => @$d['krs_osoby']['id'],
    );
}


return $output;
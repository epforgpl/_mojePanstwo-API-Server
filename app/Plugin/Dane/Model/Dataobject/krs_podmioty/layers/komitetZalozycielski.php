<?

$data = $this->DB->query("SELECT `krs_komitety_zal`.nazwa, `krs_komitety_zal`.imiona, `krs_osoby`.`id`, `krs_osoby`.`data_urodzenia`, `krs_osoby`.`privacy_level`, `krs_osoby`.`deleted` 
		FROM `krs_komitety_zal` 
		LEFT JOIN `krs_osoby` 
		ON `krs_komitety_zal`.`osoba_id` = `krs_osoby`.`id` 
		WHERE `krs_komitety_zal`.`pozycja_id` = '" . addslashes($id) . "' AND `krs_komitety_zal`.`deleted`='0'
		ORDER BY `krs_komitety_zal`.`ord` ASC LIMIT 100");

$output = array();
foreach ($data as $d) {
	
	if( @$d['deleted'] ) {
		continue;
	}
	
    $output[] = array(
        'nazwa' => _ucfirst($d['krs_komitety_zal']['nazwa'] . ' ' . $d['krs_komitety_zal']['imiona']),
        'data_urodzenia' => $d['krs_osoby']['data_urodzenia'],
        'privacy_level' => $d['krs_osoby']['privacy_level'],
        'osoba_id' => @$d['krs_osoby']['id'],
    );
}


return $output;
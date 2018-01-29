<?php

class Wyjazdyposlow extends AppModel
{

    public $useTable = false;

    public function getStats()
    {

        App::import('model', 'DB');
        $DB = new DB();

        // App::Import('ConnectionManager');
        // $MPSearch = ConnectionManager::getDataSource('MPSearch');


        $output = array();

        // CAŁOŚCIOWO

        $output['calosc']['indywidualne'] = $DB->selectAssocs("SELECT 
		`s_poslowie_kadencje`.`id`, 
		`s_poslowie_kadencje`.`nazwa`, 
		`s_kluby`.`id` as 'klub_id', 
		`s_kluby`.`skrot`, 
		`mowcy_poslowie`.`mowca_id`, 
		s_poslowie_kadencje.wartosc_wyjazdow as 'sum',
		s_poslowie_kadencje.liczba_wyjazdow as 'count' 
		FROM `s_poslowie_kadencje` 
		JOIN `s_kluby`
		ON `s_poslowie_kadencje`.`klub_id` = `s_kluby`.`id` 
		JOIN `mowcy_poslowie`
		ON `s_poslowie_kadencje`.`id` = `mowcy_poslowie`.`posel_id`
		ORDER BY s_poslowie_kadencje.wartosc_wyjazdow DESC
		LIMIT 5
		");

        $output['calosc']['klubowe'] = $DB->selectAssocs("SELECT
		`s_kluby`.`id`, 
		`s_kluby`.`nazwa`, 
		`s_kluby`.`skrot`, 
		SUM(`poslowie_wyjazdy`.`koszt`) as 'sum',
		AVG(`poslowie_wyjazdy`.`koszt`) as 'avg',
		COUNT(DISTINCT e.id) as 'count'
		FROM `poslowie_wyjazdy` 
		JOIN `s_kluby`
		ON `poslowie_wyjazdy`.`klub_id` = `s_kluby`.`id`
		JOIN poslowie_wyjazdy_wydarzenia e
		ON e.id = poslowie_wyjazdy.wydarzenie_id
		WHERE e.deleted = '0' AND poslowie_wyjazdy.deleted = '0'
		GROUP BY `poslowie_wyjazdy`.`klub_id` 
		ORDER BY AVG(`poslowie_wyjazdy`.`koszt`) DESC
		LIMIT 10
		");
		
		$output['koszta'] = $DB->selectAssoc("SELECT SUM(`koszt_transport`) as 'transport', SUM(`koszt_dieta`) as 'diety', SUM(`koszt_hotel`) as 'hotele', SUM(`koszt`) as 'calosc' FROM `poslowie_wyjazdy`");
		
		$output['koszta']['transport'] = (float) $output['koszta']['transport'];
		$output['koszta']['diety'] = (float) $output['koszta']['diety'];
		$output['koszta']['hotele'] = (float) $output['koszta']['hotele'];
		$output['koszta']['calosc'] = (float) $output['koszta']['calosc'];
		$output['koszta']['pozostale'] = $output['koszta']['calosc'] - $output['koszta']['transport'] - $output['koszta']['diety'] - $output['koszta']['hotele'];
		
		
		
		
		$output['najdrozsze']['calosc'] = $DB->selectAssocs("SELECT id, liczba_dni, liczba_poslow, koszt, delegacja, lokalizacja FROM poslowie_wyjazdy_wydarzenia ORDER BY koszt DESC LIMIT 4");
		
		$output['najdrozsze']['indywidualnie'] = $DB->selectAssocs("SELECT 
		`s_poslowie_kadencje`.`id`, 
		`s_poslowie_kadencje`.`nazwa`, 
		`s_kluby`.`id` as 'klub_id', 
		`s_kluby`.`skrot`, 
		`mowcy_poslowie`.`mowca_id`, 
		`poslowie_wyjazdy`.`koszt`,
		`e`.`id` as 'wydarzenie_id', 
		`e`.`lokalizacja`, 
		`e`.`delegacja`
		FROM `poslowie_wyjazdy` 
		JOIN `s_poslowie_kadencje` 
			ON `poslowie_wyjazdy`.`posel_id` = `s_poslowie_kadencje`.`id` 
		JOIN `s_kluby`
			ON `poslowie_wyjazdy`.`klub_id` = `s_kluby`.`id` 
		JOIN `mowcy_poslowie`
			ON `s_poslowie_kadencje`.`id` = `mowcy_poslowie`.`posel_id`
		JOIN poslowie_wyjazdy_wydarzenia e
			ON e.id = poslowie_wyjazdy.wydarzenie_id
		WHERE 
			e.deleted = '0' AND poslowie_wyjazdy.deleted = '0'
		ORDER BY 
			`poslowie_wyjazdy`.`koszt` DESC
		LIMIT 6
		");
		
		
		
		$data = $DB->selectAssocs("
		SELECT 
			`poslowie_wyjazdy_wydarzenia`.`id`, 
			`poslowie_wyjazdy_wydarzenia`.`lokalizacja`, 
			`poslowie_wyjazdy_wydarzenia`.`delegacja`, 
			`poslowie_wyjazdy_wydarzenia`.`liczba_dni`, 
			`poslowie_wyjazdy_wydarzenia`.`liczba_poslow`, 
			`poslowie_wyjazdy_wydarzenia`.`date_start`, 
			`poslowie_wyjazdy_wydarzenia`.`date_stop`, 
			`s_poslowie_kadencje`.`id` as `posel_id`,
			`s_poslowie_kadencje`.`nazwa` as `posel_nazwa`, 
			`s_poslowie_kadencje`.`pkw_plec` as `plec`, 
			`poslowie_wyjazdy`.`glosowania_daty`,
			`poslowie_wyjazdy`.`koszt_dieta`,
			`poslowie_wyjazdy`.`koszt_transport`,
			`poslowie_wyjazdy`.`koszt_hotel`,
		    `mowcy_poslowie`.`mowca_id`,
		    `s_kluby`.`id` as `klub_id`,
		    `s_kluby`.`skrot` as `klub_skrot`
		FROM `poslowie_wyjazdy` 
			JOIN `poslowie_wyjazdy_wydarzenia` 
				ON `poslowie_wyjazdy`.`wydarzenie_id` = `poslowie_wyjazdy_wydarzenia`.`id` 
			JOIN `s_poslowie_kadencje` 
				ON `poslowie_wyjazdy`.`posel_id` = `s_poslowie_kadencje`.`id` 
			JOIN `s_kluby` 
				ON `poslowie_wyjazdy`.`klub_id` = `s_kluby`.`id` 
			JOIN `mowcy_poslowie` 
				ON `s_poslowie_kadencje`.`id` = `mowcy_poslowie`.`posel_id`
		WHERE `poslowie_wyjazdy`.`glosowania_daty`!='' 
		ORDER BY 
			`poslowie_wyjazdy_wydarzenia`.`date_start` ASC,
			`poslowie_wyjazdy`.`koszt_dieta` DESC
		");
		
		
		$wydarzenia = array();
		foreach( $data as $d ) {
			$wydarzenia[ $d['id'] ]['data'] = array(
				'id' => $d['id'],
				'lokalizacja' => $d['lokalizacja'],
				'delegacja' => $d['delegacja'],
				'liczba_dni' => $d['liczba_dni'],
				'liczba_poslow' => $d['liczba_poslow'],
				'date_start' => $d['date_start'],
				'date_stop' => $d['date_stop'],
			);
			$wydarzenia[ $d['id'] ]['poslowie'][] = array(
				'id' => $d['posel_id'],
				'nazwa' => $d['posel_nazwa'],
				'mowca_id' => $d['mowca_id'],
				'klub_id' => $d['klub_id'],
				'klub_skrot' => $d['klub_skrot'],
				'koszt_dieta' => $d['koszt_dieta'],
				'koszt_transport' => $d['koszt_transport'],
				'koszt_hotel' => $d['koszt_hotel'],
				'plec' => $d['plec'],
				'glosowania_dni' => explode(',', $d['glosowania_daty']),
			);
		}
		
		unset( $data );
		$output['wydarzenia'] = array_values( $wydarzenia );
		
		
				
		return $output;
        

    }
    
    public function getStats8()
    {

        App::import('model', 'DB');
        $DB = new DB();

        // App::Import('ConnectionManager');
        // $MPSearch = ConnectionManager::getDataSource('MPSearch');


        $output = array();

        // CAŁOŚCIOWO

        $output['calosc']['indywidualne'] = $DB->selectAssocs("SELECT 
		`s_poslowie_kadencje`.`id`, 
		`s_poslowie_kadencje`.`nazwa`, 
		`s_kluby`.`id` as 'klub_id', 
		`s_kluby`.`skrot`, 
		`mowcy_poslowie`.`mowca_id`, 
		s_poslowie_kadencje.`wartosc_wyjazdow8` as 'sum',
		s_poslowie_kadencje.`liczba_wyjazdow8` as 'count' 
		FROM `s_poslowie_kadencje` 
		JOIN `s_kluby`
		ON `s_poslowie_kadencje`.`klub_id` = `s_kluby`.`id` 
		LEFT JOIN `mowcy_poslowie`
		ON `s_poslowie_kadencje`.`id` = `mowcy_poslowie`.`posel_id`
		ORDER BY s_poslowie_kadencje.`wartosc_wyjazdow8` DESC
		LIMIT 5
		");

        $output['calosc']['klubowe'] = $DB->selectAssocs("SELECT
		`s_kluby`.`id`, 
		`s_kluby`.`nazwa`, 
		`s_kluby`.`skrot`, 
		SUM(`poslowie_wyjazdy8`.`koszt`) as 'sum',
		AVG(`poslowie_wyjazdy8`.`koszt`) as 'avg',
		COUNT(DISTINCT e.id) as 'count'
		FROM `poslowie_wyjazdy8` 
		JOIN `s_kluby`
		ON `poslowie_wyjazdy8`.`klub_id` = `s_kluby`.`id`
		JOIN `poslowie_wyjazdy_wydarzenia8` e
		ON e.id = `poslowie_wyjazdy8`.wydarzenie_id
		WHERE e.deleted = '0' AND `poslowie_wyjazdy8`.deleted = '0'
		GROUP BY `poslowie_wyjazdy8`.`klub_id` 
		ORDER BY AVG(`poslowie_wyjazdy8`.`koszt`) DESC
		LIMIT 10
		");
		
		$output['koszta'] = $DB->selectAssoc("SELECT SUM(`koszt_transport`) as 'transport', SUM(`koszt_dieta`) as 'diety', SUM(`koszt_hotel`) as 'hotele', SUM(`koszt`) as 'calosc' FROM `poslowie_wyjazdy8`");
		
		$output['koszta']['transport'] = (float) $output['koszta']['transport'];
		$output['koszta']['diety'] = (float) $output['koszta']['diety'];
		$output['koszta']['hotele'] = (float) $output['koszta']['hotele'];
		$output['koszta']['calosc'] = (float) $output['koszta']['calosc'];
		$output['koszta']['pozostale'] = $output['koszta']['calosc'] - $output['koszta']['transport'] - $output['koszta']['diety'] - $output['koszta']['hotele'];
		
		
		
		
		$output['najdrozsze']['calosc'] = $DB->selectAssocs("SELECT id, liczba_dni, liczba_poslow, koszt, nazwa as 'delegacja', lokalizacja FROM `poslowie_wyjazdy_wydarzenia8` ORDER BY koszt DESC LIMIT 4");
		
		$output['najdrozsze']['indywidualnie'] = $DB->selectAssocs("SELECT 
		`s_poslowie_kadencje`.`id`, 
		`s_poslowie_kadencje`.`nazwa`, 
		`s_kluby`.`id` as 'klub_id', 
		`s_kluby`.`skrot`, 
		`mowcy_poslowie`.`mowca_id`, 
		`poslowie_wyjazdy8`.`koszt`,
		`e`.`id` as 'wydarzenie_id', 
		`e`.`lokalizacja`, 
		`e`.`nazwa` as 'delegacja'
		FROM `poslowie_wyjazdy8` 
		JOIN `s_poslowie_kadencje` 
			ON `poslowie_wyjazdy8`.`posel_id` = `s_poslowie_kadencje`.`id` 
		JOIN `s_kluby`
			ON `poslowie_wyjazdy8`.`klub_id` = `s_kluby`.`id` 
		JOIN `mowcy_poslowie`
			ON `s_poslowie_kadencje`.`id` = `mowcy_poslowie`.`posel_id`
		JOIN `poslowie_wyjazdy_wydarzenia8` e
			ON e.id = `poslowie_wyjazdy8`.wydarzenie_id
		WHERE 
			e.deleted = '0' AND `poslowie_wyjazdy8`.deleted = '0'
		ORDER BY 
			`poslowie_wyjazdy8`.`koszt` DESC
		LIMIT 6
		");
		
		
		
		$data = $DB->selectAssocs("
		SELECT 
			`poslowie_wyjazdy_wydarzenia8`.`id`, 
			`poslowie_wyjazdy_wydarzenia8`.`lokalizacja`, 
			`poslowie_wyjazdy_wydarzenia8`.`nazwa`, 
			`poslowie_wyjazdy_wydarzenia8`.`liczba_dni`, 
			`poslowie_wyjazdy_wydarzenia8`.`liczba_poslow`, 
			`poslowie_wyjazdy_wydarzenia8`.`data_start`, 
			`poslowie_wyjazdy_wydarzenia8`.`data_stop`, 
			`s_poslowie_kadencje`.`id` as `posel_id`,
			`s_poslowie_kadencje`.`nazwa` as `posel_nazwa`, 
			`s_poslowie_kadencje`.`pkw_plec` as `plec`, 
			`poslowie_wyjazdy8`.`glosowania_daty`,
			`poslowie_wyjazdy8`.`koszt_dieta`,
			`poslowie_wyjazdy8`.`koszt_transport`,
			`poslowie_wyjazdy8`.`koszt_hotel`,
		    `mowcy_poslowie`.`mowca_id`,
		    `s_kluby`.`id` as `klub_id`,
		    `s_kluby`.`skrot` as `klub_skrot`
		FROM `poslowie_wyjazdy8` 
			JOIN `poslowie_wyjazdy_wydarzenia8` 
				ON `poslowie_wyjazdy8`.`wydarzenie_id` = `poslowie_wyjazdy_wydarzenia8`.`id` 
			JOIN `s_poslowie_kadencje` 
				ON `poslowie_wyjazdy8`.`posel_id` = `s_poslowie_kadencje`.`id` 
			JOIN `s_kluby` 
				ON `poslowie_wyjazdy8`.`klub_id` = `s_kluby`.`id` 
			JOIN `mowcy_poslowie` 
				ON `s_poslowie_kadencje`.`id` = `mowcy_poslowie`.`posel_id`
		ORDER BY 
			`poslowie_wyjazdy_wydarzenia8`.`data_start` ASC,
			`poslowie_wyjazdy8`.`koszt_dieta` DESC
		");
		
		
		$wydarzenia = array();
		foreach( $data as $d ) {
			$wydarzenia[ $d['id'] ]['data'] = array(
				'id' => $d['id'],
				'lokalizacja' => $d['lokalizacja'],
				'delegacja' => $d['delegacja'],
				'liczba_dni' => $d['liczba_dni'],
				'liczba_poslow' => $d['liczba_poslow'],
				'date_start' => $d['date_start'],
				'date_stop' => $d['date_stop'],
			);
			$wydarzenia[ $d['id'] ]['poslowie'][] = array(
				'id' => $d['posel_id'],
				'nazwa' => $d['posel_nazwa'],
				'mowca_id' => $d['mowca_id'],
				'klub_id' => $d['klub_id'],
				'klub_skrot' => $d['klub_skrot'],
				'koszt_dieta' => $d['koszt_dieta'],
				'koszt_transport' => $d['koszt_transport'],
				'koszt_hotel' => $d['koszt_hotel'],
				'plec' => $d['plec'],
				'glosowania_dni' => explode(',', $d['glosowania_daty']),
			);
		}
		
		unset( $data );
		$output['wydarzenia'] = array_values( $wydarzenia );
		
		
				
		return $output;
        

    }

    public function getWorldStats()
    {
        App::import('model', 'DB');
        $DB = new DB();

        $sql = <<<SQL
SELECT l.iso2cc AS code, MIN(kraj) AS kraj, COUNT(DISTINCT e.id) AS ilosc_wyjazdow, SUM(w.koszt) AS laczna_kwota
FROM poslowie_wyjazdy w
INNER JOIN poslowie_wyjazdy_wydarzenia e ON (w.wydarzenie_id = e.id)
INNER JOIN poslowie_wyjazdy_lokalizacje l ON (l.lokalizacja = e.lokalizacja)
WHERE e.deleted = '0' AND w.deleted = '0'
GROUP BY l.iso2cc
ORDER BY laczna_kwota DESC
SQL;
        return $DB->selectAssocs($sql);
    }
    
    public function getWorldStats8()
    {
        App::import('model', 'DB');
        $DB = new DB();

        $sql = <<<SQL
SELECT `poslowie_wyjazdy_lokalizacje8`.`iso2cc` as 'code', `poslowie_wyjazdy_lokalizacje8`.`kraj` as 'kraj', COUNT(DISTINCT(poslowie_wyjazdy_wydarzenia8.id)) as 'ilosc_wyjazdow', SUM(poslowie_wyjazdy8.koszt) as 'laczna_kwota' FROM `poslowie_wyjazdy8` JOIN `poslowie_wyjazdy_wydarzenia8` ON `poslowie_wyjazdy8`.`wydarzenie_id` = `poslowie_wyjazdy_wydarzenia8`.`id` JOIN `poslowie_wyjazdy_wydarzenia_lokalizacje8` ON `poslowie_wyjazdy_wydarzenia8`.`id` = `poslowie_wyjazdy_wydarzenia_lokalizacje8`.`wydarzenie_id` JOIN `poslowie_wyjazdy_lokalizacje8` ON `poslowie_wyjazdy_wydarzenia_lokalizacje8`.`lokalizacja_id` = `poslowie_wyjazdy_lokalizacje8`.`id` GROUP BY `poslowie_wyjazdy_lokalizacje8`.`id` ORDER BY `laczna_kwota` DESC
SQL;
        return $DB->selectAssocs($sql);
    }

    public function getCountryDetails($countryCode)
    {
        App::import('model', 'DB');
        $DB = new DB();

        $countryCode = $DB->DB->real_escape_string($countryCode);

        $sql = <<<SQL
SELECT
    l.iso2cc AS country_code,
    e.id AS wydarzenie_id,
    e.delegacja,
    kraj,
    miasto,
    e.wniosek_nr,
    e.liczba_dni,
    e.date_start AS od,
    e.date_stop AS do,
    w.id,
    w.koszt_transport,
    w.koszt_dieta,
    w.koszt_hotel,
    w.koszt_dojazd,
    w.koszt_ubezpieczenie,
    w.koszt_fundusz,
    w.koszt_kurs,
    w.koszt_zaliczki,
    w.koszt AS koszt_suma,
    p.nazwa AS posel,
    k.nazwa AS klub,
    k.glosowania_skrot AS klub_skrot

FROM poslowie_wyjazdy w
INNER JOIN poslowie_wyjazdy_wydarzenia e ON (w.wydarzenie_id = e.id)
INNER JOIN poslowie_wyjazdy_lokalizacje l ON (l.lokalizacja = e.lokalizacja)
INNER JOIN s_poslowie_kadencje p ON (w.posel_id = p.id)
LEFT OUTER JOIN s_kluby k ON (w.klub_id = k.id)
WHERE l.iso2cc = '$countryCode' AND e.deleted = '0' AND w.deleted = '0'
ORDER BY e.date_start, e.id, w.id
SQL;

        $rows = $DB->selectAssocs($sql);

        if (!$rows) {
            throw new NotFoundException();
        }

        $tree = array();
        $wydarzenie = null;

        $last_wydarzenie = null;

        for ($i = 0; $i < count($rows); $i++) {
            $row = $rows[$i];
            if ($row['wydarzenie_id'] != $last_wydarzenie) {
                //if ($wydarzenie != null)
                $w = array_intersect_key($row, array_flip(array(
                        'wydarzenie_id', 'id', 'delegacja', 'country_code', 'kraj', 'miasto', 'wniosek_nr', 'liczba_dni', 'od', 'do'))
                );
                $w['poslowie'] = array();

                array_push($tree, $w);
                $wydarzenie = &$tree[count($tree) - 1];
            }

            array_push($wydarzenie['poslowie'], array_intersect_key($row, array_flip(array(
                'posel',
                'klub',
                'klub_skrot',
                'koszt_suma',
                'koszt_transport',
                'koszt_dieta',
                'koszt_hotel',
                'koszt_dojazd',
                'koszt_ubezpieczenie',
                'koszt_fundusz',
                'koszt_kurs',
                'koszt_zaliczki',
            ))));

//            if ($i == count($rows) - 1 && $row['wydarzenie_id'] != $last_wydarzenie) {
//                array_push($tree, $wydarzenie); // push last
//            }
            $last_wydarzenie = $row['wydarzenie_id'];
        }

        return $tree;
    }
    
    public function getCountryDetails8($countryCode)
    {
        App::import('model', 'DB');
        $DB = new DB();

        $countryCode = $DB->DB->real_escape_string($countryCode);

        $sql = <<<SQL
SELECT
    l.iso2cc AS country_code,
    e.id AS wydarzenie_id,
    e.nazwa as 'delegacja',
    kraj,
    e.numer as 'wniosek_nr',
    e.liczba_dni,
    e.data_start AS od,
    e.data_stop AS do,
    w.id,
    w.osoba,
    w.koszt_transport,
    w.koszt_dieta,
    w.koszt_hotel,
    w.koszt_dojazd,
    w.koszt_ubezpieczenie,
    w.koszt_zaliczki,
    w.koszt AS koszt_suma,
    p.nazwa AS posel,
    k.nazwa AS klub,
    k.glosowania_skrot AS klub_skrot

FROM `poslowie_wyjazdy8` w
INNER JOIN `poslowie_wyjazdy_wydarzenia8` e ON (w.wydarzenie_id = e.id)
INNER JOIN `poslowie_wyjazdy_wydarzenia_lokalizacje8` el ON (el.wydarzenie_id = e.id)
INNER JOIN `poslowie_wyjazdy_lokalizacje8` l ON (l.id = el.lokalizacja_id)
LEFT JOIN s_poslowie_kadencje p ON (w.posel_id = p.id)
LEFT JOIN s_kluby k ON (w.klub_id = k.id)
WHERE l.iso2cc = '$countryCode' AND e.deleted = '0' AND w.deleted = '0'
ORDER BY e.data_start, e.id, w.id
SQL;

        $rows = $DB->selectAssocs($sql);

        if (!$rows) {
            throw new NotFoundException();
        }

        $tree = array();
        $wydarzenie = null;

        $last_wydarzenie = null;

        for ($i = 0; $i < count($rows); $i++) {
            $row = $rows[$i];
            if ($row['wydarzenie_id'] != $last_wydarzenie) {
                //if ($wydarzenie != null)
                $w = array_intersect_key($row, array_flip(array(
                        'wydarzenie_id', 'id', 'delegacja', 'country_code', 'kraj', 'miasto', 'wniosek_nr', 'liczba_dni', 'od', 'do'))
                );
                $w['poslowie'] = array();

                array_push($tree, $w);
                $wydarzenie = &$tree[count($tree) - 1];
            }

            array_push($wydarzenie['poslowie'], array_intersect_key($row, array_flip(array(
                'osoba',
                'posel',
                'klub',
                'klub_skrot',
                'koszt_suma',
                'koszt_transport',
                'koszt_dieta',
                'koszt_hotel',
                'koszt_dojazd',
                'koszt_ubezpieczenie',
                'koszt_fundusz',
                'koszt_zaliczki',
            ))));

//            if ($i == count($rows) - 1 && $row['wydarzenie_id'] != $last_wydarzenie) {
//                array_push($tree, $wydarzenie); // push last
//            }
            $last_wydarzenie = $row['wydarzenie_id'];
        }

        return $tree;
    }
    
}

<?	
	
	$data = $this->DB->selectAssocs("SELECT 
		`osoby`.`id` as `osoba_id`, `osoby`.`imiona`, `osoby`.`nazwisko`, `osoby`.`plec`, `osoby`.`data_urodzenia`, `osoby`.`privacy_level`, 
		`organizacje`.`id` as `organizacja_id`, `organizacje`.`nazwa`, `organizacje`.`forma_prawna_str` as `forma`, `organizacje`.`data_rejestracji`, `organizacje`.`adres_miejscowosc` as `miejscowosc`, `organizacje`.`kapital_zakladowy`, `organizacje`.`krs_str` as `krs`, 
		`_osoby`.`reprezentat` AS 'osoba_reprezentant', `_osoby`.`reprezentat_funkcja` AS 'osoba_reprezentat_funkcja', `_osoby`.`wspolnik` AS 'osoba_wspolnik', `_osoby`.`akcjonariusz` AS 'osoba_akcjonariusz', `_osoby`.`prokurent` AS 'osoba_prokurent', `_osoby`.`nadzorca` AS 'osoba_nadzorca', `_osoby`.`zalozyciel` AS 'osoba_zalozyciel', 
		`_organizacje`.`reprezentat` AS 'organizacja_reprezentant', `_organizacje`.`reprezentat_funkcja` AS 'organizacja_reprezentat_funkcja', `_organizacje`.`wspolnik` AS 'organizacja_wspolnik', `_organizacje`.`akcjonariusz` AS 'organizacja_akcjonariusz', `_organizacje`.`prokurent` AS 'organizacja_prokurent', `_organizacje`.`nadzorca` AS 'organizacja_nadzorca', `_organizacje`.`zalozyciel` AS 'organizacja_zalozyciel' 
	FROM `krs_osoby-pozycje` AS `_osoby` 
	JOIN `krs_osoby-pozycje` AS `_organizacje`
		ON `_osoby`.`osoba_id` = `_organizacje`.`osoba_id`
	JOIN `krs_osoby` AS `osoby` 
		ON `osoby`.`id` = `_osoby`.`osoba_id` 
	JOIN `krs_pozycje` AS `organizacje` 
		ON `organizacje`.`id` = `_organizacje`.`pozycja_id`
	WHERE `osoby`.`deleted` = '0' AND `_osoby`.`pozycja_id` = '" . addslashes( $id ) . "' AND `_osoby`.`deleted`='0' AND `_organizacje`.`deleted`='0'");
	
	
	
	$nodes = array();
	$osoby_ids = array();
	$organizacje_ids = array();
	
	$relationships = array();
	$relationships_ids = array();
	
	foreach( $data as $d ) {
		
		if( !in_array($d['osoba_id'], $osoby_ids) ) {
			
			$osoby_ids[] = $d['osoba_id'];
			$nodes[] = array(
				'label' => 'osoba',
				'id' => 'osoba' . $d['osoba_id'],
				'mp_id' => $d['osoba_id'],
				'data' => array(
					'privacy_level' => $d['privacy_level'],
					'nazwisko' => $d['nazwisko'],
					'imiona' => $d['imiona'],
					'data_urodzenia' => $d['data_urodzenia'],
					'plec' => $d['plec'],
				),
			);
			
		}
		
		if( !in_array($d['organizacja_id'], $organizacje_ids) ) {
			
			$organizacje_ids[] = $d['organizacja_id'];
			$nodes[] = array(
				'label' => 'podmiot',
				'id' => 'podmiot' . $d['organizacja_id'],
				'mp_id' => $d['organizacja_id'],
				'data' => array(
					'nazwa' => $d['nazwa'],
					'forma' => $d['forma'],
					'krs' => $d['krs'],
					// 'kapital_zakladowy' => $d['kapital_zakladowy'],
					'miejscowosc' => $d['miejscowosc'],
					'data_rejestracji' => $d['data_rejestracji'],
				),
			);
			
		}
		
		
		if( $d['osoba_reprezentant'] ) {
			
			$r = array(
				'type' => $d['osoba_reprezentat_funkcja'] ? $d['osoba_reprezentat_funkcja'] : 'REPREZENTANT',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $id,
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		if( $d['osoba_wspolnik'] ) {
			
			$r = array(
				'type' => 'WSPÓLNIK',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $id,
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		if( $d['osoba_akcjonariusz'] ) {
			
			$r = array(
				'type' => 'JEDYNY AKCJONARIUSZ',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $id,
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		if( $d['osoba_prokurent'] ) {
			
			$r = array(
				'type' => 'PROKURENT',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $id,
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		if( $d['osoba_nadzorca'] ) {
			
			$r = array(
				'type' => 'NADZORCA',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $id,
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		if( $d['osoba_zalozyciel'] ) {
			
			$r = array(
				'type' => 'ZAŁOŻYCIEL',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $id,
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		
		
		
		
		
		
		
		if( $d['organizacja_reprezentant'] ) {
			
			$r = array(
				'type' => $d['organizacja_reprezentat_funkcja'] ? $d['organizacja_reprezentat_funkcja'] : 'REPREZENTANT',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $d['organizacja_id'],
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		if( $d['organizacja_wspolnik'] ) {
			
			$r = array(
				'type' => 'WSPÓLNIK',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $d['organizacja_id'],
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		if( $d['organizacja_akcjonariusz'] ) {
			
			$r = array(
				'type' => 'JEDYNY AKCJONARIUSZ',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $d['organizacja_id'],
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		if( $d['organizacja_prokurent'] ) {
			
			$r = array(
				'type' => 'PROKURENT',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $d['organizacja_id'],
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		if( $d['organizacja_nadzorca'] ) {
			
			$r = array(
				'type' => 'NADZORCA',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $d['organizacja_id'],
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		if( $d['organizacja_zalozyciel'] ) {
			
			$r = array(
				'type' => 'ZAŁOŻYCIEL',
				'start' => 'osoba' . $d['osoba_id'],
				'end' => 'podmiot' . $d['organizacja_id'],
			);

			$rh = $r['type'] . $r['start'] . $r['end'];
			if( !in_array($rh, $relationships_ids) ) {
				$relationships_ids[] = $rh;
				$relationships[] = $r;
			}
			
		}
		
		
		
		
	
		
	}
	
	
	
	
	return array(
		'root' => 'podmiot' . $id,
		'nodes' => $nodes,
		'relationships' => $relationships,
	);

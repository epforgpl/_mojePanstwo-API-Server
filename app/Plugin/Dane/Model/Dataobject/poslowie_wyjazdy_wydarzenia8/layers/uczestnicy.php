<?
	
	return $this->DB->selectAssocs("
		SELECT 
			`poslowie_wyjazdy8`.id,
			`poslowie_wyjazdy8`.osoba,
			`poslowie_wyjazdy8`.koszt_transport,
			`poslowie_wyjazdy8`.koszt_dieta,
			`poslowie_wyjazdy8`.koszt_hotel,
			`poslowie_wyjazdy8`.koszt_dojazd,
			`poslowie_wyjazdy8`.koszt_ubezpieczenie,
			`poslowie_wyjazdy8`.koszt_zaliczki,
			`poslowie_wyjazdy8`.koszt,
			`poslowie_wyjazdy8`.glosowania_daty,
			s_poslowie_kadencje.id AS 'poslowie.id', 
			mowcy_poslowie.mowca_id AS 'ludzie.id', 
			s_poslowie_kadencje.nazwa AS 'poslowie.nazwa', 
			s_kluby.id AS 'sejm_kluby.id', 
			s_kluby.nazwa AS 'sejm_kluby.nazwa', 
			s_kluby.skrot AS 'sejm_kluby.skrot' 
		FROM `poslowie_wyjazdy8` 
		LEFT JOIN s_poslowie_kadencje ON `poslowie_wyjazdy8`.posel_id = s_poslowie_kadencje.id
		LEFT JOIN mowcy_poslowie ON `poslowie_wyjazdy8`.posel_id = mowcy_poslowie.posel_id
		LEFT JOIN s_kluby ON `poslowie_wyjazdy8`.klub_id = s_kluby.id
		WHERE 
			`poslowie_wyjazdy8`.deleted='0' AND 
			`poslowie_wyjazdy8`.wydarzenie_id = '" . addslashes( $id ) . "'
	");
<?php

class Finanse extends AppModel
{

    public $useTable = false;

    public function getCommunePopCount($id)
    {
        App::import('model', 'DB');
        $DB = new DB();
        return (int)$DB->selectValue('SELECT liczba_ludnosci FROM pl_gminy WHERE id = ' . ((int)$id));
    }

    public function getBudgetSpendings()
    {

        App::import('model', 'DB');
        $DB = new DB();

        $data = $DB->selectAssocs("
			SELECT 
				`pl_budzety_wydatki_dzialy`.`id` as 'dzial_id',  
				`pl_budzety_wydatki_dzialy`.`tresc` as 'dzial_nazwa',  
				`pl_budzety_wydatki_dzialy`.`plan` as 'dzial_plan', 
				`pl_budzety_wydatki_rozdzialy`.`id` as 'rozdzial_id',  
				`pl_budzety_wydatki_rozdzialy`.`tresc` as 'rozdzial_nazwa',  
				`pl_budzety_wydatki_rozdzialy`.`plan` as 'rozdzial_plan' 
			FROM `pl_budzety_wydatki_dzialy` 
			JOIN `pl_budzety_wydatki_rozdzialy` ON `pl_budzety_wydatki_rozdzialy`.`dzial_id` = `pl_budzety_wydatki_dzialy`.`id` 
			ORDER BY
				`pl_budzety_wydatki_dzialy`.`plan` DESC, 
				`pl_budzety_wydatki_rozdzialy`.`plan` DESC
		");

        $dzialy = array();
        foreach ($data as $d) {

            $dzialy[$d['dzial_id']]['id'] = $d['dzial_id'];
            $dzialy[$d['dzial_id']]['nazwa'] = $d['dzial_nazwa'];
            $dzialy[$d['dzial_id']]['plan'] = $d['dzial_plan'];
            $dzialy[$d['dzial_id']]['rozdzialy'][] = array(
                'id' => $d['rozdzial_id'],
                'nazwa' => $d['rozdzial_nazwa'],
                'plan' => $d['rozdzial_plan'],
            );

        }

        $dzialy = array_values($dzialy);

        return array(
            'dzialy' => $dzialy,
        );

    }

    public function getBudgetSections()
    {

        App::import('model', 'DB');
        $DB = new DB();

        $data = $DB->selectAssocs("
		SELECT 
			`pl_budzety_wydatki_dzialy`.`id` as 'dzial.id', 
			`pl_budzety_wydatki_dzialy`.`tresc` as 'dzial.nazwa', 
			GROUP_CONCAT(`pl_budzety_wydatki_rozdzialy`.`tresc` SEPARATOR ', ') as 'dzial.opis'
		FROM 
			`pl_budzety_wydatki_dzialy` 
		LEFT JOIN	
			`pl_budzety_wydatki_rozdzialy` 
				ON `pl_budzety_wydatki_rozdzialy`.`dzial_id` = `pl_budzety_wydatki_dzialy`.`id` 
				AND `pl_budzety_wydatki_rozdzialy`.`local` = '1'
				AND `pl_budzety_wydatki_rozdzialy`.`tresc` NOT LIKE '%uchylony%'
		GROUP BY
			`pl_budzety_wydatki_dzialy`.`id` 
		ORDER BY
			`pl_budzety_wydatki_dzialy`.`tresc` ASC 
		");

        return $data;

    }

    /**
     * @param int $gmina_id
     * @return array Wydatki gminy dla każdego działu
     */
    public function getCommuneData($gmina_id = 0)
    {
        App::import('model', 'DB');
        $DB = new DB();

        $id = (int)$gmina_id;

        $ranges = array(
            array(0, 20000),
            array(20000, 50000),
            array(50000, 100000),
            array(100000, 500000),
            array(500000, 999999999)
        );

        $commune = $DB->selectAssoc("SELECT id, liczba_ludnosci FROM pl_gminy WHERE id = $id");

        $range = 0;
        foreach ($ranges as $i => $r) {
            if ($commune['liczba_ludnosci'] >= $r[0] && $commune['liczba_ludnosci'] <= $r[1]) {
                $range = $i;
                break;
            }
        }

        $data = array();
        $data['stats'] = array();

        $data['sections'] = $DB->selectAssocs("
            SELECT
              pl_budzety_wydatki_dzialy.id,
              pl_budzety_wydatki_dzialy.src,
              pl_budzety_wydatki_dzialy.tresc,
              mf_wydatki_dzialy_zakresy.sum_wydatki,
              mf_wydatki_dzialy_zakresy.wydatki_min_gmina_id,
              mf_wydatki_dzialy_zakresy.wydatki_max_gmina_id,
              mf_wydatki_dzialy_zakresy.zakres
            FROM
              mf_wydatki_dzialy_zakresy
            JOIN
              pl_budzety_wydatki_dzialy
                ON pl_budzety_wydatki_dzialy.id = mf_wydatki_dzialy_zakresy.dzial_id
            WHERE
              mf_wydatki_dzialy_zakresy.zakres = '$range'
            GROUP BY
              mf_wydatki_dzialy_zakresy.dzial_id
            ORDER BY
              mf_wydatki_dzialy_zakresy.sum_wydatki DESC
        ");

        foreach ($data['sections'] as $i => $section) {
            $gmina_min = $DB->selectAssoc("
                SELECT
                  sum_wydatki,
                  pl_gminy.nazwa
                FROM
                  mf_wydatki_gminy_dzialy
                JOIN
                  pl_gminy ON pl_gminy.id = mf_wydatki_gminy_dzialy.gmina_id
                WHERE
                  gmina_id = " . $section['wydatki_min_gmina_id'] . " AND
                  dzial_id = " . $section['id'] . "
            ");

            $data['sections'][$i]['min'] = $gmina_min['sum_wydatki'];
            $data['sections'][$i]['min_nazwa'] = is_null($gmina_min['nazwa']) ? 'Brak' : $gmina_min['nazwa'];

            $gmina_max = $DB->selectAssoc("
                SELECT
                  sum_wydatki,
                  pl_gminy.nazwa
                FROM
                  mf_wydatki_gminy_dzialy
                JOIN
                  pl_gminy ON pl_gminy.id = mf_wydatki_gminy_dzialy.gmina_id
                WHERE
                  gmina_id = " . $section['wydatki_max_gmina_id'] . " AND
                  dzial_id = " . $section['id'] . "
            ");

            $data['sections'][$i]['max'] = $gmina_max['sum_wydatki'];
            $data['sections'][$i]['max_nazwa'] = is_null($gmina_max['nazwa']) ? 'Brak' : $gmina_max['nazwa'];


            $data['sections'][$i]['commune'] = $DB->selectValue("
                SELECT
                  sum_wydatki
                FROM
                  mf_wydatki_gminy_dzialy
                WHERE
                  gmina_id = " . $id . " AND
                  dzial_id = " . $section['id'] . "
            ");
        }

        foreach ($data['sections'] as $i => $section) {
            $data['sections'][$i]['buckets'] = array();
            $min = (int)$section['min'];
            $max = (int)$section['max'];
            $segment = (int)(($max - $min) / 10);
            $segments = array();
            for ($m = 1; $m <= 10; $m++) {
                $segments[] = array(
                    'min' => $min + $m * $segment,
                    'max' => $min + ($m + 1) * $segment
                );
            }

            foreach ($segments as $segment) {
                $count = (int)$DB->selectValue("
                    SELECT
                        COUNT(*)
                    FROM
                      mf_wydatki_gminy_dzialy
                    WHERE
                      dzial_id = " . $section['id'] . " AND
                      zakres = '" . $section['zakres'] . "' AND
                      sum_wydatki
                        BETWEEN " . $segment['min'] . " AND " . $segment['max'] . "
                ");
                $data['sections'][$i]['buckets'][] = array(
                    'count' => $count,
                    'height' => $count
                );
            }
        }

        return $data;
    }

    public function getBudgetData()
    {
        App::import('model', 'DB');
        $DB = new DB();

        $data = array();
        $data['stats'] = array();

        $data['stats']['sum'] = (float)$DB->selectValue("
            SELECT sum_wydatki
            FROM mf_wydatki_roczniki
            WHERE rocznik = 2014
        ");

        $data['sections'] = $DB->selectAssocs("
            SELECT
              pl_budzety_wydatki_dzialy.id,
              pl_budzety_wydatki_dzialy.src,
              pl_budzety_wydatki_dzialy.tresc,
              mf_wydatki_dzialy.sum_wydatki,
              mf_wydatki_dzialy.wydatki_min_gmina_id,
              mf_wydatki_dzialy.wydatki_max_gmina_id
            FROM
              mf_wydatki_dzialy
            JOIN
              pl_budzety_wydatki_dzialy
                ON pl_budzety_wydatki_dzialy.id = mf_wydatki_dzialy.dzial_id
            GROUP BY
              mf_wydatki_dzialy.dzial_id
            ORDER BY
              mf_wydatki_dzialy.sum_wydatki DESC
        ");

        foreach ($data['sections'] as $i => $section) {
            $gmina_min = $DB->selectAssoc("
                SELECT
                  sum_wydatki,
                  pl_gminy.nazwa
                FROM
                  mf_wydatki_gminy_dzialy
                JOIN
                  pl_gminy ON pl_gminy.id = mf_wydatki_gminy_dzialy.gmina_id
                WHERE
                  gmina_id = " . $section['wydatki_min_gmina_id'] . " AND
                  dzial_id = " . $section['id'] . "
            ");

            $data['sections'][$i]['min'] = $gmina_min['sum_wydatki'];
            $data['sections'][$i]['min_nazwa'] = $gmina_min['nazwa'];

            $gmina_max = $DB->selectAssoc("
                SELECT
                  sum_wydatki,
                  pl_gminy.nazwa
                FROM
                  mf_wydatki_gminy_dzialy
                JOIN
                  pl_gminy ON pl_gminy.id = mf_wydatki_gminy_dzialy.gmina_id
                WHERE
                  gmina_id = " . $section['wydatki_max_gmina_id'] . " AND
                  dzial_id = " . $section['id'] . "
            ");

            $data['sections'][$i]['max'] = $gmina_max['sum_wydatki'];
            $data['sections'][$i]['max_nazwa'] = $gmina_max['nazwa'];
        }

        foreach ($data['sections'] as $i => $section) {
            $data['sections'][$i]['buckets'] = array();
            $min = (int)$section['min'];
            $max = (int)$section['max'];
            $segment = (int)(($max - $min) / 10);
            $segments = array();
            for ($m = 1; $m <= 10; $m++) {
                $segments[] = array(
                    'min' => $min + $m * $segment,
                    'max' => $min + ($m + 1) * $segment
                );
            }

            foreach ($segments as $segment) {
                $count = (int)$DB->selectValue("
                    SELECT
                        COUNT(*)
                    FROM
                      mf_wydatki_gminy_dzialy
                    WHERE
                      dzial_id = " . $section['id'] . " AND
                      sum_wydatki
                        BETWEEN " . $segment['min'] . " AND " . $segment['max'] . "
                ");
                $data['sections'][$i]['buckets'][] = array(
                    'count' => $count,
                    'height' => $count
                );
            }
        }

        return $data;
    }


    // stara wersja API
    public function getBudgetData2($gmina_id = null)
    {

        App::import('model', 'DB');
        $DB = new DB();


        // Configure::write('debug', 2);

        // parametry zewnetrzne
        $data = '2014Q2';
        $gmina = $DB->selectAssoc("SELECT id, nazwa, teryt FROM pl_gminy WHERE id='$gmina_id'");
        $teryt = $gmina['teryt'];


        // Przedzia³y wielkoœci gmin
        $ranges = array();
        $ranges[] = array('min' => 0, 'max' => 20000);
        $ranges[] = array('min' => 20000, 'max' => 50000);
        $ranges[] = array('min' => 50000, 'max' => 100000);
        $ranges[] = array('min' => 100000, 'max' => 500000);
        $ranges[] = array('min' => 500000, 'max' => 999999999);


        $data = explode('q', strtolower($data));
        $rok = substr($data[0], 2, 2);
        $miesiac = $data[1];
        $minLiczba = null;
        $maxLiczba = null;
        $liczbaLudnosci = null;

        // Dane podstawowe/globalne
        $sql = sprintf('
			SELECT
				d.id as \'dzial_id\', dzial,
				min, g1.nazwa AS min_nazwa,
				max, g2.nazwa AS max_nazwa,
				sum_section, d.tresc
			FROM finance_date f
			JOIN pl_budzety_wydatki_dzialy d ON d.src = f.dzial
			LEFT JOIN pl_gminy g1 ON g1.teryt = min_teryt
			LEFT JOIN pl_gminy g2 ON g2.teryt = max_teryt
			WHERE rok = %d AND kwartal = %d
			ORDER BY sum_section DESC',
            $rok, $miesiac
        );
        $result = $DB->q($sql);


        $results = array();
        $sum = 0;
        while ($row = $result->fetch_assoc()) {
            $results[$row['dzial']] = $row;
            $results[$row['dzial']]['buckets'] = array_fill(0, 10, null);
            $sum += $row['sum_section'];
        }

        $this->_getHistogram($DB, $results, 'buckets', $rok, $miesiac);


        // Jezeli mamy okreslona gmine


        if ($teryt) {
            // dane dla gminy
            $sql = sprintf("
				SELECT
					dzial, sum_section, liczba_ludnosci
				FROM finance_teryt
				WHERE rok = %d AND kwartal = %d AND teryt = '%s'",
                $rok, $miesiac, $teryt
            );
            $result = $DB->q($sql);

            $terytSum = 0;
            $dzial = array();
            while ($row = $result->fetch_assoc()) {
                $dzial[] = $row['dzial'];
                $results[$row['dzial']]['teryt_buckets'] = array_fill(0, 10, null);
                $results[$row['dzial']]['teryt_sum_section'] = $row['sum_section'];
                $terytSum += $row['sum_section'];

                if ($liczbaLudnosci == null) {
                    $liczbaLudnosci = $row['liczba_ludnosci'];
                }
            }
            // Dane sumaryczne dla gminy
            foreach ($dzial as $_dzial) {
                $results[$_dzial]['teryt_sum'] = $terytSum;
                $results[$_dzial]['teryt_sum_section_percent'] = !$terytSum ? 0 : round(100 * $results[$_dzial]['teryt_sum_section'] / $terytSum, 2);
            }

            // Dane dla gmin o podobnej wielkosci
            if ($liczbaLudnosci != null) {
                foreach ($ranges as $range) {
                    if ($liczbaLudnosci >= $range['min'] && $liczbaLudnosci < $range['max']) {
                        $minLiczba = $range['min'];
                        $maxLiczba = $range['max'];
                    }
                }

                $this->_getHistogram($DB, $results, 'teryt_buckets', $rok, $miesiac, $minLiczba, $maxLiczba);

                $sql = sprintf("
					SELECT
						dzial,
						min_sum_section, min_teryt, g1.nazwa AS min_teryt_name,
						max_sum_section, max_teryt, g2.nazwa AS max_teryt_name
					FROM (
						SELECT
							dzial,
							min_sum_section, LPAD(IF(min_teryt %% 100 = 0, min_teryt + 1, min_teryt), 6, '0') AS min_teryt,
							max_sum_section, LPAD(IF(max_teryt %% 100 = 0, max_teryt + 1, max_teryt), 6, '0') AS max_teryt
						FROM (
							SELECT
								dzial,
								MIN(sum_section) AS min_sum_section,
								IF(LOCATE(',', GROUP_CONCAT(teryt ORDER BY sum_section ASC)) > 0, SUBSTRING(GROUP_CONCAT(teryt ORDER BY sum_section ASC), 1, LOCATE(',',GROUP_CONCAT(teryt ORDER BY sum_section ASC)) - 1), teryt) AS min_teryt,
								MAX(sum_section) AS max_sum_section,
								IF(LOCATE(',', GROUP_CONCAT(teryt ORDER BY sum_section DESC)) > 0, SUBSTRING(GROUP_CONCAT(teryt ORDER BY sum_section DESC), 1, LOCATE(',',GROUP_CONCAT(teryt ORDER BY sum_section DESC)) - 1), teryt) AS max_teryt
							FROM finance_teryt
							WHERE rok = %d AND kwartal = %d  AND liczba_ludnosci >= %d AND liczba_ludnosci < %d
							GROUP BY dzial
						) AS ww
					) AS xx
					LEFT JOIN pl_gminy g1 ON g1.teryt = min_teryt
					LEFT JOIN pl_gminy g2 ON g2.teryt = max_teryt",
                    $rok, $miesiac, $minLiczba, $maxLiczba
                );
                $result = $DB->q($sql);
                while ($row = $result->fetch_assoc()) {
                    $results[$row['dzial']]['teryt_min_sum_section'] = $row['min_sum_section'];
                    $results[$row['dzial']]['teryt_max_sum_section'] = $row['max_sum_section'];
                    $results[$row['dzial']]['teryt_min_nazwa'] = $row['min_teryt_name'];
                    $results[$row['dzial']]['teryt_max_nazwa'] = $row['max_teryt_name'];
                }

                // Gmina na tle podobnych w kazdej kategorii
                foreach ($dzial as $_dzial) {
                    $left = $results[$_dzial]['teryt_min_sum_section'];
                    $right = $results[$_dzial]['teryt_max_sum_section'];
                    $v = $results[$_dzial]['teryt_sum_section'];;
                    $results[$_dzial]['teryt_section_percent'] = round(100 * ($v - $left) / ($right - $left));
                }
            }
        }

        // Wynik finalny
        $finalResult = array(
            'sections' => array(),
            'stats' => array(
                'sum' => $sum,
                'min_liczba_ludnosci' => $minLiczba,
                'max_liczba_ludnosci' => $maxLiczba,
                'teryt_liczba_ludnosci' => $liczbaLudnosci,
                'teryt_nazwa' => @$gmina['nazwa'],
            )
        );
        foreach ($results as $item) {
            $finalResult['sections'][] = array(
                'id' => $item['dzial_id'],
                'nazwa' => @$item['tresc'],
                'min' => @$item['min'],
                'max' => @$item['max'],
                'min_nazwa' => @$item['min_nazwa'],
                'max_nazwa' => @$item['max_nazwa'],
                'sum_section' => @$item['sum_section'],
                'buckets' => @$item['buckets'],
                'teryt_sum' => @$item['teryt_sum'],
                'teryt_sum_section' => @$item['teryt_sum_section'],
                'teryt_sum_section_percent' => @$item['teryt_sum_section_percent'],
                'teryt_min' => @$item['teryt_min_sum_section'],
                'teryt_max' => @$item['teryt_max_sum_section'],
                'teryt_section_percent' => @$item['teryt_section_percent'],
                'teryt_min_nazwa' => @$item['teryt_min_nazwa'],
                'teryt_max_nazwa' => @$item['teryt_max_nazwa'],
                'teryt_buckets' => @$item['teryt_buckets']
            );
        }
        //debug($finalResult); die();


        $finalResult['gmina'] = $gmina;


        return $finalResult;

    }

    protected function _getHistogram($DB, &$results, $index, $year, $month, $limitDown = null, $limitUp = null)
    {
        $sql = sprintf('
			SELECT
				ft.dzial,
				IF(fd.bucket_size > 0, ROUND((ft.sum_section - fd.min) / fd.bucket_size), 0) AS bucket,
				COUNT(1) AS count,
				ROUND(LN(COUNT(1))) AS height
			FROM finance_teryt ft
			JOIN finance_date fd ON fd.dzial = ft.dzial AND fd.rok = ft.rok AND fd.kwartal = ft.kwartal '
            . ($limitDown ? ' AND liczba_ludnosci >= ' . $limitDown : '')
            . ($limitUp ? ' AND liczba_ludnosci < ' . $limitUp : '')
            . ' WHERE ft.rok = %d AND ft.kwartal = %d
			GROUP BY ft.rok, ft.kwartal, ft.dzial, bucket;',
            $year, $month
        );
        $result = mysql_query($sql);
        $maxDzial = array();
        $result = $DB->q($sql);
        while ($row = $result->fetch_assoc()) {
            if (!isset($maxDzial[$row['dzial']])) {
                $maxDzial[$row['dzial']] = $row['height'];
            } else if ($row['height'] > $maxDzial[$row['dzial']]) {
                $maxDzial[$row['dzial']] = $row['height'];
            }
            $results[$row['dzial']][$index][$row['bucket']] = array('count' => $row['count'], 'height' => $row['height']);
        }
        foreach ($results as $dzial => $data) {
            foreach ($results[$dzial][$index] as $k => $bucket) {
                if ($results[$dzial][$index][$k]) {
                    $results[$dzial][$index][$k]['height'] = $maxDzial[$dzial] > 0 ? round(10 * $bucket['height'] / $maxDzial[$dzial]) : 0;
                }
            }
        }
    }

    public function getPkb()
    {

        App::import('model', 'DB');
        $DB = new DB();

        $data = $DB->selectAssocs("SELECT * FROM pl_PKB ORDER BY rocznik ASC");
        $dol = $DB->selectAssocs("SELECT rocznik, USD FROM kursy_srednie ORDER BY rocznik ASC");
        $bezrobocie = $DB->selectAssocs("SELECT rocznik, v FROM `BDL_data_pl` WHERE `kombinacja_id` = 13931 AND deleted='0' ORDER BY `BDL_data_pl`.`rocznik`  ASC");
        $inflacja = $DB->selectAssocs("SELECT rocznik, v FROM `BDL_data_pl` WHERE `kombinacja_id` = 18971 AND deleted='0' ORDER BY `BDL_data_pl`.`rocznik`  ASC");
        $dlug = $DB->selectAssocs("SELECT * FROM `dlug_publiczny` ORDER BY `rocznik`  ASC");

        $usd = array();
        foreach ($dol as $row) {
            $usd[$row['rocznik']] = $row;
        }

        $dane = array('PKB' => $data, 'USD' => $usd, 'bezrobocie' => $bezrobocie, 'inflacja' => $inflacja, 'dlug' => $dlug);

        return $dane;
    }

    public function getCompareData($p1, $p2)
    {
        App::import('model', 'DB');
        $DB = new DB();

        $wyd_czesci = $DB->selectAssocs("SELECT pl_budzety_wydatki.rocznik, pl_budzety_wydatki.czesc_str, pl_budzety_wydatki.tresc, SUM( pl_budzety_wydatki.plan ) AS plan
          FROM pl_budzety_wydatki
          WHERE pl_budzety_wydatki.rocznik
          IN ( $p1, $p2 )
          AND pl_budzety_wydatki.type =  'czesc'
          AND pl_budzety_wydatki.czesc_id NOT
          IN ( 15, 90, 107 )
          GROUP BY pl_budzety_wydatki.czesc_str, pl_budzety_wydatki.rocznik");

        $wyd_czesci2 = array();
        foreach ($wyd_czesci as $row) {
            $czesc_str = str_pad($row['czesc_str'], 3,'0', STR_PAD_LEFT);
            if (!isset($wyd_czesci2[trim($czesc_str)])) {
                $wyd_czesci2[trim($czesc_str)] = array();
                $wyd_czesci2[trim($czesc_str)]['tresc'] = $row['tresc'];
            }
            if ($row['rocznik'] == $p1) {
                $wyd_czesci2[trim($czesc_str)]['p1'] = $row['plan'];
            } else {
                $wyd_czesci2[trim($czesc_str)]['p2'] = $row['plan'];
            }
        }
        $wyd_czesci = array(
            'wzrost' => array(),
            'spadek' => array(),
            'bd' => array()
        );

        foreach ($wyd_czesci2 as $row) {
            $zmiana = false;
            $zmiana2 = false;

            if (isset($row['p1']) && isset($row['p2']) && $row['p1'] !== 0) {
                $zmiana = $row['p2'] * 100 / $row['p1'] - 100;
                $zmiana2 = $row['p2'] / $row['p1'];
            }
            if ($zmiana > 0) {
                $wyd_czesci['wzrost'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => $row['p1'],
                    'p2' => $row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            } elseif ($zmiana === false) {
                $wyd_czesci['bd'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => @$row['p1'],
                    'p2' => @$row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            } else {
                $wyd_czesci['spadek'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => $row['p1'],
                    'p2' => $row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            }

        }
        $wyd_dzial = $DB->selectAssocs("SELECT pl_budzety_wydatki.rocznik, pl_budzety_wydatki.dzial_str, pl_budzety_wydatki.tresc, SUM( pl_budzety_wydatki.plan ) AS plan
          FROM pl_budzety_wydatki
          WHERE pl_budzety_wydatki.rocznik
          IN ( $p1, $p2 )
         AND pl_budzety_wydatki.type =  'dzial'
          AND pl_budzety_wydatki.czesc_id NOT
        IN ( 15, 90, 107 )
          GROUP BY pl_budzety_wydatki.dzial_str, pl_budzety_wydatki.rocznik");

        $wyd_dzial2 = array();
        foreach ($wyd_dzial as $row) {
            $dzial_str = str_pad($row['dzial_str'], 3,'0', STR_PAD_LEFT);
            if (!isset($wyd_dzial2[trim($dzial_str)])) {
                $wyd_dzial2[trim($dzial_str)] = array();
                $wyd_dzial2[trim($dzial_str)]['tresc'] = $row['tresc'];
            }
            if ($row['rocznik'] == $p1) {
                $wyd_dzial2[trim($dzial_str)]['p1'] = $row['plan'];
            } else {
                $wyd_dzial2[trim($dzial_str)]['p2'] = $row['plan'];
            }
        }
        $wyd_dzial = array(
            'wzrost' => array(),
            'spadek' => array(),
            'bd' => array()
        );
        foreach ($wyd_dzial2 as $row) {
            $zmiana = false;
            $zmiana2 = false;
            if (isset($row['p1']) && isset($row['p2'])) {
                $zmiana = $row['p2'] * 100 / $row['p1'] - 100;
                $zmiana2 = $row['p2'] / $row['p1'];
            }
            if ($zmiana > 0) {
                $wyd_dzial['wzrost'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => $row['p1'],
                    'p2' => $row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            } elseif ($zmiana === false) {
                $wyd_dzial['bd'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => @$row['p1'],
                    'p2' => @$row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            } else {
                $wyd_dzial['spadek'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => $row['p1'],
                    'p2' => $row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            }
        }

        $wyd_rozdzial = $DB->selectAssocs("SELECT pl_budzety_wydatki.rocznik, pl_budzety_wydatki.rozdzial_str, pl_budzety_wydatki.tresc, SUM( pl_budzety_wydatki.plan ) AS plan FROM pl_budzety_wydatki
JOIN pl_budzety_wydatki_rozdzialy
ON pl_budzety_wydatki.rozdzial_str = pl_budzety_wydatki_rozdzialy.src
WHERE pl_budzety_wydatki.rocznik
IN ( $p1, $p2 )
AND pl_budzety_wydatki.type =  'rozdzial'
AND pl_budzety_wydatki.czesc_id
NOT IN ( 15, 90, 107 )
GROUP BY pl_budzety_wydatki.rozdzial_str, pl_budzety_wydatki.rocznik");

        $wyd_rozdzial2 = array();
        foreach ($wyd_rozdzial as $row) {
            $rozdzial_str = str_pad($row['rozdzial_str'], 5,'0', STR_PAD_LEFT);
            if (!isset($wyd_rozdzial2[trim($rozdzial_str)])) {
                $wyd_rozdzial2[trim($rozdzial_str)] = array();
                $wyd_rozdzial2[trim($rozdzial_str)]['tresc'] = $row['tresc'];
            }
            if ($row['rocznik'] == $p1) {
                $wyd_rozdzial2[trim($rozdzial_str)]['p1'] = $row['plan'];
            } else {
                $wyd_rozdzial2[trim($rozdzial_str)]['p2'] = $row['plan'];
            }
        }
        $wyd_rozdzial = array(
            'wzrost' => array(),
            'spadek' => array(),
            'bd' => array()
        );
        foreach ($wyd_rozdzial2 as $row) {
            $zmiana = false;
            $zmiana2 = false;
            if (isset($row['p1']) && isset($row['p2'])) {
                $zmiana = $row['p2'] * 100 / $row['p1'] - 100;
                $zmiana2 = $row['p2'] / $row['p1'];
            }
            if ($zmiana > 0) {
                $wyd_rozdzial['wzrost'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => $row['p1'],
                    'p2' => $row['p2'],
                    'zmiana' => $zmiana,
                    'zmiana2' => $zmiana2
                );
            } elseif ($zmiana === false) {
                $wyd_rozdzial['bd'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => @$row['p1'],
                    'p2' => @$row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            } else {
                $wyd_rozdzial['spadek'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => $row['p1'],
                    'p2' => $row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            }
        }


        $doch_dzial = $DB->selectAssocs("SELECT rocznik, dzial_str, tresc, SUM( plan ) AS plan
          FROM pl_budzety_wydatki
          WHERE rocznik
          IN ( $p1,$p2 )
         AND type =  'dzial'
          AND LENGTH(czesc_str) < 3
          GROUP BY dzial_str, rocznik");

        $doch_dzial2 = array();
        foreach ($doch_dzial as $row) {
            $dzial_str = str_pad($row['dzial_str'], 3,'0', STR_PAD_LEFT);
            if (!isset($doch_dzial2[trim($dzial_str)])) {
                $doch_dzial2[trim($dzial_str)] = array();
                $doch_dzial2[trim($dzial_str)]['tresc'] = $row['tresc'];
            }
            if ($row['rocznik'] == $p1) {
                $doch_dzial2[trim($dzial_str)]['p1'] = $row['plan'];
            } else {
                $doch_dzial2[trim($dzial_str)]['p2'] = $row['plan'];
            }
        }
        $doch_dzial = array(
            'wzrost' => array(),
            'spadek' => array(),
            'bd' => array()
        );
        foreach ($doch_dzial2 as $row) {
            $zmiana = false;
            $zmiana2 = false;
            if (isset($row['p1']) && isset($row['p2'])) {
                $zmiana = $row['p2'] * 100 / $row['p1'] - 100;
                $zmiana2 = $row['p2'] / $row['p1'];
            }
            if ($zmiana > 0) {
                $doch_dzial['wzrost'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => $row['p1'],
                    'p2' => $row['p2'],
                    'zmiana' => $zmiana,
                    'zmiana2' => $zmiana2
                );
            } elseif ($zmiana === false) {
                $doch_dzial['bd'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => @$row['p1'],
                    'p2' => @$row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            } else {
                $doch_dzial['spadek'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => $row['p1'],
                    'p2' => $row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            }
        }


        $doch_czesci = $DB->selectAssocs("SELECT rocznik, czesc_str, tresc, SUM( plan ) AS plan
          FROM pl_budzety_wydatki
          WHERE rocznik
          IN ( $p1,$p2 )
         AND type =  'czesc'
          AND LENGTH(czesc_str) < 3
          GROUP BY czesc_str, rocznik");

        $doch_czesci2 = array();
        foreach ($doch_czesci as $row) {
            $czesc_str = str_pad($row['czesc_str'], 3,'0', STR_PAD_LEFT);
            if (!isset($doch_czesci2[trim($czesc_str)])) {
                $doch_czesci2[trim($czesc_str)] = array();
                $doch_czesci2[trim($czesc_str)]['tresc'] = $row['tresc'];
            }
            if ($row['rocznik'] == $p1) {
                $doch_czesci2[trim($czesc_str)]['p1'] = $row['plan'];
            } else {
                $doch_czesci2[trim($czesc_str)]['p2'] = $row['plan'];
            }
        }
        $doch_czesci = array(
            'wzrost' => array(),
            'spadek' => array(),
            'bd' => array()
        );
        foreach ($doch_czesci2 as $row) {
            $zmiana = false;
            $zmiana2 = false;
            if (isset($row['p1']) && isset($row['p2'])) {
                $zmiana = $row['p2'] * 100 / $row['p1'] - 100;
                $zmiana2 = $row['p2'] / $row['p1'];
            }
            if ($zmiana > 0) {
                $doch_czesci['wzrost'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => $row['p1'],
                    'p2' => $row['p2'],
                    'zmiana' => $zmiana,
                    'zmiana2' => $zmiana2
                );
            } elseif ($zmiana === false) {
                $doch_czesci['bd'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => @$row['p1'],
                    'p2' => @$row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            } else {
                $doch_czesci['spadek'][] = array(
                    'tresc' => $row['tresc'],
                    'p1' => $row['p1'],
                    'p2' => $row['p2'],
                    'zmiana' => $zmiana, 'zmiana2' => $zmiana2
                );
            }
        }


        usort($wyd_rozdzial['spadek'], function ($a, $b) {
            return $a['zmiana'] - $b['zmiana'];
        });
        usort($wyd_rozdzial['wzrost'], function ($a, $b) {
            return $b['zmiana'] - $a['zmiana'];
        });
        usort($wyd_dzial['spadek'], function ($a, $b) {
            return $a['zmiana'] - $b['zmiana'];
        });
        usort($wyd_dzial['wzrost'], function ($a, $b) {
            return $b['zmiana'] - $a['zmiana'];
        });
        usort($wyd_czesci['spadek'], function ($a, $b) {
            return $a['zmiana'] - $b['zmiana'];
        });
        usort($wyd_czesci['wzrost'], function ($a, $b) {
            return $b['zmiana'] - $a['zmiana'];
        });
        usort($doch_dzial['spadek'], function ($a, $b) {
            return $a['zmiana'] - $b['zmiana'];
        });
        usort($doch_dzial['wzrost'], function ($a, $b) {
            return $b['zmiana'] - $a['zmiana'];
        });
        usort($doch_czesci['spadek'], function ($a, $b) {
            return $a['zmiana'] - $b['zmiana'];
        });
        usort($doch_czesci['wzrost'], function ($a, $b) {
            return $b['zmiana'] - $a['zmiana'];
        });

        return array(
            'wydatki' => array(
                'czesci' => $wyd_czesci,
                'dzialy' => $wyd_dzial,
                'rozdzialy' => $wyd_rozdzial

            ),
            'dochody' => array(
                'czesci' => $doch_czesci,
                'dzialy' => $doch_dzial
            )
        );

    }
    
    function getSpendings( $year ) {
	    
	    App::import('model', 'DB');
        $DB = new DB();
	    
	    $q = "SELECT dzial_id, tresc, SUM(plan) as 'kwota' FROM `pl_budzety_wydatki` WHERE `type` = 'dzial' AND `rocznik` = '" . addslashes( $year ) . "' GROUP BY dzial_id ORDER BY SUM(plan) DESC";
	    
	    $suma = 0;
	    
	    $items = $DB->selectAssocs( $q );
	    foreach( $items as $i )
	    	$suma += $i['kwota'];
	    	
	    unset( $i );
	    foreach( $items as &$i )
	    	$i['procent'] = $i['kwota'] / $suma;
	    
	    
	    return $items;
	    
    }
    
    function getTable($table) {
	    
	    App::import('model', 'DB');
        $DB = new DB();
        
        if( $table_data = $DB->selectAssoc("SELECT `table`, `document_id`, `name`, `unit` FROM `doctables` WHERE `table`='" . addslashes( $table ) . "'") ) {
        	        
	        $columns = array();
	        $data = $DB->query("SHOW FULL COLUMNS FROM `$table`");
	        
	        foreach( $data as $d ) {
		        
		        $column_data = @json_decode($d['COLUMNS']['Comment'], true);
		        
		        $columns[] = array(
			        'field' => $d['COLUMNS']['Field'],
			        'title' => @$column_data['title'],
			        'type' => @$column_data['type'],
		        );
		        
	        }
	        
	        $result = array(
		        'table' => $table_data,
		        'columns' => $columns,
		        'data' => $DB->selectAssocs("SELECT * FROM `$table`"),
	        );
	        	    
		    return $result;
	    
	    }
	    
    }
    
    function getTables($id) {
	    
        $result = array();
	    
	    if( $id == 'wskazniki_makroekonomiczne' ) {
		    
		    $result = array(
			    $this->getTable('docd_wskazniki_2014'),
			    $this->getTable('docd_wskazniki_2013'),
			    $this->getTable('docd_wskazniki_2012'),
			    $this->getTable('docd_wskazniki_2011'),
			    $this->getTable('docd_wskazniki_2010'),
			    $this->getTable('docd_wskazniki_2009'),
			    $this->getTable('docd_wskazniki_2008'),
			    $this->getTable('docd_wskazniki_2007'),
			    $this->getTable('docd_wskazniki_2006'),
			    $this->getTable('docd_wskazniki_2005'),
		    );
		    
	    } elseif( $id == 'dochody_i_wydatki_sektora_finansow_publicznych' ) {
		    
		    $result = array(
			    $this->getTable('docd_sfp_zestawienie_2014'),
			    $this->getTable('docd_sfp_zestawienie_2013'),
			    $this->getTable('docd_sfp_zestawienie_2012'),
			    $this->getTable('docd_sfp_zestawienie_2011'),
			    $this->getTable('docd_sfp_zestawienie_2010'),
			    $this->getTable('docd_sfp_zestawienie_2009'),
			    $this->getTable('docd_sfp_zestawienie_2008'),
			    $this->getTable('docd_sfp_zestawienie_2007'),
			    $this->getTable('docd_sfp_zestawienie_2006'),
		    );
		    
	    } elseif( $id == 'dlug_publiczny' ) {
		    
		    $result = array(
			    $this->getTable('docd_dlug_publiczny_2014'),
			    $this->getTable('docd_dlug_publiczny_2013'),
			    $this->getTable('docd_dlug_publiczny_2012'),
			    $this->getTable('docd_dlug_publiczny_2011'),
			    $this->getTable('docd_dlug_publiczny_2010'),			    
			    $this->getTable('docd_dlug_publiczny_2009'),			    
			    $this->getTable('docd_dlug_publiczny_2008'),			    
			    $this->getTable('docd_dlug_publiczny_2007'),			    
			    $this->getTable('docd_dlug_publiczny_2006'),			    
		    );
		    
	    } elseif( $id == 'fundusze_celowe' ) {
		    
		    $result = array(
			    $this->getTable('docd_fundusze_celowe_2014'),			    
			    $this->getTable('docd_fundusze_celowe_2013'),			    
			    $this->getTable('docd_fundusze_celowe_2012'),			    
			    $this->getTable('docd_fundusze_celowe_2011'),			    
			    $this->getTable('docd_fundusze_celowe_2010'),			    
			    $this->getTable('docd_fundusze_celowe_2009'),			    
			    $this->getTable('docd_fundusze_celowe_2008'),			    
		    );
		    
	    } elseif( $id == 'dochody_ue' ) {
		    
		    $result = array(
			    $this->getTable('docd_dochody_budzetu_srodkow_europejskich_2014'),			    
			    $this->getTable('docd_dochody_budzetu_srodkow_europejskich_2013'),			    
			    $this->getTable('docd_dochody_budzetu_srodkow_europejskich_2012'),			    
			    $this->getTable('docd_dochody_budzetu_srodkow_europejskich_2011'),			    
		    );
		    
		} elseif( $id == 'wydatki_ue' ) {
		    
		    $result = array(
			    $this->getTable('docd_wydatki_eu_2014'),			    
			    // $this->getTable('docd_wydatki_eu_2013'),			    
			    // $this->getTable('docd_wydatki_eu_2012'),			    
			    // $this->getTable('docd_wydatki_eu_2011'),			    
		    );
	    
	    } elseif( $id == 'dotacje_i_subwencje_budzetu_panstwa' ) {
		    
		    $result = array(
			    $this->getTable('docd_dotacje_subwencje_2014'),			    
			    $this->getTable('docd_dotacje_subwencje_2013'),			    
			    $this->getTable('docd_dotacje_subwencje_2012'),			    
			    $this->getTable('docd_dotacje_subwencje_2011'),			    
			    $this->getTable('docd_dotacje_subwencje_2010'),			    
			    $this->getTable('docd_dotacje_subwencje_2009'),			    
			    $this->getTable('docd_dotacje_subwencje_2007'),			    
			    $this->getTable('docd_dotacje_subwencje_2006'),			    
		    );
		    
	    } elseif( $id == 'wykonanie_fus' ) {
		    
		    $result = array(
			    $this->getTable('docd_fus_2014'),			    			    
			    $this->getTable('docd_fus_2013'),			    			    
			    $this->getTable('docd_fus_2012'),			    			    
			    $this->getTable('docd_fus_2011'),			    			    
			    $this->getTable('docd_fus_2010'),			    			    
			    $this->getTable('docd_fus_2009'),			    			    
			    $this->getTable('docd_fus_2008'),			    			    
			    $this->getTable('docd_fus_2007'),			    			    
			    $this->getTable('docd_fus_2006'),			    			    
		    );
		    
	    } elseif( $id == 'wykonanie_budzetu_panstwa' ) {
		    
		    $result = array(
			    $this->getTable('docd_bp_wykonanie_2014'),			    			    
			    $this->getTable('docd_bp_wykonanie_2013'),			    			    
			    $this->getTable('docd_bp_wykonanie_2012'),			    			    
			    $this->getTable('docd_bp_wykonanie_2011'),			    			    
			    $this->getTable('docd_bp_wykonanie_2010'),			    			    
			    $this->getTable('docd_bp_wykonanie_2009'),			    			    
			    $this->getTable('docd_bp_wykonanie_2008'),			    			    
			    $this->getTable('docd_bp_wykonanie_2007'),			    			    
			    $this->getTable('docd_bp_wykonanie_2006'),			    			    
		    );
		    
	    } elseif( $id == 'dochody_budzetu_panstwa' ) {
		    
		    $result = array(
			    $this->getTable('docd_dochody_2014'),			    			    
			    $this->getTable('docd_dochody_2013'),			    			    
			    $this->getTable('docd_dochody_2012'),			    			    
			    $this->getTable('docd_dochody_2011'),			    			    
			    $this->getTable('docd_dochody_2010'),			    			    
			    $this->getTable('docd_dochody_2009'),			    			    
			    $this->getTable('docd_dochody_2008'),			    			    
			    $this->getTable('docd_dochody_2007'),			    			    
			    $this->getTable('docd_dochody_2006'),			    			    
		    );
		    
	    } elseif( $id == 'wydatki_budzetu_panstwa_wedlug_dzialow' ) {
		    
		    $result = array(
			    $this->getTable('docd_wydatki_2014'),			    			    
			    $this->getTable('docd_wydatki_2013'),			    			    
			    $this->getTable('docd_wydatki_2012'),			    			    
			    $this->getTable('docd_wydatki_2011'),			    			    
			    $this->getTable('docd_wydatki_2010'),			    			    
			    $this->getTable('docd_wydatki_2009'),			    			    
			    $this->getTable('docd_wydatki_2008'),			    			    
		    );
		    
	    } elseif( $id == 'wykonanie_fer_rolnikow' ) {
		    
		    $result = array(
			    $this->getTable('docd_fer_2014'),			    			    
			    $this->getTable('docd_fer_2013'),			    			    
			    $this->getTable('docd_fer_2012'),			    			    
			    $this->getTable('docd_fer_2011'),			    			    
			    $this->getTable('docd_fer_2010'),			    			    
			    $this->getTable('docd_fer_2009'),			    			    
			    $this->getTable('docd_fer_2008'),			    			    
			    $this->getTable('docd_fer_2007'),			    			    
			    $this->getTable('docd_fer_2006'),			    			    
		    );
		    
	    } elseif( $id == 'wykonanie_funduszu_pracy' ) {
		    
		    $result = array(
			    $this->getTable('docd_fp_2014'),			    			    
			    $this->getTable('docd_fp_2013'),			    			    
			    $this->getTable('docd_fp_2012'),			    			    
			    $this->getTable('docd_fp_2011'),			    			    
			    $this->getTable('docd_fp_2010'),			    			    
			    $this->getTable('docd_fp_2009'),			    			    
			    $this->getTable('docd_fp_2006'),			    			    
		    );
		    
	    } elseif( $id == 'wydatki_budzetu_panstwa_wedlug_czesci' ) {
		    
		    $result = array(
			    $this->getTable('docd_wydatki_czesci_2014'),			    			    
			    $this->getTable('docd_wydatki_czesci_2013'),
		    );
		    
	    }
	    
	    
	    
	    
	    
	    
	    
	    
	    
	    
	    
	    
	    $result = array_filter($result);
	    return $result;
	    
    }
    
}
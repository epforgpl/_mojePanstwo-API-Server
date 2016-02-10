<?php

class Stats extends AppModel
{

    public $useTable = false;

    public function getPanstwa($params)
    {
        App::import('model','DB');
        $DB = new DB();
        $data = array();

        $rocznik = isset($params['rocznik']) ? (int) $params['rocznik'] : 2005;
        $symbol_id = isset($params['symbol_id']) ? (int) $params['symbol_id'] : 1;
        $order = (isset($params['order']) && $params['order'] == 'eksport') ? 'eksport' : 'import';

        $data = $DB->selectAssocs("
          SELECT hz_panstwa.id, hz_data.rocznik, hz_data.typ, hz_panstwa.nazwa, hz_panstwa.symbol, hz_data.wartosc_pln
          FROM hz_data
          JOIN hz_panstwa ON hz_panstwa.id = hz_data.panstwo_id
          WHERE
            hz_data.symbol_id = $symbol_id AND
            hz_data.rocznik = $rocznik AND
            hz_data.typ = '$order' AND
            hz_data.wartosc_pln > 0
          GROUP BY hz_data.panstwo_id
          ORDER BY hz_data.wartosc_pln DESC
        ");

        return $data;
    }

    public function getTowary($params)
    {
		App::import('model','DB');
		$DB = new DB();
		$data = array();

        $rocznik = isset($params['rocznik']) ? (int) $params['rocznik'] : 2005;
        $panstwo_id = isset($params['panstwo_id']) ? (int) $params['panstwo_id'] : 1;
        $order = (isset($params['order']) && $params['order'] == 'eksport') ? 'eksport' : 'import';

        $data = $DB->selectAssocs("
          SELECT hz_data.rocznik, hz_data.typ, hz_cn_symbole.nazwa, SUM(hz_data.wartosc_pln) as wartosc_pln
          FROM hz_data
          JOIN hz_cn_symbole ON hz_cn_symbole.id = hz_data.symbol_id
          WHERE
            hz_data.panstwo_id = $panstwo_id AND
            hz_data.rocznik = $rocznik AND
            hz_data.typ = '$order' AND
            hz_cn_symbole.parent_id = 0
          GROUP BY hz_data.symbol_id
          ORDER BY wartosc_pln DESC
          LIMIT 5
        ");

        return $data;
    }
    
    public function getNewStats($range = 'month')
    {
    	
	    $_allowed_ranges = array('week', 'month', 'year', '3years', '5years');
	    if( !in_array($range, $_allowed_ranges) )
	    	return false;
	    
	    App::import('model', 'DB');
        $this->DB = new DB();
	    
	    $data = $this->DB->selectValue("SELECT `data` FROM `uzp_stats` WHERE `id`='" . addslashes( $range ) . "'");
	    if( !empty($data) && ( $data = unserialize(preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $data)) )) {
		    
		    return $data;
		    
	    } else return false;
	    		    
    }

    public function getCountriesData($type, $year)
    {
        App::import('model','DB');
        $db = new DB();
        $year = (int) $year;
        $limit = 3;

        $countries = $db->selectAssocs("
          SELECT
            hz_panstwa.id as `id`,
            hz_panstwa.symbol as `code`,
            hz_panstwa.nazwa as `kraj`,
            hz_panstwa_roczniki.import_pln as `import`,
            hz_panstwa_roczniki.eksport_pln as `eksport`
          FROM hz_panstwa_roczniki
          JOIN hz_panstwa ON hz_panstwa.id = hz_panstwa_roczniki.panstwo_id
          WHERE
            hz_panstwa_roczniki.rocznik = $year
        ");

        /* foreach($countries as $i => $country)
        {
            $countries[$i]['symbole'] = array();
            $countries[$i]['symbole']['import'] = $db->selectAssocs("
              SELECT
                hz_cn_symbole.nazwa,
                hz_data.wartosc_pln
              FROM hz_data
              JOIN hz_cn_symbole ON hz_cn_symbole.id = hz_data.symbol_id
              WHERE
                hz_data.rocznik = $year AND
                hz_data.panstwo_id = {$countries[$i]['id']} AND
                hz_cn_symbole.parent_id = 0 AND
                hz_data.typ = 'import'
              ORDER BY hz_data.wartosc_pln DESC
              LIMIT $limit
            ");

            $countries[$i]['symbole']['eksport'] = $db->selectAssocs("
              SELECT
                hz_cn_symbole.nazwa,
                hz_data.wartosc_pln
              FROM hz_data
              JOIN hz_cn_symbole ON hz_cn_symbole.id = hz_data.symbol_id
              WHERE
                hz_data.rocznik = $year AND
                hz_data.panstwo_id = {$countries[$i]['id']} AND
                hz_cn_symbole.parent_id = 0 AND
                hz_data.typ = 'eksport'
              ORDER BY hz_data.wartosc_pln DESC
              LIMIT $limit
            ");
        } */

        return $countries;
    }

    public function getSymbols($params)
    {
        $year = isset($params['year']) ? (int) $params['year'] : 2015;
        $parent_id = isset($params['parent_id']) ? (int) $params['parent_id'] : 0;
        $type = isset($params['type']) && $params['type'] == 'import' ? 'import' : 'eksport';
        $limit = isset($params['limit']) ? (int) $params['limit'] : 5;
        $country_id = isset($params['country_id']) ? (int) $params['country_id'] : 0;

        $country_where = '';
        if($country_id > 0)
            $country_where = 'AND hz_data.panstwo_id = '.$country_id;


        App::import('model', 'DB');
        $db = new DB();

        return $db->selectAssocs("
          SELECT
            hz_cn_symbole.nazwa,
            hz_cn_symbole.id,
            SUM(hz_data.wartosc_pln) AS wartosc_pln
          FROM hz_data
          JOIN hz_cn_symbole ON hz_cn_symbole.id = hz_data.symbol_id
          WHERE
            hz_data.rocznik = $year AND
            hz_cn_symbole.parent_id = $parent_id AND
            hz_data.typ = '$type'
            $country_where
          GROUP BY hz_data.symbol_id
          ORDER BY wartosc_pln DESC
          LIMIT $limit
        ");
    }

    public function getTopSymbolsData($year)
    {
        App::import('model', 'DB');
        $db = new DB();
        $year = (int) $year;
        $limit = 5;
        $symbols = array(
            'import' => array(),
            'export' => array()
        );

        $symbols['import'] = $db->selectAssocs("
          SELECT
            hz_cn_symbole.nazwa,
            hz_cn_symbole.id,
            SUM(hz_data.wartosc_pln) AS wartosc_pln
          FROM hz_data
          JOIN hz_cn_symbole ON hz_cn_symbole.id = hz_data.symbol_id
          WHERE
            hz_data.rocznik = $year AND
            hz_cn_symbole.parent_id = 0 AND
            hz_data.typ = 'import'
          GROUP BY hz_data.symbol_id
          ORDER BY wartosc_pln DESC
          LIMIT $limit
        ");

        $symbols['export'] = $db->selectAssocs("
          SELECT
            hz_cn_symbole.nazwa,
            hz_cn_symbole.id,
            SUM(hz_data.wartosc_pln) AS wartosc_pln
          FROM hz_data
          JOIN hz_cn_symbole ON hz_cn_symbole.id = hz_data.symbol_id
          WHERE
            hz_data.rocznik = $year AND
            hz_cn_symbole.parent_id = 0 AND
            hz_data.typ = 'eksport'
          GROUP BY hz_data.symbol_id
          ORDER BY wartosc_pln DESC
          LIMIT $limit
        ");

        return $symbols;
    }

} 
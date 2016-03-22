<?php

function array_msort($array, $cols)
{
    $colarr = array();
    foreach ($cols as $col => $order) {
        $colarr[$col] = array();
        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
    }
    $eval = 'array_multisort(';
    foreach ($cols as $col => $order) {
        $eval .= '$colarr[\''.$col.'\'],'.$order.',';
    }
    $eval = substr($eval,0,-1).');';
    eval($eval);
    $ret = array();
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            $k = substr($k,1);
            if (!isset($ret[$k])) $ret[$k] = $array[$k];
            $ret[$k][$col] = $array[$k][$col];
        }
    }
    return $ret;

}

class Srodowisko extends AppModel
{

    public $useTable = 'srodowisko_pomiary';

    public function getData($param, $rank = 'latest')
    {
		
		$Dataobject = ClassRegistry::init('Dane.Dataobject');
		
		$data = $Dataobject->find('all', array(
            'conditions' => array(
	            'dataset' => 'srodowisko_stacje_pomiarowe',
            ),
            'limit' => 0,
            'aggs' => array(
	            'pomiary' => array(
		            'nested' => array(
			            'path' => 'stacje_pomiarowe-pomiary'
		            ),
		            'aggs' => array(
			            'filter' => array(
				            'filter' => array(
					            'bool' => array(
						            'must' => array(
							            array(
								            'term' => array(
									            'stacje_pomiarowe-pomiary.param' => $param,
								            ),
							            ),
							            array(
								            'range' => array(
									            'stacje_pomiarowe-pomiary.time' => array(
										            'gte' => "now-3h/h"
									            ),
								            ),
							            ),
						            ),
					            ),
				            ),
				            'aggs' => array(
					            'stacje' => array(
						            'terms' => array(
							            'field' => 'stacje_pomiarowe-pomiary.station_id',
							            'size' => 500,
						            ),
						            'aggs' => array(
							            'time' => array(
								            'terms' => array(
									            'field' => 'stacje_pomiarowe-pomiary.time',
									            'size' => 1,
									            'order' => array(
									            	'_term' => 'desc'
									            ),
								            ),
								            'aggs' => array(
									            'value' => array(
										            'avg' => array(
											            'field' => 'stacje_pomiarowe-pomiary.value',
											            // 'size' => 1,
										            ),
									            ),
								            ),
							            ),
						            ),
					            ),							            
				            ),
			            ),
		            ),
	            ),
            ),
        ));
        
        $data = $Dataobject->getDataSource()->Aggs['pomiary']['filter']['stacje']['buckets'];
        $stacje = array();
                
        foreach( $data as $b ) {
	        
	        // if( $source = @$b['top']['hits']['hits'][0]['_source'] ) {
		        
		        // $v = (float) $source['value'];
		        
		        $stacje[] = array(
			        'id' => $b['key'],
			        'time' => $b['time']['buckets'][0]['key_as_string'],
			        'value' => $b['time']['buckets'][0]['value']['value'],
		        );
	        // }
	        
        }
        

		$stacje = array_values(array_msort($stacje, array('value'=>SORT_ASC)));        
        
        return array(
	        'stations' => $stacje,
        );
		
    }

	public function getRankingData($param, $option, $order = 'best') {
		
		$options = array(
			'3d' => 'now-3d/d',
			'1w' => 'now-1w/d',
			'1m' => 'now-1M/d',
		);
		
		if( $order=='best' )
			$order = 'asc';
		else
			$order = 'desc';
		
		
		
		
		if(!array_key_exists($option, $options))
			return false;
		
		$Dataobject = ClassRegistry::init('Dane.Dataobject');
		
		$data = $Dataobject->find('all', array(
            'conditions' => array(
	            'dataset' => 'srodowisko_stacje_pomiarowe',
            ),
            'limit' => 0,
            'aggs' => array(
	            'pomiary' => array(
		            'nested' => array(
			            'path' => 'stacje_pomiarowe-pomiary'
		            ),
		            'aggs' => array(
			            'filter' => array(
				            'filter' => array(
					            'bool' => array(
						            'must' => array(
							            array(
								            'term' => array(
									            'stacje_pomiarowe-pomiary.param' => $param,
								            ),
							            ),
							            array(
								            'range' => array(
									            'stacje_pomiarowe-pomiary.time' => array(
										            'gt' => $options[ $option ]
									            ),
								            ),
							            ),
						            ),
					            ),
				            ),
				            'aggs' => array(
					            'stacje' => array(
						            'terms' => array(
							            'field' => 'stacje_pomiarowe-pomiary.station_id',
							            'size' => 500,
							            'order' => array(
								            'value.value' => $order,
							            ),
						            ),
						            'aggs' => array(
							            'value' => array(
								            'avg' => array(
									            'field' => 'stacje_pomiarowe-pomiary.value',
								            ),
							            ),
						            ),
					            ),							            
				            ),
			            ),
		            ),
	            ),
            ),            
        ));
        
        $data = $Dataobject->getDataSource()->Aggs['pomiary']['filter']['stacje']['buckets'];
        $stacje = array();
                
        foreach( $data as $b ) {
	        
	        // if( $source = @$b['top']['hits']['hits'][0]['_source'] ) {
		        
		        // $v = (float) $source['value'];
		        
		        $stacje[] = array(
			        'id' => $b['key'],
			        'value' => $b['value']['value'],
		        );
	        // }
	        
        }
        
        
        
        return array(
	        'stations' => $stacje,
        );
		
		
		
		
		
		
		
		
		
		
		
		/*
		$response = array(
			'most' => array(),
			'least' => array()
		);

		$options = array(
			'3d' => time() - 259200,
			'1w' => time() - 604800,
			'1m' => time() - 2592000
		);

		if(!array_key_exists($option, $options))
			return $response;

		$date = date('Y-m-d', $options[$option]);

		foreach($response as $key => $values) {
			$response[$key] = $this->query("
			  SELECT
				AVG(`value`) as `val`,
				`station_id`
			  FROM `srodowisko_pomiary`
			  WHERE
				`param` = ? AND
				`timestamp` BETWEEN ? AND NOW()
			  GROUP BY `station_id`
			  ORDER BY `val` " . ($key == 'most' ? 'ASC' : 'DESC') . "
			  " . ($limit === false ? 'LIMIT 10' : '') . "
			", array(
				$param,
				$date
			));
		}

		return $response;
		*/
		
	}

    public function getChartData($station_id, $param, $timestamp) {

		switch($timestamp) {

			case 'd':

				return $this->query("
				  SELECT
					AVG(`value`) as `avg`,
					`timestamp`
					FROM `srodowisko_pomiary`
					WHERE
					  `station_id` = ? AND
					  `param` = ?
					GROUP BY YEAR(`timestamp`), MONTH(`timestamp`), DAY(`timestamp`)
					ORDER BY `timestamp` DESC
					LIMIT 30
				", array(
					(int) $station_id,
					$param
				));

			break;

			case 'h':

				return $this->query("
				  SELECT
					`value` as `avg`,
					`timestamp`
					FROM `srodowisko_pomiary`
					WHERE
					  `station_id` = ? AND
					  `param` = ?
					ORDER BY `timestamp` DESC
					LIMIT 30
				", array(
					(int) $station_id,
					$param
				));

			break;

			default:

				list($from, $to) = explode('_', $timestamp);
				$from = strtotime($from);
				$to = strtotime($to);

				if($from === false || $to === false)
					return array();

				$from = date('Y-m-d', $from);
				$to = date('Y-m-d', $to);

				return $this->query("
				  SELECT
					AVG(`value`) as `avg`,
					`timestamp`
					FROM `srodowisko_pomiary`
					WHERE
					  `station_id` = ? AND
					  `param` = ? AND
					  `timestamp` BETWEEN ? AND ?
					GROUP BY YEAR(`timestamp`), MONTH(`timestamp`), DAY(`timestamp`)
					ORDER BY `timestamp` DESC
					LIMIT 30
				", array(
					(int) $station_id,
					$param,
					$from,
					$to
				));

			break;

		}
    }

}

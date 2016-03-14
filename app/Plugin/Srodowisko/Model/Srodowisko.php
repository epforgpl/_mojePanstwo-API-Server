<?php


class Srodowisko extends AppModel
{

    public $useTable = 'srodowisko_pomiary';

    public function getData($param)
    {
		
		$Dataobject = ClassRegistry::init('Dane.Dataobject');
		
		$data = $Dataobject->find('all', array(
            'conditions' => array(
	            'dataset' => 'srodowisko_stacje_pomiarowe',
            ),
            'limit' => 0,
            'aggs' => array(
	            'stacje' => array(
		            'terms' => array(
			            'field' => 'srodowisko_stacje_pomiarowe.id',
			            'size' => 500,
		            ),
		            'aggs' => array(
			            'pomiary' => array(
				            'nested' => array(
					            'path' => 'stacje_pomiarowe-pomiary'
				            ),
				            'aggs' => array(
					            'filter' => array(
						            'filter' => array(
							            'term' => array(
								            'stacje_pomiarowe-pomiary.param' => $param,
							            ),
						            ),
						            'aggs' => array(
							            'top' => array(
								            'top_hits' => array(
									            'size' => 1,
									            'sort' => array(
										            'stacje_pomiarowe-pomiary.time' => array(
											            'order' => 'desc',
										            ),
									            ),
									            '_source' => array(
										            'include' => array(
											            'time', 'value'
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
        
        $data = $Dataobject->getDataSource()->Aggs['stacje'];
        $stacje = array();
        
        
        foreach( $data['buckets'] as $b ) {
	        
	        if( $source = @$b['pomiary']['filter']['top']['hits']['hits'][0]['_source'] ) {
		        
		        $v = (float) $source['value'];
		        
		        $stacje[] = array(
			        'id' => $b['key'],
			        'time' => $source['time'],
			        'value' => $v,
		        );
	        }
	        
        }
        
        
        
        return array(
	        'stations' => $stacje,
        );
		
    }

	public function getRankingData($param, $option, $limit = false) {
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

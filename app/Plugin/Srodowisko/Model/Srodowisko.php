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

    public function getChartData($station_id, $param) {
        return $this->query("
          SELECT
            AVG(`value`) as `avg`,
            `timestamp`
            FROM `srodowisko_pomiary`
            WHERE
              `station_id` = ? AND
              `param` = ?
            GROUP BY day(`timestamp`)
            ORDER BY `timestamp` DESC
        ", array(
            (int) $station_id,
            $param
        ));
    }

}

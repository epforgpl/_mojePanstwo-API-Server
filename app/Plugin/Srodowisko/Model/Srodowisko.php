<?php

class Srodowisko extends AppModel {

    public $useTable = 'srodowisko_pomiary';

    public function getData($station_id, $param) {
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
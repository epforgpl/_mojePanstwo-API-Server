<?php

class CrawlerPage extends AppModel {

    public $useTable = 'crawler_pages';

    public function afterSave($options = array()) {
        $res = $this->query("SELECT id FROM objects WHERE `dataset_id` = '224' AND `object_id`='" . addslashes( $this->data['CrawlerPage']['id'] ) . "' LIMIT 1");
        $object_id = (int) (@$res[0]['objects']['id']);

        $this->objectIndex(array(
            'dataset' => 'webpages',
            'object_id' => $object_id
        ));
    }

}

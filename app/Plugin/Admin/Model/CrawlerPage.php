<?php

class CrawlerPage extends AppModel {

    public $useTable = 'crawler_pages';

    public function afterSave($options = array()) {
        $this->objectIndex(array(
            'dataset' => 'webpages',
            'object_id' => $this->data['CrawlerPage']['id']
        ));
    }

}

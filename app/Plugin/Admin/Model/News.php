<?php

class News extends AppModel {

    public $useTable = 'news';

    private static $ES_DATE_FORMAT = "Ymd\THis\Z";
    private static $ES_DATASET_ID = 225;
    private static $ES_DATASET = 'news';

    public function afterSave($options = array()) {
        $ES = ConnectionManager::getDataSource('MPSearch');
        $res = $this->query("SELECT id FROM objects WHERE `dataset_id` = '". self::$ES_DATASET_ID ."' AND `object_id`='" . addslashes( $this->data['News']['id'] ) . "' LIMIT 1");
        $global_id = (int) (@$res[0]['objects']['id']);

        if(!$global_id) {
            $this->query("INSERT INTO `objects` (`dataset`, `dataset_id`, `object_id`) VALUES ('". self::$ES_DATASET ."', ". self::$ES_DATASET_ID .", ".$this->data['News']['id'].")");
            $res = $this->query('select last_insert_id() as id;');
            $global_id = $res[0][0]['id'];
        }

        $data = $this->data['News'];
        $fields = array();
        foreach($data as $name => $value) {
            $fields['news.' . $name] = $value;
        }

        if(isset($fields['news.created_at']))
            $fields['news.created_at'] = date(self::$ES_DATE_FORMAT, strtotime($data['created_at']));
        if(isset($fields['news.updated_at']))
            $fields['news.updated_at'] = date(self::$ES_DATE_FORMAT, strtotime($data['updated_at']));

        $ES->API->index(array(
            'index' => 'mojepanstwo_v1',
            'id' => $global_id,
            'type' => 'objects',
            'refresh' => true,
            'body' => array(
                'id' => $data['id'],
                'title' => $data['name'],
                'text' => $data['name'] . ' ' . $data['description'] . ' ' . strip_tags($data['content']),
                'dataset' => self::$ES_DATASET,
                'slug' => Inflector::slug($data['name']),
                'data' => $fields,
                'date' => date('Y-m-d', isset($data['created_at']) ? strtotime($data['created_at']) : time())
            )
        ));

        if(isset($fields['news.crawler_page_id']) && $fields['news.crawler_page_id'] != '0') {
            App::import('Model', 'Admin.CrawlerPage');
            $page = new CrawlerPage();
            $page->save(array(
                'id' => (int) $fields['news.crawler_page_id'],
                'status' => '1'
            ));
        }
    }

    public function beforeDelete($cascade = true) {
        $id = (int) $this->id;
        $ES = ConnectionManager::getDataSource('MPSearch');
        $res = $this->query("SELECT id FROM objects WHERE `dataset_id` = '". self::$ES_DATASET_ID ."' AND `object_id`='" . addslashes( $id ) . "' LIMIT 1");
        $global_id = (int) (@$res[0]['objects']['id']);
        if(!$global_id)
            return false;

        $ES->API->delete(array(
            'index' => 'mojepanstwo_v1',
            'type' => 'objects',
            'id' => $global_id,
            'refresh' => true,
            'ignore' => 404
        ));
    }

}
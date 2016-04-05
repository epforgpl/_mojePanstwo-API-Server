<?php

class News extends AppModel {

    public $useTable = 'news';

    private static $ES_DATE_FORMAT = "Y-m-d H:i:s";
    private static $ES_DATASET_ID = 226;
    private static $ES_DATASET = 'ngo_konkursy';

    private static $ES_FIELDS_MAP = array(
        'id',
        'instytucja_id',
        array('db' => 'name', 'es' => 'tytul'),
        array('db' => 'description', 'es' => 'opis'),
        array('db' => 'date', 'es' => 'data'),
        array('db' => 'deadline', 'es' => 'data_deadline'),
        array('db' => 'range_min', 'es' => 'wartosc_min'),
        array('db' => 'range_max', 'es' => 'wartosc_max'),
        array('db' => 'is_promoted', 'es' => 'promo'),
        array('db' => 'is_image', 'es' => 'img'),
        array('db' => 'image_source', 'es' => 'img_src'),
        array('db' => 'source_url', 'es' => 'url'),
        array('db' => 'created_at', 'es' => 'czas_utowrzenia', 'type' => 'datetime'),
        array('db' => 'updated_at', 'es' => 'czas_modyfikacji', 'type' => 'datetime'),
    );

    public function afterSave($options = array()) {
        $ES = ConnectionManager::getDataSource('MPSearch');
        
        $item = $this->query("SELECT id, created_at FROM news WHERE id='" . addslashes( $this->data['News']['id'] ) . "'");
                
        $res = $this->query("SELECT id FROM objects WHERE `dataset_id` = '". self::$ES_DATASET_ID ."' AND `object_id`='" . addslashes( $this->data['News']['id'] ) . "' LIMIT 1");
        $global_id = (int) (@$res[0]['objects']['id']);

        if(!$global_id) {
            $this->query("INSERT INTO `objects` (`dataset`, `dataset_id`, `object_id`) VALUES ('". self::$ES_DATASET ."', ". self::$ES_DATASET_ID .", ".$this->data['News']['id'].")");
            $res = $this->query('select last_insert_id() as id;');
            $global_id = $res[0][0]['id'];
        }

        $data = $this->data['News'];
        $areas = array();
        $tags = array();
		
        $this->query("DELETE FROM news_areas WHERE news_id = " . $this->data['News']['id']);
        if(isset($data['areas'])) {
            $areas = $data['areas'];
            foreach($areas as $area) {
                $this->query("INSERT INTO news_areas VALUES (" . (int) $this->data['News']['id'] . ", " . (int) $area. ")");
            }
			
			$data['area_id'] = $data['areas'];
            unset($data['areas']);
        }

        $this->query("DELETE FROM news_tags WHERE news_id = " . $this->data['News']['id']);
        if(isset($data['tags'])) {
            App::uses('Temat', 'Dane.Model');
            $this->Temat = new Temat();
            $tags = explode(',', $data['tags']);
            $tags_ids = array();
            foreach($tags as $tag) {
                $q = trim($tag);
                $topic = $this->Temat->find('first', array(
                    'conditions' => array(
                        'Temat.q' => $q
                    )
                ));

                if(!$topic) {
                    $this->Temat->clear();
                    $this->Temat->save(array(
                        'q' => $q,
                    ));

                    $tags_ids[] = (int) $this->Temat->getLastInsertId();
                } else {
                    $tags_ids[] = (int) $topic['Temat']['id'];
                }
            }

            unset($data['tags']);
            foreach($tags_ids as $tag_id) {
                $this->query("INSERT INTO news_tags VALUES (" . (int) $this->data['News']['id'] . ", " . (int) $tag_id. ")");
            }
        }
		
		if( $item[0]['news']['created_at']  )
			$data['created_at'] = $item[0]['news']['created_at'] ;
		
		$es_object = array(
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
                'data' => array(
                    self::$ES_DATASET => $this->prepareDataToESFields($data)
                ),
                'date' => date('Y-m-d', isset($data['created_at']) ? strtotime($data['created_at']) : time())
            )
        );
                
        $ES->API->index($es_object);

        if(isset($data['crawler_page_id']) && $data['crawler_page_id'] != '0') {
            App::import('Model', 'Admin.CrawlerPage');
            $page = new CrawlerPage();
            $page->save(array(
                'id' => (int) $data['crawler_page_id'],
                'status' => '1'
            ));
        }
    }

    private function prepareDataToESFields($data) {
        $fields = array();
        foreach(self::$ES_FIELDS_MAP as $field) {
            if(!is_array($field) && isset($data[$field])) {
                $fields[$field] = $data[$field];
            } else {
                if(isset($data[$field['db']])) {
                    if(isset($field['type']) && $field['type'] == 'datetime') {
                        $fields[$field['es']] = date(self::$ES_DATE_FORMAT, strtotime($data[$field['db']]));
                    } else {
                        $fields[$field['es']] = $data[$field['db']];
                    }
                }
            }
        }
        
        if( isset($data['area_id']) )
        	$fields['area_id'] = $data['area_id'];
        
        return $fields;
    }

    public function beforeDelete($cascade = true) {
        $id = (int) $this->id;
        $this->query("DELETE FROM news_tags WHERE news_id = " . $id);
        $this->query("DELETE FROM news_areas WHERE news_id = " . $id);
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
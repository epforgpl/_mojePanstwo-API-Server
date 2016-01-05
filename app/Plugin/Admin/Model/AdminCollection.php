<?php

App::uses('Collection','Collections.Model');

class AdminCollection extends AppModel {

    public $useTable = 'collections';

    public function afterSave($created, $options) {
        if($id = $this->data['AdminCollection']['id']) {
            $col = new Collection();
            $col->syncById($id);
        }
    }


}
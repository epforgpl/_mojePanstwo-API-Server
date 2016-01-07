<?php

App::uses('Document','Pisma.Model');

class AdminLetter extends AppModel {

    public $useTable = 'pisma_documents';

    public function afterSave($created, $options) {
        if($id = $this->data['AdminLetter']['id']) {
            $db = ConnectionManager::getDataSource('default');
            $res = $db->query("SELECT alphaid FROM pisma_documents WHERE id = " . $id);
            $doc = new Document();
            $doc->sync($res[0]['pisma_documents']['alphaid']);
        }
    }

}
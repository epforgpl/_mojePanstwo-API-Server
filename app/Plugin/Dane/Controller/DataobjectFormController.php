<?php

App::uses('AppController', 'Controller');

class DataobjectFormController extends AppController {

    private $actions = array(

        'add_activity' => array(
            'rules' => array('superuser', 'owner', 'admin')
        ),

        'edit_activity' => array(
            'rules' => array('superuser', 'owner', 'admin')
        ),

        'pobierz_nowy_odpis' => array(
            'rules' => array('superuser', 'owner', 'admin')
        ),

    );

    public function post($dataset, $id) {
        try {

        } catch (Exception $e) {
            $this->set('exception', $e->getMessage());
            $this->set('_serialize', 'exception');
        }
    }
    
}
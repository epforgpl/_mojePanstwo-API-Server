<?php

App::uses('AppModel', 'Model');

class Transaction extends AppModel {

    public $validate = array(
        'krs_pozycje_id' => array(
            'required' => true,
            'rule' => 'numeric'
        ),
        'amount' => array(
            'required' => true,
            'rule' => array('decimal', 2)
        ),
        'email' => array(
            'required' => true,
            'rule' => 'email'
        )
    );

    public $useTable = 'transactions';

    public function beforeSave(array $options = array()) {
        parent::beforeSave($options);
        if(empty($this->data['Transaction']['id']))
            $this->data['Transaction']['form_send_at'] = date('Y-m-d H:i:s');
    }

}
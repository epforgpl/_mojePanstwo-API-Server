<?php

class PkdsectionsController extends AppController
{
    public function getSection()
    {
	   	
	   	$DB = $this->loadModel('DB');
	    $data = $this->DB->selectAssoc("SELECT id, nazwa, symbol FROM `pkd2007_sekcje` WHERE `symbol`='" . addslashes( $this->request->params['id'] ) . "'");
	   	
	   	$this->set('data', $data);
	    $this->set('_serialize', 'data');
	   	
	}
} 
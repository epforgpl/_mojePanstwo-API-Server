<?php

class PortalController extends AppController
{

    public $components = array('RequestHandler');
	
	public function verifyKey() {
				
		if(
			isset( $this->request->query['object_id'] ) && 
			isset( $this->request->query['key'] ) && 
			$this->Portal->verifyKey($this->request->query['object_id'], $this->request->query['key'])
		) {
			$res = true;
		} else {
			$res = false;
		}
		
		$this->set('res', $res);
		$this->set('_serialize', 'res');
		
	}

}
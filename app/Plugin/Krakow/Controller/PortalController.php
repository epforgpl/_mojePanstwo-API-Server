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
	
	public function savePromises() {
					
		if(
			isset( $this->request->data['object_id'] ) && 
			isset( $this->request->data['key'] ) && 
			isset( $this->request->data['items'] ) && 
			$this->Portal->verifyKey($this->request->data['object_id'], $this->request->data['key'])
		) {
			
			$res = $this->Portal->savePromises($this->request->data['object_id'], $this->request->data['items']);
			
		} else {
			$res = false;
		}
		
		$this->set('res', $res);
		$this->set('_serialize', 'res');
		
	}
	
	public function saveWpf() {
		
		if(
			isset( $this->request->data['id'] ) && 
			isset( $this->request->data['wpf'] ) && 
			isset( $this->request->data['wpf']['lat'] ) && 
			isset( $this->request->data['wpf']['lon'] ) && 
			isset( $this->request->data['wpf']['zoom'] ) 
		) {
					
			$res = $this->Portal->saveWpf($this->request->data['id'], $this->request->data['wpf']['lat'], $this->request->data['wpf']['lon'], $this->request->data['wpf']['zoom']);
		
		} else {
			$res = false;
		}
		
		$this->set('res', $res);
		$this->set('_serialize', 'res');
		
	}

}
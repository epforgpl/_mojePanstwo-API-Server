<?php

class PodatkiController extends AppController
{

    public function stat()
    {
				
        $data = $this->Podatki->stat($this->request->data);
		
        $this->set('data', $data);
        $this->set('_serialize', 'data');

    } 
    
    public function sendData()
    {
				
        $data = $this->Podatki->sendData($this->request->data);
		
        $this->set('data', $data);
        $this->set('_serialize', 'data');

    }    

}
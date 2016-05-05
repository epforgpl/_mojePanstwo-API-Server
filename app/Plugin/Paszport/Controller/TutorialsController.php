<?php

class TutorialsController extends PaszportAppController
{
	
    public function index()
    {        
        $data = $this->Tutorial->index($this->Auth->user('id'));
        
        $this->set(array(
            'data' => $data,
            '_serialize' => 'data',
        ));
    }
    
    public function edit($tutoral_id)
    {        
        $data = $this->Tutorial->edit($this->Auth->user('id'), $tutoral_id, $this->request->data);
        
        $this->set(array(
            'data' => $data,
            '_serialize' => 'data',
        ));
    }
}
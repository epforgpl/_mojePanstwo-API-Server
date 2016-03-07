<?php

class SrodowiskoController extends AppController
{

    public function data()
    {		    
        $data = $this->Srodowisko->getData( $this->request->query['param'] );

        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }
    
} 
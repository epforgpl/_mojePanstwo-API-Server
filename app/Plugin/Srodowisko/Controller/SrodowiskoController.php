<?php

class SrodowiskoController extends AppController
{

    public $uses = array('Srodowisko.Srodowisko');

    public function getData() {
        $this->set('response', $this->Srodowisko->getChartData(
            $this->request->data['station_id'],
            $this->request->data['param'],
            $this->request->data['timestamp']
        ));

        $this->set('_serialize', 'response');
    }

    public function getRankingData() {
	    
	    if( $this->request->data['param']=='PM2_5' )
	    	$this->request->data['param']='PM2.5';
	    
        $this->set('response', $this->Srodowisko->getRankingData(
            $this->request->data['param'],
            $this->request->data['option'],
            isset($this->request->data['order']) ? $this->request->data['order'] : 'best'
        ));

        $this->set('_serialize', 'response');
    }

    public function data()
    {		    
        $data = $this->Srodowisko->getData( $this->request->query['param'] );

        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }
    
} 


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
	    
	    if(
		    @$this->request->query['rank'] &&
		    in_array($this->request->query['rank'], array('3d', '1w', '1m'))
	    ) {
	    
	    	$data = $this->Srodowisko->getRankingData(
	            $this->request->query['param'],
	            $this->request->query['rank'],
	            isset($this->request->data['order']) ? $this->request->data['order'] : 'best'
	        );
	    
	    } else {
	    
	        $data = $this->Srodowisko->getData( $this->request->query['param'], $this->request->query['rank'] );
        
        }

        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }
    
} 


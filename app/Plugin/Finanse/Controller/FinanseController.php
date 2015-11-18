<?php

class FinanseController extends AppController
{

    public function getBudgetSpendings()
    {
        $data = $this->Finanse->getBudgetSpendings();

        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }

    public function getCommunePopCount($id) {
        $data = $this->Finanse->getCommunePopCount($id);
        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }
    
    public function getBudgetSections()
    {
        $data = $this->Finanse->getBudgetSections();

        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }

    public function getBudgetData2()
    {
        $gmina_id = isset($this->request->query['gmina_id'] ) ? $this->request->query['gmina_id'] : false;
        $data = $this->Finanse->getBudgetData2($gmina_id);
        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }

    public function getCommuneData()
    {
        $gmina_id = isset($this->request->query['id'] ) ? $this->request->query['id'] : 0;
        $data = $this->Finanse->getCommuneData($gmina_id);
        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }
    
    public function getBudgetData()
    {
    	$gmina_id = isset($this->request->query['gmina_id'] ) ? $this->request->query['gmina_id'] : false;
        $data = $this->Finanse->getBudgetData($gmina_id);
        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }

    public function getCommuneBudgetData()
    {
        $data = array(1,2,3);
        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }
    public function getPkb()
    {
        $data = $this->Finanse->getPkb();

        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }
    
    public function getCompareData()
    {
        $data = $this->Finanse->getCompareData($this->request->query['p1'], $this->request->query['p2']);
        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }
    
    public function getSpendings()
    {
	    
	    $data = $this->Finanse->getSpendings( $this->request->params['id'] );
	    $this->set('data', $data);
        $this->set('_serialize', 'data');
	    
    }

} 
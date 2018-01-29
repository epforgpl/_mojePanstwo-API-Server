<?php

class WyjazdyposlowController extends AppController
{

    public function stats()
    {
        $stats = $this->Wyjazdyposlow->getStats();

        $this->set('stats', $stats);
        $this->set('_serialize', 'stats');
    }
    
    public function stats8()
    {
        $stats = $this->Wyjazdyposlow->getStats8();

        $this->set('stats', $stats);
        $this->set('_serialize', 'stats');
    }

    public function world() {
        $this->setSerialized('ret', $this->Wyjazdyposlow->getWorldStats());
    }
    
    public function world8() {
        $this->setSerialized('ret', $this->Wyjazdyposlow->getWorldStats8());
    }

    public function countryDetails() {
        $this->setSerialized('ret', $this->Wyjazdyposlow->getCountryDetails($this->request->params['countrycode']));
    }
    
    public function countryDetails8() {
        $this->setSerialized('ret', $this->Wyjazdyposlow->getCountryDetails8($this->request->params['countrycode']));
    }
    
} 
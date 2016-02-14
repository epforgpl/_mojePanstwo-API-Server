<?php

class StatsController extends AppController
{

	public $uses = array('HandelZagraniczny.Stats');

    public function stats()
    {
        $stats = $this->Zamowieniapubliczne->getStats();

        $this->set('stats', $stats);
        $this->set('_serialize', 'stats');
    }

    public function panstwa()
    {
        $params = $this->request->query;

        $validParams = array(
            'symbol_id'    => 0,
            'rocznik'       => 0,
            'order'         => null
        );

        foreach($params as $name => $value) {
            if(isset($validParams[$name]))
                $validParams[$name] = $value;
        }

        $validParams['order'] = isset($params['order']) ? $params['order'] : 'import';

        $stats = $this->Stats->getPanstwa($validParams);
        $this->set('stats', $stats);
        $this->set('_serialize', 'stats');
    }

    public function towary()
    {
        $params = $this->request->query;

        $validParams = array(
            'panstwo_id'    => 0,
            'rocznik'       => 0,
            'order'         => null
        );

        foreach($params as $name => $value) {
            if(isset($validParams[$name]))
                $validParams[$name] = $value;
        }

        $validParams['order'] = isset($params['order']) ? $params['order'] : 'import';

        $stats = $this->Stats->getTowary($validParams);
        $this->set('stats', $stats);
        $this->set('_serialize', 'stats');
    }
    
    public function newstats()
    {
        $data = $this->Zamowieniapubliczne->getNewStats();

        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }

    public function getSymbols()
    {
        $params = array(
            'parent_id'     => 0,
            'year'          => 2015,
            'type'          => 'import',
            'limit'         => 5,
            'country_id'    => 0,
        );

        foreach($params as $param => $value) {
            if(isset($this->request->query[$param]))
                $params[$param] = $this->request->query[$param];
        }

        $this->set('data', $this->Stats->getSymbols($params));
        $this->set('_serialize', 'data');
    }

    public function getTopSymbolsData()
    {
        $year = $this->countriesDataYearValidator($this->request->query);
        $data = $this->Stats->getTopSymbolsData($year);
        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }

	public function getCountriesData()
    {
        $type = $this->countriesDataTypeValidator($this->request->query);
        $year = $this->countriesDataYearValidator($this->request->query);

        $data = $this->Stats->getCountriesData($type, $year);

        $this->set('data', $data);
        $this->set('_serialize', 'data');
    }

    private function countriesDataYearValidator($query)
    {
        $year = (int) (isset($query['year']) ? $query['year'] : 2014);
        if($year >= 2004 && $year <= 2015)
            return $year;
        return 2015;
    }

    private function countriesDataTypeValidator($query)
    {
        $typesMap = array(
            'bilans',
            'import',
            'eksport',
            'wymiana'
        );

        $type = (string) (isset($query['type']) ? strtolower($query['type']) : 'bilans');
        return in_array($type, $typesMap) ? $type : 'bilans';
    }

} 
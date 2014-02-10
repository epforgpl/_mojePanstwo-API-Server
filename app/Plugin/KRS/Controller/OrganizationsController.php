<?php

class OrganizationsController extends AppController
{

    public function index()
    {

        $q = @$this->request->query['q'];
        if ($q) {

            $fields = array('id', 'dataset', 'object_id', '_data_id', '_data_forma_prawna_str', '_data_nazwa_skrocona', '_data_krs');

            $conditions = array(
                'dataset' => 'krs_podmioty',
            );

            $raw_conditions = array();

            if (is_numeric($q)) {

                $num_q = str_pad($q, 10, '0', STR_PAD_LEFT);

                $raw_conditions = array(
                    '_data_krs:' . $num_q . ' OR ' . $q . '*',
                );

            } else {

                $conditions['q'] = '' . $q . '*';

            }


            $params = array(
                'conditions' => $conditions,
                'fields' => $fields,
                'limit' => 30,
            );


            if (!empty($raw_conditions))
                $params['raw_conditions'] = $raw_conditions;


            $search = ClassRegistry::init('Dane.Dataobject')->find('all', $params);

            $this->set('search', $search);
            $this->set('_serialize', array('search'));

        }

    }


    public function view()
    {

        $id = $this->request->params['id'];
        if ($id) {


            $organizations = ClassRegistry::init('Dane.Dataobject')->find('all', array(
                'conditions' => array(
                    'object_id' => $id,
                    'dataset' => 'krs_podmioty',
                ),
                'limit' => 1,
            ));

            $organization = $organizations['dataobjects'][0]['data'];

            $this->set('organization', $organization);
            $this->set('_serialize', array('organization'));


        }

    }
    
    public function getFeaturedByGroups()
    {
	   	
	   	
	   	
	   	// NAJNOWSZE ORGANIZACJE
	   	
	   	$data = ClassRegistry::init('Dane.Dataobject')->find('all', array(
	   		'conditions' => array(
	   			'dataset' => 'krs_podmioty',
	   		),
	   		'order' => 'data_rejestracji desc',
	   		'limit' => 12,
	   	));
	   	
	   	$najnowsze_organizacje = array();
	   	if( isset($data['dataobjects']) )
	   	{
		    foreach( $data['dataobjects'] as $object )
		    {
			    $najnowsze_organizacje[] = array(
			    	'type' => 'organization',
			    	'id' => $object['data']['id'],
			    	'nazwa' => $object['data']['nazwa'],
			    	'field_name' => 'Rejestracja',
			    	'field_value' => substr($object['data']['data_rejestracji'], 0, 10),
			    );
		    }
	   	}
	   	
	   		   	
	   	
	   	// NAJWIĘKSZE SPÓŁKI
	   	
	   	$data = ClassRegistry::init('Dane.Dataobject')->find('all', array(
	   		'conditions' => array(
	   			'dataset' => 'krs_podmioty',
	   		),
	   		'order' => 'wartosc_kapital_zakladowy desc',
	   		'limit' => 12,
	   	));
	   	
	   	$najwieksze_spolki = array();
	   	if( isset($data['dataobjects']) )
	   	{
		    foreach( $data['dataobjects'] as $object )
		    {
			    $najwieksze_spolki[] = array(
			    	'type' => 'organization',
			    	'id' => $object['data']['id'],
			    	'nazwa' => $object['data']['nazwa'],
			    	'field_name' => 'Kapitał zakładowy',
			    	'field_value' => substr($object['data']['wartosc_kapital_zakladowy'], 0, 10),
			    );
		    }
	   	}
	   	
	   	
	   	
	   	
	   		    
	    $groups = array(
	    	array(
	    		'id' => 'najnowsze_organizacje',
	    		'label' => 'Najnowsze organizacje',
	    		'content' => $najnowsze_organizacje,
	    	),
	    	array(
	    		'id' => 'najwieksze_spolki',
	    		'label' => 'Największe spółki',
	    		'content' => $najwieksze_spolki
	    	),
	    );
	    
	    $this->set('groups', $groups);
        $this->set('_serialize', array('groups'));
	    
    }

} 
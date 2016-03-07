<?php

/**
 * @property Srodowisko Srodowisko
 */
class SrodowiskoController extends AppController
{

    public $uses = array('Srodowisko.Srodowisko');

    public function getData() {
        $this->set('response', $this->Srodowisko->getData(
            $this->request->data['station_id'],
            $this->request->data['param']
        ));

        $this->set('_serialize', 'response');
    }

}
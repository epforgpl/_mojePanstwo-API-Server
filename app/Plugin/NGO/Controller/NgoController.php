<?php

class NgoController  extends AppController
{

    public $uses = array('NGO.NgoNewsletter');

    public function newsletter() {
        $message = null;

        if(
            !isset($this->request->data['email']) ||
            (
                isset($this->request->data['email']) &&
                filter_var($this->request->data['email'], FILTER_VALIDATE_EMAIL) === false
            )
        )
            $message = 'Nieprawidłowy adres email';
        else
            $message =$this->NgoNewsletter->save($this->request->data) ?
                'Dziękujemy za dołączenie do newslettera' :
                'Wystąpił problem, spróbuj ponownie później';

        $this->set('message', $message);
        $this->set('_serialize' , 'message');
    }
}
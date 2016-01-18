<?php

class FacebookRegistrationComponent extends Component {

    /**
     * @var User
     */
    private $Users;

    private $facebookUser;
    private $user;

    public function __construct() {
        $this->Users = ClassRegistry::init('Paszport.User');
    }

    public function setFacebookUser($facebookUser) {
        $this->facebookUser = $facebookUser;
    }

    public function register() {
        
        $this->user = $this->Users->find('first', array(
            'conditions' => array(
                'User.email' => $this->facebookUser['email']
            )
        ));
        
        if(!$this->user) {
            $this->createNewUser();
        } elseif($this->user['User']['facebook_id'] != $this->facebookUser['id']) {
            $this->setFacebookId();
        }
					
        $this->updatePhoto();
        $this->updateLoggedAt();
    }

    private function createNewUser() {
        $username = $this->facebookUser['first_name'] . $this->facebookUser['last_name'] . rand(0, 9999);

        $this->Users->clear();
        $this->Users->set(array(
            'User' => array(
                'email'         => $this->facebookUser['email'],
                'username'      => $username,
                'facebook_id'   => $this->facebookUser['id']
            )
        ));
		
        if($this->Users->validates()) {
            $this->user = $this->Users->save($this->Users->data, false, array(
                'id', 'email', 'username', 'facebook_id'
            ));

            if(!$this->user)
                throw new Exception('Internal error');
        } else {
            $errors = $this->Users->validationErrors;
            throw new Exception($errors[0]);
        }
    }

    private function setFacebookId() {
        $this->Users->clear();
        $this->Users->id = $this->user['User']['id'];
        $this->Users->set(array(
            'User' => array(
                'facebook_id' => $this->facebookUser['id']
            )
        ));
        $this->Users->save($this->Users->data, false, array('facebook_id'));
    }

    private function updateLoggedAt() {
        if(!isset($this->user['User']['id']))
            return false;

        $this->Users->clear();
        $this->Users->id = $this->user['User']['id'];
        $this->Users->save(array(
            'logged_at' => date('Y-m-d H:i:s', time())
        ));
    }

    private function updatePhoto() {
        if(isset($this->facebookUser['picture']['data']['url'])) {
            $this->Users->clear();
            $this->Users->id = $this->user['User']['id'];
            $this->Users->set(array(
                'User' => array(
                    'photo_small' => $this->facebookUser['picture']['data']['url']
                )
            ));
            $this->Users->save($this->Users->data, false, array('photo_small'));
        }
    }

    public function getUser() {
        return $this->user;
    }

}

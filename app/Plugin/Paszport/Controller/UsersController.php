<?php
App::uses('HttpSocket', 'Network/Http');
App::import('Vendor', 'Paszport.Encrypt');

/**
 * Class UsersController
 *
 *
 */
class UsersController extends PaszportAppController
{
    public $uses = array('Paszport.User', 'Paszport.UserAdditionalData');
    public $components = array('Session', 'Paszport.Image2', 'Paszport.FacebookRegistration');
    public $userFields = array('User.email', 'User.created', 'User.photo', 'User.photo_small', 'User.group_id', 'User.username');

    /**
     * Sets permissions
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
    }

    public function avatar($id = null)
    {
        $id = $this->user_id;
        if ($this->data) {
            $allowed_types = array(
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/gif' => 'gif',
                'image/png' => 'png',
            );
            if (!in_array(strtolower($this->data['User']['photo']['type']), array_keys($allowed_types))) {
                exit();
            } else {
                $filename = md5(time() . rand(0, 999)) . '.' . $allowed_types[$this->data['User']['photo']['type']];
                $filename_small = md5(time() . rand(0, 999)) . '_small.' . $allowed_types[$this->data['User']['photo']['type']];
                $path = ROOT . DS . 'app' . DS . 'Plugin' . DS . 'Paszport' . DS . 'webroot' . DS . 'uploads' . DS . 'avatars' . DS;

                file_put_contents($path . $filename, base64_decode($this->data['User']['photo']['binary']));
                file_put_contents($path . $filename_small, base64_decode($this->data['User']['photo']['binary']));
                $filename = $this->Image2->source('uploads/avatars/' . $filename)->crop(190, 190)->imagePath();
                $filename_small = $this->Image2->source('uploads/avatars/' . $filename_small)->crop(27, 27)->imagePath();
                $this->User->id = $id;
                $this->User->saveField('photo', $filename);
                $this->User->saveField('photo_small', $filename_small);
                $this->User->read();
                $this->info($id);
            }
        }
    }

    public function avatarinline($id)
    {
        $user = $this->User->find('first', array('conditions' => array('User.id' => $id), 'recursive' => -2));
        if ($user['User']['photo_small']) {
            echo json_encode(array($this->Image2->source(str_replace(FULL_BASE_URL . '/paszport/', '', $user['User']['photo_small']))->crop(27, 27)->inlineImage()));
        } else {
            echo json_encode(array($this->Image2->source(str_replace(FULL_BASE_URL . '/paszport/', '', 'default.jpg'))->crop(27, 27)->inlineImage()));
        }

        exit();
    }

    public function info()
    {
        $user = $this->user;

        if( $user )
        {
            $this->UserAdditionalData->id = $user['id'];
            $data = $this->UserAdditionalData->read(null, $user['id']);

            if( !empty($data) )
            {
                $user['unread_count'] = $data['UserAdditionalData']['alerts_unread_count'];
                $user['group'] = $data['UserAdditionalData']['group'];
            }
        }

        $this->set('user', $user);
        $this->set('_serialize', array('user', 'applications', 'streams'));
    }

    /**
     * Return full user information
     */
    public function read()
    {
        $user = $this->Auth->user();
        $user_data = $this->User->find('first', array(
            'conditions' => array('User.id' => $user['id']),
            'contain' => array('Language', 'UserExpand', 'Group'),
        ));

        // TODO should be restricted depending what app is authenticated
        $filtered_keys = array('id', 'email', 'created', 'modified', 'language_id', 'twitter_id', 'facebook_id',
            'photo', 'photo_small', 'group_id', 'username');
        $filtered_data = array_intersect_key($user_data['User'], array_flip($filtered_keys))    ;

        $this->setSerialized($filtered_data);
    }

    public function registerFromFacebook()
    {
        $response = array();
				
        try {
            $this->FacebookRegistration->setFacebookUser($this->data);
            $this->FacebookRegistration->register();
            $response['user'] = $this->FacebookRegistration->getUser();
            if(strtotime($response['user']['User']['created']) + 300 > time()) {
                $this->sendWelcomeEmail($response['user']['User']['email']);
            }
        } catch (Exception $e) {
            $response['errors'] = $e->getMessage();
        }

        $this->set(array_merge($response, array(
            '_serialize' => array_keys($response)
        )));
    }

    public function findFacebook()
    {
        if($this->data && isset($this->data['facebook_id']) && isset($this->data['email'])) {
            $user = $this->User->find('first', array(
                    'conditions' => array(
                        'User.facebook_id' => $this->data['facebook_id'],
                        'User.email' => $this->data['email']
                    ))
            );
            $this->set(array(
                'user' => $this->data,
                '_serialize' => array('user'),
            ));
        } else {
            throw new BadRequestException();
        }
    }

    public function register()
    {
        if(
            isset($this->data) &&
            is_array($this->data) &&
            (
                (
                    isset( $this->data['email'] ) &&
                    $this->data['email']
                ) ||
                (
                    isset( $this->data['User']['email'] ) &&
                    $this->data['User']['email']
                )
            )
        ) {

            $errors = array();
            $user = false;
            $moderateRequestFields = array(
                'krs_pozycje_nazwa',
                'organization_object_id',
                'organization_firstname',
                'organization_lastname',
                'organization_function',
                'organization_phone_number'
            );

            $createModerateRequest = false;

            if(isset($this->data['User']['is_ngo']) && $this->data['User']['is_ngo'] == '1') {
                $createModerateRequest = array();
                foreach($moderateRequestFields as $field)
                    $createModerateRequest[$field] = isset($this->data[$field]) ? $this->data[$field] : NULL;
            }

            $this->User->set($this->data);

            if($this->User->validates()) {

                $this->User->data['User']['password'] = $this->Auth->password( $this->User->data['User']['password'] );
                $this->User->data['User']['group_id'] = 1;

                $this->User->getDataSource()->begin();

                $saved = $this->User->save($this->User->data, false, array(
                    'id', 'email', 'password', 'username', 'group_id', 'language_id', 'is_ngo'
                ));

                if ($saved) {

                    try {
                        $this->UserAdditionalData->save(array('id' => $this->User->id));
                        $this->User->getDataSource()->commit();

                    } catch (Exception $e) {
                        $this->User->getDataSource()->rollback();
                        throw $e;
                    }

                    if( $user = $this->User->find('first', array(
                        'fields' => $this->userFields,
                        'conditions' => array(
                            'User.id' =>  $this->User->id,
                        ),
                    )) ) {

                        $user = $user['User'];
                        $this->Auth->login(array(
                            'type' => 'account',
                            'id' => $this->User->id,
                        ));

                        $this->User->clear();
                        $this->User->id = $user['id'];
                        $this->User->save(array(
                            'logged_at' => date('Y-m-d H:i:s', time())
                        ));

                        $this->sendWelcomeEmail($user['email']);

                        if(is_array($createModerateRequest)) {
                            App::import('Model','Dane.Dataobject');
                            $dataObjectModel = new Dataobject();

                            $results = $dataObjectModel->moderate_request(
                                array(
                                    'firstname' => $createModerateRequest['organization_firstname'],
                                    'lastname' => $createModerateRequest['organization_lastname'],
                                    'position' => $createModerateRequest['organization_function'],
                                    'organization' => $createModerateRequest['krs_pozycje_nazwa'],
                                    'email' => $user['email'],
                                    'phone' => $createModerateRequest['organization_phone_number']
                                ),
                                (int) $createModerateRequest['organization_object_id'],
                                'krs_podmioty'
                            );

                            if(isset($results['error'])) {
                                $errors[] = $results['error'];
                            }
                        }

                    } else $errors = array('Internal error');
                } else $errors = $this->User->validationErrors; // email verification
            } else $errors = $this->User->validationErrors; // basic validation

            $this->set(array(
                'errors' => $errors,
                'user' => $user,
                '_serialize' => array('errors', 'user'),
            ));

        } else {
            throw new BadRequestException();
        }
    }

    private function sendWelcomeEmail($email) {
        App::uses('CakeEmail', 'Network/Email');
        $Email = new CakeEmail('noreply');

        return $Email->template('Paszport.welcome')
            ->addHeaders(array('X-Mailer' => 'mojePaństwo'))
            ->emailFormat('html')
            ->subject('Miło Cię gościć na Moim Państwie.')
            ->to($email)
            ->from('biuro@mojepanstwo.pl', 'mojePaństwo')
            ->replyTo('biuro@mojepanstwo.pl', 'mojePaństwo')
            ->send();
    }

    public function login()
    {
        if ($this->data && isset($this->data['password']) && isset($this->data['email'])) {
            $data = $this->data;
            $user = $this->User->find('first', array('conditions' => array('User.email' => $data['email'])));

            if ($user) {
                if($user['User']['password'] == $this->Auth->password($data['password'])) {
                    unset($user['User']['password']);
                    unset($user['User']['repassword']);
                    $this->User->clear();
                    $this->User->id = $user['User']['id'];
                    $this->User->save(array(
                        'logged_at' => date('Y-m-d H:i:s', time())
                    ));

                    $this->set(array(
                        'user' => $user,
                        '_serialize' => 'user',
                    ));
                }
                else {
                    throw new ForbiddenException();
                }

            } else {
                throw new NotFoundException();
            }

        } else {
            throw new BadRequestException();
        }
    }

    public function canCreatePassword() {
        $response = false;

        $user = $this->User->find('first', array(
            'conditions' => array(
                'User.id' => (int) $this->Auth->user('id')
            )
        ));

        if((int) $user['User']['facebook_id'] > 0 && $user['User']['password'] == '')
            $response = true;

        $this->set(array(
            'response' => $response,
            '_serialize' => 'response'
        ));
    }

    public function setIsNgo() {
        $this->Auth->deny();
        if($this->Auth->user('type') != 'account')
            throw new ForbiddenException();

        $response = false;
        $id = (int) $this->Auth->user('id');
        if($this->request->isPost() && isset($this->data['is_ngo'])) {
            $this->User->id = $id;
            $this->User->save(array('User' => array(
                'is_ngo' => $this->data['is_ngo'] == '1' ? '1' : '0',
            )));
            $response = true;
        }

        $this->set(array(
            'response' => $response,
            '_serialize' => 'response',
        ));
    }

    public function setUserName() {
        $this->Auth->deny();
        if($this->Auth->user('type') != 'account')
            throw new ForbiddenException();

        $response = false;
        $id = (int) $this->Auth->user('id');
        if($this->request->isPost() && isset($this->data['value'])) {
            $this->User->id = $id;
            $this->User->set(array('User' => array(
                'username' => $this->data['value'],
            )));
            if($this->User->validates(array('fieldList' => array('username')))) {
                $this->User->save(array('username' => $this->data['value']));
                $response = true;
            }
        }

        $this->set(array(
            'response' => $response,
            '_serialize' => 'response',
        ));
    }

    public function setEmail() {
        $this->Auth->deny();
        if($this->Auth->user('type') != 'account')
            throw new ForbiddenException();

        $response = false;
        $id = (int) $this->Auth->user('id');
        if($this->request->isPost() && isset($this->data['value'])) {
            $this->User->id = $id;
            $this->User->set(array('User' => array(
                'email' => $this->data['value'],
            )));
            if($this->User->validates(array('fieldList' => array('email')))) {
                $this->User->save(array('email' => $this->data['value']));
                $response = true;
            }
        }

        $this->set(array(
            'response' => $response,
            '_serialize' => 'response',
        ));
    }

    public function setUserPassword() {
        $this->Auth->deny();
        if($this->Auth->user('type') != 'account')
            throw new ForbiddenException();

        $response = false;
        $id = (int) $this->Auth->user('id');
        if($this->request->isPost() && isset($this->data['old_password']) && isset($this->data['new_password'])) {
            $user = $this->User->find('first', array('conditions' => array('User.id' => $id)));
            if(($user['User']['password'] == $this->Auth->password($this->data['old_password']))) {
                $this->User->id = $id;
                $this->User->set(array('User' => array(
                    'password' => $this->data['new_password'],
                )));
                if($this->User->validates(array('fieldList' => array('password')))) {
                    $this->User->save(array('password' => $this->Auth->password($this->data['new_password'])));
                    $response = true;
                }
            }
        }

        $this->set(array(
            'response' => $response,
            '_serialize' => 'response',
        ));
    }

    public function createNewPassword() {
        $this->Auth->deny();
        if($this->Auth->user('type') != 'account')
            throw new ForbiddenException();

        $response = false;
        $id = (int) $this->Auth->user('id');
        if($this->request->isPost() && isset($this->data['password']) && isset($this->data['confirm_password'])) {
            $user = $this->User->find('first', array('conditions' => array('User.id' => $id)));
            $this->User->id = $id;
            $this->User->set(array('User' => array(
                'password' => $this->data['password'],
            )));
            if($this->User->validates(array('fieldList' => array('password')))) {
                $this->User->save(array('password' => $this->Auth->password($this->data['password'])));
                $response = true;
            }
        }

        $this->set(array(
            'response' => $response,
            '_serialize' => 'response',
        ));
    }

    public function deletePaszport()
    {
        $this->Auth->deny();
        if($this->Auth->user('type') != 'account')
            throw new ForbiddenException();

        $response = false;
        $id = (int) $this->Auth->user('id');

        if($this->request->isPost() && isset($this->data['password'])) {
            $exists = $this->User->find('count', array('conditions' => array('User.id' => $id, 'User.password' => $this->Auth->password($this->data['password']))));
            if($exists > 0) {
                $this->User->delete($id);
                $response = true;
            }
        }

        $this->set(array(
            'response' => $response,
            '_serialize' => 'response',
        ));
    }

    /**
     * forces password for just registered FB users
     */
    public function setpassword()
    {
        $id = $this->authorizeUser();
        $user = $this->User->find('first', array('recursive' => -2, 'conditions' => array('User.id' => $id)));
        if($user['User']['password_set']) {
            $this->set('_serialize', '');
        }

        $this->set(array(
            'status' => 400,
            'errors' => 'bad request',
            '_serialize' => array('errors', 'status'),
        ));

        CakeLog::debug(print_r($this->data, true));
        if ($this->request->isPost() && isset($this->data['User']['password'])) {
            $this->User->id = $id;

            // verify password
            $this->User->set(array('User' => array(
                'password' => $this->data['User']['password'],
            )));

            if ($this->User->validates(array('fieldList' => array('password')))) {
                if ($this->User->save(array('password' => $this->Auth->password($this->data['User']['password']), 'password_set' => 1))) {
                    $this->set('_serialize', '');

                } else {
                    $this->set(array(
                        'status' => 422,
                        'errors' => $this->User->validationErrors,
                        '_serialize' => array('errors', 'status'),
                    ));
                }

            } else {
                $this->set(array(
                    'status' => 422,
                    'errors' => $this->User->validationErrors,
                    '_serialize' => array('errors', 'status'),
                ));
            }
        }
    }

    /**
     * Switcher to attach profiles
     * @param string|null $profile
     */
    public function attachprofile($profile = null)
    {
        if (is_null($profile)) {
            exit();
        }

        switch ($profile) {
            case "facebook":
                $this->__attachFacebook();
                break;
            case "gplus":
                //@TODO add gplus
                break;

        }
        exit();
    }

    /**
     * Switcher to attach profiles
     * @param string|null $profile
     */
    public function deattachprofile($profile = null)
    {
        if (is_null($profile)) {
            exit();
        }

        switch ($profile) {
            case "facebook":
                $this->__attachFacebook(true);
                break;
            case "gplus":
                //@TODO add gplus
                break;

        }
        exit();
    }


    /**
     * @param bool $deattach - on true deletes the relation
     * @return bool
     */
    public function __attachFacebook($deattach = false, $redirect = null)
    {
        if ($deattach) {
            $this->User->id = $this->Auth->user('id');
            if ($this->User->saveField('facebook_id', null)) {
                $this->_log(array('msg' => 'LC_PASZPORT_LOG_FB_DEATTACHED', 'ip' => $this->request->clientIp(), 'user_agent' => env('HTTP_USER_AGENT')));
            }
            return true;
        }
        # check if user has already given permissions to the app
        $user_data = $this->Connect->FB->api('/me/?fields=id,first_name,last_name,email,gender,picture.type(square).width(200),locale');
        if ($user_data['id']) { # merge, save, inform
            $this->User->id = $this->Auth->user('id');
            $to_save = array(
                'User' => array(
                    'facebook_id' => $user_data['id'],
                ),
            );
            $user_photo = $this->User->find('first', array('conditions' => array('User.id' => $this->Auth->user('id')), 'recursive' => -2, 'fields' => array('User.photo')));
            if (!$user_photo['User']['photo']) {
                $this->User->Behaviors->load('Upload.Upload', array('photo' => array('path' => '{ROOT}webroot{DS}uploads{DS}{model}{DS}{field}{DS}')));
                $to_save['User']['photo'] = preg_replace('/https/', 'http', $user_data['picture']['data']['url']);
            }
            if ($this->User->save($to_save)) {
                $this->_log(array('msg' => 'LC_PASZPORT_LOG_FB_ACCOUNT_MERGED', 'ip' => $this->request->clientIp(), 'user_agent' => env('HTTP_USER_AGENT')));
            } else {
                exit();
            }
        } else {
            exit();
        }
    }


    /**
     *
     * Converts FB male|female to our int representatives
     * @param string $gender
     * @return int
     */
    public function __fbGender($gender)
    {
        switch ($gender) {
            case "male":
                return 1;
                break;
            case "female":
                return 2;
                break;
            default:
                return 0;
                break;
        }
    }

    /**
     * Converts rfc language definitions country_LOCALE into our models
     *
     * @param string $rfc_lang
     * @return int
     */
    public function __fbLanguage($rfc_lang)
    {
        $lang = $this->User->Language->find('first', array('recursive' => -2, 'conditions' => array('rfc_code' => $rfc_lang)));
        if ($lang) {
            return $lang['Language']['id'];
        } else {
            return 2; # english
        }
    }

    public function forgotNewPassword() {
        $errors = array();
        $success = false;

        if($this->data && $this->data['User']['password'] && $this->data['token']) {
            $password = $this->Auth->password($this->data['User']['password']);
            $token = urlencode($this->data['token']);

            $user = $this->User->find('first', array(
                'conditions' => array(
                    'User.reset_hash' => $token
                )
            ));

            if($user['User']) {
                $this->User->id = $user['User']['id'];
                $this->User->set(array('User' => array(
                    'password' => $password
                )));
                $this->User->save(array('password' => $password));
                $success = true;
            } else {
                $errors[] = 'LC_PASZPORT_SECURITY_TOKEN_INVALID';
            }

        } else {
            $errors[] = 'BAD_REQUEST';
        }

        $this->set(array(
            'success' => $success,
            'errors' => $errors,
            '_serialize' => array('success', 'errors'),
        ));
    }

    public function forgotToken() {
        $errors = array();
        $success = false;

        if($this->data && $this->data['token']) {
            $hash = $this->data['token'];
            $hash = str_replace(' ', '+', urldecode($hash));
            $e = new Encryption(MCRYPT_BlOWFISH, MCRYPT_MODE_CBC);

            $token_data = json_decode($e->decrypt(base64_decode($hash), Configure::read('Security.salt')), true);
            $user_email = $token_data['email'];
            $expires = $token_data['expires'];
            if (time() > $expires) {
                $errors[] = 'LC_PASZPORT_SECURITY_TOKEN_EXPIRED';
            } else {
                $user = $this->User->find('first', array(
                    'conditions' => array(
                        'User.email' => $user_email,
                        'User.reset_hash' => urlencode($this->data['token'])
                    )
                ));

                $user = isset($user['User']) ? $user['User'] : false;
                if ($user) {
                    $success = true;
                } else {
                    $errors[] = 'LC_PASZPORT_SECURITY_TOKEN_INVALID';
                }
            }
        } else {
            $errors[] = 'BAD_REQUEST';
        }

        $this->set(array(
            'success' => $success,
            'errors' => $errors,
            '_serialize' => array('success', 'errors'),
        ));
    }

    public function forgot()
    {
        App::uses('CakeEmail', 'Network/Email');
        $errors = array();
        $success = false;
        if($this->data && $this->data['User']['email']) {
            $user = $this->User->find('first', array('conditions' => array('User.email' => $this->data['User']['email']), 'recursive' => -2));
            if($user) {
                $Email = new CakeEmail();
                $Email->config('noreply');
                $Email->to($user['User']['email'])
                    ->template('Paszport.reset')
                    ->emailFormat('html')
                    ->subject('Zmiana hasła');

                $e = new Encryption(MCRYPT_BlOWFISH, MCRYPT_MODE_CBC);
                $data = json_encode(array(
                    'email' => $user['User']['email'],
                    'expires' => strtotime('+24 hours')
                ));
                $hash = base64_encode($e->encrypt($data, Configure::read('Security.salt')));
                $Email->viewVars(array('hash' => urlencode($hash)));

                if($Email->send()) {
                    $this->User->id = $user['User']['id'];
                    $this->User->set(array('User' => array(
                        'reset_hash' => urlencode($hash)
                    )));
                    $this->User->save(array('reset_hash' => urlencode($hash)));
                    //$this->User->field('users', $user['User']['id'], array('User' => array('reset_hash' => urlencode($hash))));
                    $success = true;
                } else {
                    $errors[] = 'LC_MAIL_SEND_ERROR';
                }
            } else {
                $errors[] = 'LC_PASZPORT_USER_DOES_NOT_EXIST';
            }
        } else {
            $errors[] = 'BAD_REQUEST';
        }

        $this->set(array(
            'success' => $success,
            'errors' => $errors,
            '_serialize' => array('success', 'errors'),
        ));
    }

    /**
     *
     * Generates token, sends mail, validates token and redirect to password changing method
     * @return bool
     */
    private function __forgot()
    {
        App::uses('CakeEmail', 'Network/Email');
        if ($this->request->isPost()) { # if post then someone sent form, we should find user with given e-mail
            $user = $this->User->find('first', array('conditions' => array('User.email' => $this->data['User']['email']), 'recursive' => -2));
            if ($user) { # if user exists send email
                $Email = new CakeEmail();
                $Email->config('smtp');

                $Email->to($user['User']['email']);
                $Email->subject(__('LC_PASZPORT_MAIL_RESET_PASS_SUBJECT', true));
                $e = new Encryption(MCRYPT_BlOWFISH, MCRYPT_MODE_CBC);
                $data = json_encode(array('email' => $user['User']['email'], 'expires' => strtotime('+24 hours')));
                $hash = base64_encode($e->encrypt($data, Configure::read('Security.salt')));
                $Email->viewVars(array('hash' => urlencode($hash)));
                if ($Email->send()) {
                    $this->_log(array('msg' => 'LC_PASZPORT_LOG_MAIL_RESET_PASS_SENT', 'ip' => $this->request->clientIp(), 'user_agent' => env('HTTP_USER_AGENT')));
                    $this->User->id = $user['User']['id'];
                    $this->User->saveField('reset_hash', urlencode($hash));
                }
            } else { # if not display error
                exit();
            }
        } else { # if the request was not post
            if (isset($this->request->query['token'])) { # but it has $hash sent, we are going to change user's password
                $hash = $this->request->query['token'];
                $hash = str_replace(' ', '+', urldecode($hash));
                $e = new Encryption(MCRYPT_BlOWFISH, MCRYPT_MODE_CBC);
                $token_data = json_decode($e->decrypt(base64_decode($hash), Configure::read('Security.salt')), true);
                $user_email = $token_data['email'];
                $expires = $token_data['expires'];

                if (time() > $expires) {
                    return false;
                } else {
                    $user = $this->User->find('first', array('recursive' => -2, 'conditions' => array('User.email' => $user_email, 'User.reset_hash' => urlencode($this->request->query['token']))));
                    if (!$user) {
                        exit();
                    }
                }

            }
        }
    }

    /**
     *
     * Sets password given by user
     * clears the reset_hash field in DB
     */
    public function reset()
    {
        $this->set(array(
            'status' => 400,
            'errors' => 'bad request',
            '_serialize' => array('errors', 'status'),
        ));

        if ($this->data['User']) {
            // verify password
            $to_save = array('User' => array(
                'password' => $this->data['User']['password'],
                'repassword' => $this->data['User']['repassword'],
                'reset_hash' => '',
                'id' => $this->data['User']['id']
            ));
            $this->actAsUser($this->data);

            $this->User->set($to_save);
            if ($this->User->validates(array('fieldList' => array('repassword', 'password', 'reset_hash')))) {
                $to_save['User']['password'] = $to_save['User']['repassword'] = $this->Auth->password($this->data['User']['password']);

                if ($this->User->save($to_save)) {
                    $this->set('_serialize', '');
                    $this->_log(array('msg' => 'LC_PASZPORT_LOG_PASSWORD_RESET_SUCCESS', 'ip' => $this->request->clientIp(), 'user_agent' => env('HTTP_USER_AGENT')));

                } else {
                    $this->set(array(
                        'status' => 422,
                        'errors' => $this->User->validationErrors,
                        '_serialize' => array('errors', 'status'),
                    ));
                }
            } else {
                $this->set(array(
                    'status' => 422,
                    'errors' => $this->User->validationErrors,
                    '_serialize' => array('errors', 'status'),
                ));
            }
        }
    }


    /**
     * Logout
     */
    public function logout()
    {
        if ($this->request->isAjax()) {
            $this->requestAction($this->Auth->logout());
            echo json_encode(array('error' => '', 'status' => 200, 'msg' => __('LC_PASZPORT_LOGOUT', true)));
            die();
        }
        $this->_log(array('msg' => 'LC_PASZPORT_LOG_LOGOUT', 'ip' => $this->request->clientIp(), 'user_agent' => env('HTTP_USER_AGENT')));
    }

    /**
     * Register
     */
    public function add()
    {
        $to_save = $this->data;
        if ($to_save && array_key_exists('User', $to_save)) {
            $this->User->set($to_save);

            if (!$this->User->validates()) {
                $this->set(array(
                    'errors' => $this->User->validationErrors,
                    '_serialize' => array('errors'),
                ));

                return;
            }

            $to_save['User']['password'] = $this->Auth->password($this->data['User']['password']);
            $to_save['User']['repassword'] = $this->Auth->password($this->data['User']['repassword']);

            $this->User->getDataSource()->begin();
            $saved = $this->User->save($to_save);
            if ($saved) {
                try {
                    $this->UserAdditionalData->save(array('id' => $this->User->id));
                    $this->User->getDataSource()->commit();

                } catch (Exception $e) {
                    $this->User->getDataSource()->rollback();
                    throw $e;
                }

                $this->actAsUser($saved);
                $this->info();

            } else {
                $this->set(array(
                    'errors' => $this->User->validationErrors,
                    '_serialize' => array('errors'),
                ));
            }

        } else {
            // invalid request
            throw new BadRequestException('Missing User data');
        }
    }

    private function authorizeUser() {
        if (isset($this->user_id)) {
            return $this->user_id;
        }

        if (!empty($this->passedArgs) && intval($this->passedArgs[0]) > 0) {
            return $this->passedArgs[0];
        }

        throw new UnauthorizedException();
    }

    /**
     * Saves changes to one field in model
     * return json response about success or failure
     */
    public function field()
    {
        $id = $this->authorizeUser();

        $forbiddenFields = array('id', 'password', 'pass', 'newpass', 'confirmnewpass');
        CakeLog::debug(print_r($this->data, true));

        if ($this->data && isset($this->data['User'])) {
            $to_save = array();
            $err_msg = null;
            $this->User->id = $id;

            if (isset($this->data['User']['pass']) && isset($this->data['User']['newpass']) && isset($this->data['User']['confirmnewpass'])) {
                // authorize password again
                $user = $this->User->find('first', array('conditions' => array('User.id' => $id)));

                if ($user['User']['password'] != $this->Auth->password($this->data['User']['pass'])) {
                    $err_msg = __('LC_PASZPORT_OLD_PASSWORD_INVALID', true);

                } else {
                    // verify password
                    $this->User->set(array('User' => array(
                        'password' => $this->data['User']['newpass'],
                        'repassword' => $this->data['User']['confirmnewpass'],
                    )));

                    if ($this->User->validates(array('fieldList' => array('repassword', 'password')))) {
                        $to_save['User']['password'] = $this->Auth->password($this->data['User']['newpass']);

                    } else {
                        $this->set(array(
                            'status' => 422,
                            'errors' => $this->User->validationErrors,
                            '_serialize' => array('errors', 'status'),
                        ));
                        return;
                    }
                }
            } else {
                foreach ($this->data['User'] as $field => $value) {
                    if (!in_array($field, $forbiddenFields)) {
                        $to_save['User'][$field] = $value;
                    }
                }
            }

            if (!empty($to_save)) {
                if ($this->User->save($to_save)) {
                    $this->set(array(
                        'status' => 200,
                        'alerts' => array(
                            'success' => array(__('LC_PASZPORT_SAVED', true)),
                        ),
                        '_serialize' => array('status', 'alerts'),
                    ));

                } else {
                    $this->set(array(
                        'status' => 422,
                        'errors' => $this->User->validationErrors,
                        '_serialize' => array('errors'),
                    ));
                }
            } else {
                if (is_null($err_msg)) {
                    $this->set(array(
                        'status' => 200,
                        'alerts' => array(
                            'success' => array(__('LC_PASZPORT_SAVED', true)),
                        ),
                        '_serialize' => array('status', 'alerts'),
                    ));

                } else {
                    $this->set(array(
                        'status' => 500,
                        'alerts' => array(
                            'error' => array($err_msg),
                        ),
                        '_serialize' => array('status', 'alerts'),
                    ));
                }
            }

        } else {
            throw new BadRequestException(__('LC_PASZPORT_NO_DATA', true));
        }
    }

    /**
     * deletes currently logged acount
     * verifies users based on password he has retyped
     */
    public function delete()
    {
        $id = $this->authorizeUser();

        if (!isset($this->data['User']['password'])) {
            throw new BadRequestException();
        }

        $exists = $this->User->find('count', array('conditions' => array('User.id' => $id, 'User.password' => $this->Auth->password($this->data['User']['password']))));
        if ($exists > 0) {
            $this->User->delete($id);
            $this->set(array(
                '_serialize' => ''
            ));

        } else {
            $this->set(array(
                'errors' => array('errors' => array('Password is invalid')),
                '_serialize' => 'errors'
            ));
        }
    }

    public function bar($logged = false)
    {
        $this->autoRender = false;
        $this->layout = 'plain';
        if ($this->data) {
            if ($logged) {
                echo json_encode(array('topbar' => file_get_contents(ROOT . DS . 'app' . DS . 'Plugin' . DS . 'Paszport' . DS . 'webroot' . DS . 'bar_logged.ctp')));
                exit();

            } else {
                if ($this->data['Topbar']['md5'] == md5(file_get_contents(ROOT . DS . 'app' . DS . 'Plugin' . DS . 'Paszport' . DS . 'webroot' . DS . 'bar.ctp'))) {
                    echo json_encode(array('status' => 'nochange'));
                    die();
                } else {
                    echo json_encode(array('topbar' => file_get_contents(ROOT . DS . 'app' . DS . 'Plugin' . DS . 'Paszport' . DS . 'webroot' . DS . 'bar.ctp')));
                    exit();
                }
            }
        }
        exit();
    }

    public function find() {
        if(!isset($this->data['type']) || !isset($this->data['conditions']))
            throw new BadRequestException();

        $user = $this->User->find($this->data['type'], $this->data['conditions']);
        $this->set(array(
            'user' => $user,
            '_serialize' => 'user'
        ));
    }

    /*
     * Pobieranie obiektów (objects.id, objects.slug)
     * administrowanych przez zalogowanego użytkownika.
     */
    public function getObjects() {
        $this->Auth->deny();
        if($this->Auth->user('type') != 'account')
            throw new ForbiddenException();

        $objects = $this->User->query("
            SELECT
              `objects`.`id`,
              `objects`.`slug`
            FROM
              `objects-users`
            INNER JOIN
              `objects` ON
                `objects`.`dataset` = `objects-users`.`dataset` AND
                `objects`.`object_id` = `objects-users`.`object_id`
            WHERE
              `objects-users`.`user_id` = ". $this->Auth->user('id') ." AND
              `objects-users`.`role` > 0
        ");

        $this->set(array(
            'objects' => $objects,
            '_serialize' => 'objects'
        ));
    }

    public function getUsersByEmail() {
        $suggestions = array();
        $query = @$this->data['query'];

        if($query) {
            $results = $this->User->find('all', array(
                'fields' => array(
                    'User.id',
                    'User.email'
                ),
                'limit' => 5,
                'conditions' => array(
                    'User.email LIKE' => "%$query%"
                ),
                'recursive' => 0
            ));

            foreach($results as $row) {
                $suggestions[] = array(
                    'id' => $row['User']['id'],
                    'value' => $row['User']['email']
                );
            }
        }

        $this->set('suggestions', $suggestions);
        $this->set('_serialize', array('suggestions'));
    }

}

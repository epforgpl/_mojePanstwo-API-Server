<?php

class User extends PaszportAppModel
{
    public $belongsTo = array('Paszport.Language', 'Paszport.Group');
    public $hasAndBelongsToMany = array('Paszport.Service');
    public $hasMany = array('Paszport.Key', 'Paszport.UserExpand', 'Paszport.UserRole');
    public $actsAs = array('Containable', 'Expandable.Expandable' => array('with' => 'UserExpand'));
    public $name = 'Paszport.User';
    public $useTable = 'users';

    public function __construct($id = false, $table = null, $ds = null)
    {
        parent::__construct($id, $table, $ds);
        $this->validator()->add('username', array(
            'unique' => array(
                'rule' => 'isUnique',
                'message' => __('LC_PASZPORT_USERNAME_MUST_BE_UNIQUE', true),
            ),
            /*
            'alphanumeric' => array(
                'rule' => 'alphaNumericDashUnderscore',
                'message' => __('LC_PASZPORT_ALPHANUMERIC', true)
            )
            */
        ));

        $this->validator()->add('email', array(
            'unique' => array(
                'rule' => 'isUnique',
                'message' => __('LC_PASZPORT_EMAIL_MUST_BE_UNIQUE', true)
            ),
            'email' => array(
                'rule' => 'email',
                'message' => __('LC_PASZPORT_NOT_A_VALID_EMAIL', true),
            )
        ));

        $this->validator()->add('password', array(
            'rule' => array('minLength', 6),
            'message' => __('LC_PASZPORT_PASSWORD_REQUIRED_AND_LENGTH', true),
            //'required' => true
        ));

        $this->validator()->add('repassword', array(
            'rule' => array('confirmPassword'),
            'message' => __('LC_PASZPORT_PASSWORDS_DONT_MATCH', true),
            //'required' => true
        ));

        /*$this->validator()->add('facebook_id', array(
            'unique' => array(
                'rule' => 'isUnique',
                'message' => __('LC_PASZPORT_FACEBOOK_ID_NOT_UNIQUE', true),
            )
        ));*/

        $this->validator()->add('twitter_id', array(
            'unique' => array(
                'rule' => 'isUnique',
                'message' => __('LC_PASZPORT_TWITTER_ID_NOT_UNIQUE', true),
            )
        ));

        $this->validator()->add('photo', array(
            'isValid' => array(
                'rule' => array('isValidMimeType', array('image/png', 'image/jpeg', 'image/jpg', 'image/gif')),
                'message' => __('LC_PASZPORT_AVATAR_BAD_FILE_FORMAT', true),
            ),
        ));
    }
	
	
    /**
     * Check if user is post import and has logged before
     * if he did, that means he misspeled his passowrd | email
     * if he did not that means we need to let him in
     *
     * This is only for sejmometr as it's the only functioning service for now
     *
     * @param $data
     * @param $hashed_pass
     * @return array|bool
     */
    public function checkAndLoginAgainstPostImport($data, $hashed_pass)
    {
        $password = (sha1($data['User']['email'] . SEJMOMETR_USERS_SALT . $data['User']['password']));
        $usr = $this->find('first', array('conditions' => array('User.email' => $data['User']['email'], 'User.password' => $password, 'User.source' => 'sejmometr')));
        if ($usr) {
            $this->id = $usr['User']['id'];
            $this->save(array(
                'password' => $hashed_pass,
                'password_set' => 1,
                'logged_before' => 1,
                'language_id' => 1,
            ));
            return $usr['User'];
        } else {
            return false;
        }
    }

    /**
     * Additional validation for password confirmation
     * @param string $check
     * @return bool
     */
    public function confirmPassword($check)
    {
        if (is_array($check)) {
            $check = array_pop($check);
        }
        if ($check === $this->data['User']['password']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Loguje za pomocą twittera
     * jesli user z danym twitter_id juz istnieje to go poprostu loguje
     * jesli nie to go rejestruje
     *
     * @param array $user_data
     * @return array
     */
    public function twitter($user_data)
    {
        # check if user already exists;
        $exists = $this->find('first', array('conditions' => array('User.twitter_id' => $user_data['id'])));
        if ($exists) {
            return $exists['User'];
        } else {
            $this->Behaviors->load('Upload.Upload', array('photo' => array('path' => '{ROOT}webroot{DS}uploads{DS}{model}{DS}{field}{DS}')));
            $create = array(
                'User' => array(
                    'email' => $user_data['screen_name'] . '@user.twitter.com',
                    'password' => AuthComponent::password(md5($user_data['id'] . rand(0, 999) . $user_data['screen_name'])),
                    'twitter_id' => $user_data['id'],
                    'source' => 'twitter',
                    'photo' => preg_replace('/_normal/', '', $user_data['profile_image_url']),
                    'group_id' => 1,
                    'password_set' => 0,

                ),
            );
            if ($this->save($create)) {
                $this->UserExpand->save(array(
                        'UserExpand' => array(
                            'user_id' => $this->id,
                            'key' => 'username',
                            'value' => $user_data['screen_name'],
                        )
                    )
                );

                return $this->data['User'];
            }
        }

    }


    public function afterFind($results, $primary = false)
    {
        foreach ($results as &$result) {
            if (isset($result['User']['photo']) && $result['User']['photo']) {
                $result['User']['photo'] = $result['User']['photo'];
            }
            if (isset($result['User']['photo_small']) && $result['User']['photo_small']) {
                $result['User']['photo_small'] = $result['User']['photo_small'];
            }
        }
        return $results;
    }

    public function alphaNumericDashUnderscore($check) {
        $value = array_values($check);
        $value = $value[0];
        return preg_match('|^[0-9a-zA-Z_-]*$|', $value);
    }

    public function afterSave($created, $options) {
        if(isset($this->data['User']['id']))
            $this->sync($this->data['User']['id']);
    }

    public function syncAll() {
        $db = ConnectionManager::getDataSource('default');
        $ids = $db->query("SELECT id FROM users");
        foreach($ids as $id)
            $this->sync($id['users']['id']);
    }

    public function sync($id) {
        $db = ConnectionManager::getDataSource('default');
        $es = ConnectionManager::getDataSource('MPSearch');
        $users_dataset_id = 221;

        $user = $db->query("SELECT id, username, created FROM users WHERE id = '" . addslashes($id) . "'");
        $user = $user[0]['users'];

        $objects = $db->query("SELECT id FROM objects WHERE `dataset_id`='$users_dataset_id' AND `object_id`='" . addslashes( $user['id'] ) . "' LIMIT 1");
        $global_id = (int) (@$objects[0]['objects']['id']);

        if(!$global_id) {
            $db->query("INSERT INTO `objects` (`dataset`, `dataset_id`, `object_id`) VALUES ('uzytkownicy', " . $users_dataset_id . ", " . $user['id'] . ")");
            $res = $db->query('select last_insert_id() as id;');
            $global_id = $res[0][0]['id'];
        }

        $es->API->index(array(
            'index' => 'mojepanstwo_v1',
            'id' => $global_id,
            'type' => 'objects',
            'refresh' => true,
            'body' => array(
                'id' => $user['id'],
                'title' => $user['username'],
                'text' => $user['username'],
                'dataset' => 'uzytkownicy',
                'slug' => Inflector::slug($user['username']),
                'data' => array(
                    'uzytkownicy' => array(
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'created' => date('Ymd\THis\Z', strtotime($user['created']))
                    ),
                )
            )
        ));
    }

}

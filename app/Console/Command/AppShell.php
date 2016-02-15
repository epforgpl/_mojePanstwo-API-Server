<?php
/**
 * AppShell file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Shell', 'Console');

/**
 * Application Shell
 *
 * Add your application-wide methods in the class below, your shells
 * will inherit them.
 *
 * @package       app.Console.Command
 */
class AppShell extends Shell
{
	
	public $uses = array('Pisma.Document', 'Dane.ObjectPage');

    public function objectPagesSyncAll() {
        $this->ObjectPage->syncAll();
    }
	
	public function lettersSyncAll() {
        
        $this->Document->syncAll();
        
    }
    
    public function lettersSync() {
        
        $this->Document->sync( $this->args[0] );
        
    }

    public function collectionsSyncAll() {
        $this->loadModel('Collections.Collection');
        $this->Collection->syncAll(((bool) (@$this->args[0])), false);
    }
    
    public function collectionsSync() {
        $this->loadModel('Collections.Collection');
        $this->Collection->syncById(@$this->args[0]);
    }

    public function usersSyncAll() {
        $this->loadModel('Paszport.User');
        $this->User->syncAll();
    }

    public function userSync() {
        $this->loadModel('Paszport.User');
        $this->User->sync((int) $this->args[0]);
    }
    
    public function projectsSyncAll() {

	    $this->loadModel('Dane.OrganizacjeDzialania');

        $projects = $this->OrganizacjeDzialania->find('all', array(
            'fields' => 'id'
        ));

        foreach($projects as $project) {
            $this->out('sync ' . $project['OrganizacjeDzialania']['id']);
            $this->OrganizacjeDzialania->sync($project['OrganizacjeDzialania']['id']);
        }
    }
    
    public function projectsSync() {
		
		if( $id = $this->args[0] ) {
		    $this->loadModel('Dane.OrganizacjeDzialania');
	
	        $this->out('sync ' . $id);
	        $this->OrganizacjeDzialania->sync($id);
        }
        
    }

    public function testWelcomeEmail() {
        if(!isset($this->args[0])) {
            echo "Usage: testWelcomeEmail example@domain.com";
            return 1;
        }

        App::uses('CakeEmail', 'Network/Email');
        $Email = new CakeEmail('noreply');

        $status = $Email->template('Paszport.welcome')
            ->addHeaders(array('X-Mailer' => 'mojePaństwo'))
            ->emailFormat('html')
            ->subject('MojePaństwo.pl - instrukcje i tutoriale')
            ->to($this->args[0], 'Jan Kowalski')
            ->from('asia.przybylska@epf.org.pl', 'Asia Przybylska')
            ->replyTo('asia.przybylska@epf.org.pl', 'Asia Przybylska')
            ->send();
    }

    public function sendWelcomeEmailToUsers() {
        App::uses('CakeEmail', 'Network/Email');
        $Email = new CakeEmail('noreply');

        $this->loadModel('Paszport.User');
        $users = $this->User->find('all');
        foreach($users as $user) {
            $status = $Email->template('Paszport.welcome')
                ->addHeaders(array('X-Mailer' => 'mojePaństwo'))
                ->emailFormat('html')
                ->subject('MojePaństwo.pl - instrukcje i tutoriale')
                ->to($user['User']['email'])
                ->from('asia.przybylska@epf.org.pl', 'Asia Przybylska')
                ->replyTo('asia.przybylska@epf.org.pl', 'Asia Przybylska')
                ->send();
            echo $user['User']['id'] . " : " . ($status ? '1' : '0') . "\n";
        }
    }

    public function sendLetterById() {
        $id = (int) $this->args[0];
        if(!$id)
            throw new Exception('id is required');

        $document = $this->Document->find('first', array(
            'Document.id' => $id
        ));

        $this->Document->send(array(
            'id' => $document['Document']['id'],
            'user_type' => $document['Document']['from_user_type'],
            'name' => $document['Document']['from_user_name'],
            'user_id' => $document['Document']['from_user_id'],
            'email' => $document['Document']['to_email'],
        ));
    }
    
}

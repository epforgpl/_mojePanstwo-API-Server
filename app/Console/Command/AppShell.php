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
        $this->Collection->syncAll(((bool) (@$this->args[0])));
    }

    public function usersSyncAll() {
        $this->loadModel('Paszport.User');
        $this->User->syncAll();
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
    
}

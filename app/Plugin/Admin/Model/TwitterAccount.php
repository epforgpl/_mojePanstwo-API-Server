<?php

/**
 * Created by PhpStorm.
 * User: tomekdrazewski
 * Date: 25/05/15
 * Time: 12:04
 */
class TwitterAccount extends AppModel
{
    
    public function beforeSave($options = array()) {
	    
	    if(
		    isset( $this->data['TwitterAccount'] ) && 
		    isset( $this->data['TwitterAccount']['twitter_name'] ) && 
		    preg_match('/^(https|http)\:\/\/twitter\.com\/(.*?)(\/*)$/i', $this->data['TwitterAccount']['twitter_name'], $match)
	    ) {
		    
		    $this->data['TwitterAccount']['twitter_name'] = $match[2];
		    		    
	    }
	    
    }

}
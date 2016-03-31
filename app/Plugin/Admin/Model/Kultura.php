<?php

class Kultura extends AppModel
{

    public $useTable = false;
    
    public function saveTab($data) {
	    
	    App::import('model','DB');
		$DB = new DB();
	    
	    $tab = array(
		    'name' => trim( $data['title'] ),
		    'saved' => '1',
		    'saved_ts' => 'NOW()',
	    );
	    
	    $DB->updateAssoc('culture_tabs', $tab, $data['tab_id']);
	    
	    
	    foreach( $data['surveys'] as $s ) {
		    
		    $survey = array(
			    'report_id' => $data['report_id'],
			    'file_id' => $data['file_id'],
			    'tab_id' => $data['tab_id'],
			    'title' => trim( $s['title'] ),
			    'html' => trim( $s['html'] ),
		    );
		    
		    $DB->insertIgnoreAssoc('culture_surveys', $survey);
		    
	    }
	    
	    return true;
	    
    }

}
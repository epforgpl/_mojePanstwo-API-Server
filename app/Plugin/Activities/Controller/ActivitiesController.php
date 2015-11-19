<?php

App::uses('AppController', 'Controller');

class ActivitiesController extends AppController {

    public $uses = array('Activities.ActivitiesFiles');

    public function getFiles($activity_id) {
        $files = $this->ActivitiesFiles->find('all', array(
            'conditions' => array(
                'ActivitiesFiles.dzialanie_id' => $activity_id
            ),
        ));

        $this->set('response', $files);
        $this->set('_serialize', 'response');
    }

    public function getFile($activity_id, $file_id) {
        $file = $this->ActivitiesFiles->find('first', array(
            'conditions' => array(
                'ActivitiesFiles.id' => $file_id,
                'ActivitiesFiles.dzialanie_id' => $activity_id
            ),
        ));

        if(!$file)
            throw new NotFoundException;

        App::uses('S3', 'Vendor');
        $S3 = new S3(S3_LOGIN, S3_SECRET, null, S3_ENDPOINT);
        $bucket = 'portal';
        $file = 'activities/files/' . $file['ActivitiesFiles']['filename'];
        $url = $S3->getAuthenticatedURL($bucket, $file, 60);

        if($url) {
            $url = str_replace('s3.amazonaws.com/' . $bucket, $bucket . '.sds.tiktalik.com', $url);
        } else
            throw new NotFoundException;

        $this->set('response', $url);
        $this->set('_serialize', 'response');
    }

}
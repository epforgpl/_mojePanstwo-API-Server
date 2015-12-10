<?php

class ResponsesController extends AppController {

    public $uses = array('Pisma.Response', 'Pisma.ResponseFile');

    public function save($letter_id) {
        try {
            if($this->Auth->user('type') != 'account')
                throw new ForbiddenException;

            $data = $this->request->data;
            $this->Response->save(array(
                'Response' => array(
                    'letter_id' => $letter_id,
                    'user_id' => $this->Auth->user('id'),
                    'title' => $data['name'],
                    'content' => $data['content'],
                    'date' => $data['date'],
                )
            ));

            if(isset($data['files']) && is_array($data['files'])) {
                $letter_response_id = $this->Response->getLastInsertID();
                foreach($data['files'] as $file) {
                    $this->ResponseFile->clear();
                    $this->ResponseFile->save(array(
                        'ResponseFile' => array(
                            'letter_response_id' => $letter_response_id,
                            'filename' => $file['filename'],
                            'src_filename' => $file['src_filename']
                        )
                    ));
                }
            }

            $this->setSerialized('response', true);
        } catch(Exception $e) {
            $this->setSerialized('error', $e->getMessage());
        }
    }

    public function getByLetter($letter_id) {
        $responses = $this->Response->find('all', array(
            /* 'fields' => array('Response.*', 'ResponseFile.*'),
            'joins' => array(
                array(
                    'table' => 'letters_responses_files',
                    'alias' => 'ResponseFile',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'ResponseFile.letter_response_id = Response.id'
                    )
                )
            ), */
            'conditions' => array(
                'Response.letter_id' => $letter_id,
                // 'Response.user_id' => $this->Auth->user('id'),
            ),
        ));

        foreach($responses as $r => $response) {
            $responses[$r]['files'] = $this->ResponseFile->find('all', array(
                'conditions' => array(
                    'ResponseFile.letter_response_id' => $response['Response']['id'],
                ),
            ));
        }

        $this->setSerialized('responses', $responses);
    }

    public function getAttachmentURL($attachment_id) {
        if(!$attachment_id)
            throw new NotFoundException;

        $attachment_id = (int) $attachment_id;

        $filename = $this->Response->query("
            SELECT filename
            FROM letters_responses_files
            INNER JOIN
              letters_responses
                ON letters_responses.id = letters_responses_files.letter_response_id
            WHERE
              letters_responses_files.id = $attachment_id
        ");

        if(!empty($filename[0]['letters_responses_files']['filename']))
            $filename = $filename[0]['letters_responses_files']['filename'];
        else
            throw new NotFoundException;

        App::uses('S3', 'Vendor');
        $S3 = new S3(S3_LOGIN, S3_SECRET, null, S3_ENDPOINT);
        $bucket = 'portal';
        $file = 'letters/responses/' . $filename;
        $url = $S3->getAuthenticatedURL($bucket, $file, 60);

        if( $url ) {
            $url = str_replace('s3.amazonaws.com/' . $bucket, $bucket . '.sds.tiktalik.com', $url);
        } else
            throw new NotFoundException;

        $this->setSerialized('url', $url);
    }

    public function get($letter_id, $response_id)
    {
        $response = $this->Response->find('first', array(
            'conditions' => array(
                'Response.letter_id' => $letter_id,
                'Response.user_id' => $this->Auth->user('id'),
                'Response.id' => $response_id
            )
        ));


        if($response) {

            $response['Response']['files'] = $this->ResponseFile->find('all', array(
                'conditions' => array(
                    'ResponseFile.letter_response_id' => $response_id
                )
            ));

        }

        $this->setSerialized('response', $response);
    }

    public function update($letter_id, $response_id) {
        if($this->Auth->user('type') != 'account')
            throw new ForbiddenException;

        $data = $this->request->data;
        $response = $this->Response->save(array(
            'Response' => array(
                'id' => $response_id,
                'letter_id' => $letter_id,
                'title' => $data['name'],
                'content' => $data['content'],
                'date' => $data['date'],
            )
        ));

        // new files
        if(isset($data['session_files']) && is_array($data['session_files'])) {
            foreach($data['session_files'] as $file) {
                $this->ResponseFile->clear();
                $this->ResponseFile->save(array(
                    'ResponseFile' => array(
                        'letter_response_id' => $response_id,
                        'filename' => $file['filename'],
                        'src_filename' => $file['src_filename']
                    )
                ));
            }
        }

        // remove deleted files from database and s3 cloud
        if(isset($data['files']) && is_array($data['files'])) {
            App::uses('S3', 'Vendor');
            $S3 = new S3(S3_LOGIN, S3_SECRET, null, S3_ENDPOINT);
            foreach($data['files'] as $file) {
                if(isset($file['deleted']) && $file['deleted'] == true) {
                    $this->ResponseFile->clear();
                    $this->ResponseFile->delete(
                        $file['ResponseFile']['id']
                    );

                    $S3->deleteObject('portal', 'letters/responses/' . $file['ResponseFile']['filename']);
                }
            }
        }

        $this->setSerialized('response', $response);
    }

}
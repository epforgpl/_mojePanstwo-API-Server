<?php

class DocsController extends AppController
{
    public $components = array(
        'S3',
    );

    /**
     * @return array
     */
    public function view()
    {

        $document_id = $this->request->params['id'];

        if ($document = $this->Doc->find('first', array(
            'fields' => array('id', 'url', 'filename', 'fileextension', 'pages_count', 'packages_count', 'filesize', 'version'),
            'conditions' => array('id' => $document_id),
        ))
        ) {

            $_serialize = array('Document');
            $document = $document['Doc'];

            $path = 'htmlex/' . $document_id . '/' . $document_id . '_1.html';

            if (
                isset($this->request->query['package']) &&
                ($package = $this->request->query['package'])
            ) {


                if ($package === '*') {


                    $html = '';

                    for ($i = 0; $i < $document['packages_count']; $i++) {

                        $p = $i + 1;
                        if ($s3_response = @$this->S3->getObject('docs.sejmometr.pl', 'htmlex/' . $document_id . '/' . $document_id . '_' . $p . '.html'))
                            $html .= @$s3_response->body;

                    }
					
					$html = str_replace('https://docs.mojepanstwo', 'http://docs.mojepanstwo', $html);
                    $this->set('Package', $html);


                } elseif (
                    (is_numeric($this->request->query['package'])) &&
                    ($package = $this->request->query['package']) &&
                    ($s3_response = @$this->S3->getObject('docs.sejmometr.pl', $path)) &&
                    ($html = @$s3_response->body)
                ) {
	                
					$html = str_replace('https://docs.mojepanstwo', 'http://docs.mojepanstwo', $html);
                    $this->set('Package', $html);
                    
                }

                $_serialize[] = 'Package';

            }


            $this->set(array(
                'Document' => $document,
                '_serialize' => $_serialize,
            ));


        } else {
            throw new NotFoundException();
        }

    }

    /**
     * Pobiera dokument
     */
    public function html()
    {
        $document_id = $this->request->params['id'];
        $package = $this->request->params['package'];
        $html = @$this->S3->getObject('docs.sejmometr.pl', preg_replace('/{id}/', $document_id, '/htmlex/{id}/{id}_' . $package . '.html'));
        $this->set(array(
            'html' => $html->body,
            '_serialize' => array('html'),
        ));
    }

    public function save_document()
    {

        $this->loadModel("Rotatedoc");
        $this->loadModel("Bookmark");


        foreach ($this->request->data['pages'] as $page) {

            if ($this->Rotatedoc->find('first', array('conditions' => array(
                'numer' => $page['numer'],
                'dokument_id' => $page['dokument_id']
            )))
            ) {
                $this->Rotatedoc->updateAll(array('Rotatedoc.rotate' => $page['rotate']), array('Rotatedoc.numer' => $page['numer'], 'Rotatedoc.dokument_id' => $page['dokument_id']));
            } else {
                $this->Rotatedoc->create();
                $this->Rotatedoc->save($page);
            }
        }

        $bookmarks=(array)$this->request->data['bookmarks'];
        foreach ($bookmarks as $bookmark) {
            if ($id = $this->Bookmark->find('first', array('conditions' => array(
                'strona_numer_hex' => $bookmark['strona_numer_hex'],
                'source_dokument_id' => $bookmark['dokument_id']
            ), 'fields' => 'id'))
            ) {
                $bookmark['id'] = $id['Bookmark']['id'];
            }
            $this->Bookmark->create();
            $this->Bookmark->save($bookmark);

        }
        $this->set(array(
            'message' => $this->request->data,
            '_serialize' => array('message'),
        ));
    }

    public function bookmarks()
    {
        $this->loadModel("Bookmark");
        $data = $this->Bookmark->find('all', array(
            'conditions' => array(
                'source_dokument_id' => $this->request->id
            ),
            'fields' => array(
                'id', 'strona_numer_hex', 'tytul', 'opis'
            ),
            'order' => array(
                'strona_start' => 'ASC'
            )));
        $this->set(array(
            'bookmarks' => $data,
            '_serialize' => array('bookmarks')
        ));
    }

    public function doc_id_from_attach()
    {
        $this->loadModel("Bookmark");
        $data = $this->Bookmark->find('first', array(
            'conditions' => array(
                'id' => $this->request->id
            )));

        $id = $data['Bookmark']['dokument_id'];
        $this->set(array(
            'doc_id' => $id,
            '_serialize' => array('doc_id')
        ));
    }

    public function save_budget_spendings()
    {
     /*   $this->loadModel("BudgetSpendings");

        $this->BudgetSpendings->create();

        $data = $this->request->data;
        reset($data);
        $rl_data = key($data);
        $dane = json_decode($rl_data);
        $dane=(array) $dane;
        $toSend = array();
        foreach ($dane as $key => $wiersz) {
            $toSend[$key]=array();
            foreach($wiersz as $k => $v){
                $toSend[$key][$k]=str_replace('_',' ',$v);
            }
        }
        if($this->BudgetSpendings->saveMany($toSend, array('atomic'=>false))){
            $res=true;
        }else{
            $res=false;
        }
*/

        $this->loadModel("BudgetEarnings");

        $this->BudgetEarnings->create();

        $data = $this->request->data;
        $rl_data = $data[0];
        $dane = json_decode($rl_data);
        $dane=(array) $dane;
        $toSend = array();
        foreach ($dane as $key => $wiersz) {
            $toSend[$key]=array();
            foreach($wiersz as $k => $v){
                $toSend[$key][$k]=str_replace('_',' ',$v);
            }
        }
        if($this->BudgetEarnings->saveMany($toSend, array('atomic'=>false))){
            $res=true;
        }else{
            $res=false;
        }

        $this->set(array(
            'dane' => $toSend,
            '_serialize' => array('dane')
        ));
    }
} 
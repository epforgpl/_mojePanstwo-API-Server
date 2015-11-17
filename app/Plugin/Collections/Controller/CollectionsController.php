<?php

App::uses('AppController', 'Controller');

/**
 * @property CollectionObject CollectionObject
 * @property Collection Collection
 */
class CollectionsController extends AppController {

    public $uses = array('Collections.Collection', 'Collections.CollectionObject');
    public $components = array('S3');

    public function beforeFilter() {
        parent::beforeFilter();

        if($this->Auth->user('type') != 'account')
            throw new ForbiddenException;
    }
    
    public function view() {
	    
	    $collection = $this->Collection->load($this->request->params['id'], $this->Auth->user('id'));
	    $this->set('collection', $collection);
	    $this->set('_serialize', 'collection');
	    
    }

    public function get($id) {
        $this->set('response', $this->Collection->find('all', array(
            'conditions' => array(
                'Collection.user_id' => $this->Auth->user('id')
            ),
            'joins' => array(
                array(
                    'table' => 'collection_object',
                    'alias' => 'CollectionObject',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'CollectionObject.collection_id = Collection.id',
                        'CollectionObject.object_id' => (int) $id
                    )
                ),
                array(
                    'table' => 'objects',
                    'alias' => 'Object',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'Object.id = Collection.object_id'
                    )
                )
            ),
            'fields' => array(
                'Collection.*',
                'CollectionObject.*',
                'Object.slug'
            ),
            'order' => array(
                'Collection.created_at' => 'desc',
            )
        )));
        $this->set('_serialize', 'response');
    }

    public function create() {
        $data = array_merge($this->request->data, array(
            'user_id' => $this->Auth->user('id'),
            'created_at' => date('Y-m-d H:i:s', time())
        ));

        $this->Collection->set($data);
        if($this->Collection->validates()) {
            $response = $this->Collection->save(array(
                'Collection' => $data
            ));
        } else {
            $response = $this->Collection->validationErrors;
        }

        $this->set('response', $response);
        $this->set('_serialize', 'response');
    }

    public function publish($id) {
        $this->set('response', $this->Collection->publish($id));
        $this->set('_serialize', 'response');
    }

    public function unpublish($id) {
        $this->set('response', $this->Collection->unpublish($id));
        $this->set('_serialize', 'response');
    }

    public function edit($id) {
        $collection = $this->Collection->find('first', array(
            'conditions' => array(
                'Collection.id' => $id
            )
        ));

        if(!$collection)
            throw new NotFoundException;

        if($collection['Collection']['user_id'] != $this->Auth->user('id'))
            throw new ForbiddenException;

        $data = array_merge($this->request->data, array(
            'user_id' => $this->Auth->user('id'),
            'image' => 0,
            'id' => $id,
        ));

        if(!isset($data['name']))
            $this->Collection->validator()->remove('name');

        $this->Collection->set($data);
        if($this->Collection->validates()) {
            $response = $this->Collection->save(array(
                'Collection' => $data
            ));
        } else {
            $response = $this->Collection->validationErrors;
        }

        $this->set('response', $response);
        $this->set('_serialize', 'response');
    }

    public function addObjectData() {
        $data = $this->request->data;
        $this->addObject(
            $data['id'],
            $data['object_id'],
            $data['note']
        );
    }

    public function addObject($id, $object_id, $note = false) {
        $collection = $this->Collection->find('first', array(
            'conditions' => array(
                'Collection.id' => $id
            )
        ));

        if(!$collection)
            throw new NotFoundException;

        if($collection['Collection']['user_id'] != $this->Auth->user('id'))
            throw new ForbiddenException;
		
		$collection_object_id = $this->CollectionObject->find('first', array(
			'fields' => array('CollectionObject.id'),
			'conditions' => array(
				'CollectionObject.collection_id' => $id,
				'CollectionObject.object_id' => $object_id,
			),
		));
		
		$data = array(
            'CollectionObject' => array(
                'collection_id' => (int) $id,
                'object_id' => (int) $object_id
            )
        );

        if($note)
            $data['CollectionObject']['note'] = $note;
		
		if( $collection_object_id ) {

			$data['CollectionObject']['id'] = $collection_object_id['CollectionObject']['id'];
            $this->CollectionObject->save($data);
			$this->CollectionObject->syncByData($data);
			$response = true;
			
		} else {
		
	        $response = $this->CollectionObject->save($data);
        
        }
        
        $this->set('response', $response);
        $this->set('_serialize', 'response');
    }

    public function removeObject($id, $object_id) {
        $collection = $this->Collection->find('first', array(
            'conditions' => array(
                'Collection.id' => $id
            )
        ));

        if(!$collection)
            throw new NotFoundException;

        if($collection['Collection']['user_id'] != $this->Auth->user('id'))
            throw new ForbiddenException;

        $this->set('response', $this->CollectionObject->query('DELETE FROM collection_object WHERE collection_id = ' . (int) $id . ' AND object_id = '. (int) $object_id));
        $this->set('_serialize', 'response');
    }

    public function removeObjects($id) {
        $collection = $this->Collection->find('first', array(
            'conditions' => array(
                'Collection.id' => $id
            )
        ));

        if(!$collection)
            throw new NotFoundException;

        if($collection['Collection']['user_id'] != $this->Auth->user('id'))
            throw new ForbiddenException;

        $ids = array();
        foreach(((array) @$this->request->data['ids']) as $id) {
            $ids[] = (int) $id;
        }

        $this->set('response', $this->CollectionObject->query('DELETE FROM collection_object WHERE collection_id = ' . (int) $id . ' AND object_id IN (' . implode(",", $ids) . ')'));
        $this->set('_serialize', 'response');
    }

    public function delete($id) {
        $collection = $this->Collection->find('first', array(
            'conditions' => array(
                'Collection.id' => $id
            )
        ));

        if(!$collection)
            throw new NotFoundException;

        if($collection['Collection']['user_id'] != $this->Auth->user('id'))
            throw new ForbiddenException;

        $id = (int) $collection['Collection']['id'];
        $res = $this->Collection->query("SELECT id FROM objects WHERE `dataset_id`='210' AND `object_id`='" . addslashes( $id ) . "' LIMIT 1");
        $this->Collection->global_id = (int)(@$res[0]['objects']['id']);
        $this->Collection->collection_id = (int)$id;
		
        $this->set('response', $this->Collection->delete($collection['Collection']['id']));
        $this->set('_serialize', 'response');
    }
    
    public function editObject($collection_id, $object_id) {
	    
	    $response = $this->Collection->editObject($collection_id, $object_id, $this->request->data);
	    
	    $this->set('response', $response);
        $this->set('_serialize', 'response');
	    
    }

}
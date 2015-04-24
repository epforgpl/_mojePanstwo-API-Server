<?
		
	class SubscriptionsController extends AppController {
	
		public $uses = array('Dane.Subscription');
	    public $components = array('RequestHandler');
	
	    public function index() {
	        $Subscriptions = $this->Subscription->find('all');
	        $this->set(array(
	            'Subscriptions' => $Subscriptions,
	            '_serialize' => array('Subscriptions')
	        ));
	    }
	
	    public function view($id) {
	        $Subscription = $this->Subscription->findById($id);
	        $this->set(array(
	            'Subscription' => $Subscription,
	            '_serialize' => array('Subscription')
	        ));
	    }
	
	    public function add() {
		    
		    $this->loadModel('Dane.Subscription');
	        $this->Subscription->create();

			$_serialize = array('message');
	        	        
	        $data = array(
		        'dataset' => $this->request->data['dataset'],
		        'object_id' => $this->request->data['object_id'],
	        );
	        
	        if( isset( $this->request->data['q'] ) && $this->request->data['q'] )
	        	$data['q'] = $this->request->data['q'];
	        	
	        if( isset( $this->request->data['channel'] ) && $this->request->data['channel'] )
	        	$data['channel'] = $this->request->data['channel'];
	        	
	        if( isset( $this->request->data['conditions'] ) && $this->request->data['conditions'] )
	        	$data['conditions'] = json_encode( $this->request->data['conditions'] );
	        
	        $data['hash'] = md5(json_encode($data));
	        	        
	        $data = array_merge($data, array(
		        'user_type' => $this->Auth->user('type'),
		        'user_id' => $this->Auth->user('id'),
			   	'cts' => date('Y-m-d h:i:j'),			   	
	        ));
	        
	        
	        if( $sub = $this->Subscription->find('first', array(
		        'conditions' => array(
			        'user_type' => $data['user_type'],
			        'user_id' => $data['user_id'],
			        'hash' => $data['hash'],
		        ),
	        )) ) {
		        	
		        	$url = $sub['Subscription']['url'];
		        	$this->set('url', $url);
		        	$_serialize[] = 'url';
		        	$message = 'Already Exists';
		        
	        } else {       
	
	        		        			        
		        if ($this->Subscription->save($data)) {
		        	
		        	$data['id'] = $this->Subscription->getInsertID();
		        	$add_data = $this->Subscription->generateData($data);		        	
		        	$data = array_merge($data, $add_data);
		        	$parent_id = $this->Subscription->index($data);
		        	
		        	$this->Subscription->save(array(
			        	'id' => $data['id'],
			        	'url' => $add_data['url'],
			        	'title' => $add_data['title'],
			        	'autotitle' => $add_data['title'],
			        	'parent_id' => $parent_id,
		        	));
		        	
		        	$this->set('url', $add_data['url']);
		        	$_serialize[] = 'url';
		        	
		            $message = 'Saved';
		        
		        } else {
		            $message = 'Error';
		        }
		        
		    }
		    
	        $this->set(array(
	            'message' => $message,
	            '_serialize' => $_serialize,
	        ));
	    }
	
	    public function edit($id) {
	        $this->Subscription->id = $id;
	        if ($this->Subscription->save($this->request->data)) {
	            $message = 'Saved';
	        } else {
	            $message = 'Error';
	        }
	        $this->set(array(
	            'message' => $message,
	            '_serialize' => array('message')
	        ));
	    }
	
	    public function delete($id) {
	        
	        if( $sub = $this->Subscription->find('first', array(
		        'conditions' => array(
			        'Subscription.id' => $id,
			        'Subscription.user_type' => $this->Auth->user('type'),
			        'Subscription.user_id' => $this->Auth->user('id'),
		        ),
	        )) ) {
	        	
	        	$this->Subscription->data['id'] = $id;
		        if ($this->Subscription->delete($id)) {
		            $message = 'Deleted';
		        } else {
		            $message = 'Error';
		        }
		        $this->set(array(
		            'message' => $message,
		            '_serialize' => array('message')
		        ));
	        
	        } else {
		        throw new NotFoundException();
	        }
	    }
	    
	    public function transfer_anonymous() {
				
			$status = false;
			
			if(
				( $user = $this->Auth->user() ) && 
				isset($this->request->query['anonymous_user_id']) && 
				$this->request->query['anonymous_user_id']
			) {
				
				$status = $this->Subscription->transfer_anonymous($this->request->query['anonymous_user_id'], $this->Auth->user('id'));
				
			}
			
			$this->setSerialized('status', $status);
			
		}
	}
	
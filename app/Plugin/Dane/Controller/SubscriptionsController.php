<?
		
	class SubscriptionsController extends AppController {
	
		public $uses = array('Dane.Subscription', 'Dane.SubscriptionChannel');
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
		    		    
		    $data = array(
		        'dataset' => $this->request->data['dataset'],
		        'object_id' => $this->request->data['object_id'],
		        'user_type' => $this->Auth->user('type'),
		        'user_id' => $this->Auth->user('id'),
			   	'channel' => isset( $this->request->data['channel'] ) ? $this->request->data['channel'] : array(),
			   	'qs' => isset( $this->request->data['qs'] ) ? $this->request->data['qs'] : array(),
	        );
	        	        	            
		    $message = $this->Subscription->add($data);
		    		    
	        $this->set(array(
	            'message' => $message,
	            '_serialize' => 'message',
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
	
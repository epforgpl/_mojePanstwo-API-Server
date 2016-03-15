<?php

class ModalsController extends PaszportAppController
{

    public $uses = array('Paszport.Modal');

    /**
     * Zwraca liste nazw modali które chcemy wyświetlić użytkownikowi
     * wykluczając te już wyświetlone oraz te które nie spełniają warunków
     * dla danego użytkownika
     */
    public function get()
    {
        $_modals = array(
            'ngo1' => array(
                'conditions' => function($user, $db) {
                    $results = $db->query('
                        SELECT COUNT(*)
                        FROM `objects-users`
                        WHERE `user_id` = ?
                    ', array(
                        (int) $user['id']
                    ));

                    $userObjectsCount = (int) $results[0][0]['COUNT(*)'];

                    $results = $db->query('
                        SELECT COUNT(*)
                        FROM `pages_requests`
                        WHERE `user_id` = ? AND `status` = 0
                    ', array(
                        (int) $user['id']
                    ));

                    $pendingPageRequestsCount = (int) $results[0][0]['COUNT(*)'];

                    return (
                        $userObjectsCount == 0 &&
                        $pendingPageRequestsCount == 0
                    );
                }
            )
        );

        $rows = $this->Modal->find('all', array(
            'fields' => 'modal',
            'conditions' => array(
                'user_id' => $this->Auth->user('id')
            )
        ));

        $displayedModals = array();
        if(count($rows) > 0) {
            $displayedModals = array_column(array_column($rows, 'Modal'), 'modal');
        }

        $modals = array();
        foreach($_modals as $name => $options)
        {
            // czy wyświetlony?
            if(in_array($name, $displayedModals))
                continue;

            // czy spełnia warunki dla danego użytkownika?
            if(!call_user_func($options['conditions'], $this->Auth->user(), $this->Modal))
                continue;

            $modals[] = $name;
        }

        $this->setSerialized(array(
            'modals' => $modals
        ));
    }

    public function add() {
        $this->Modal->clear();
        $this->setSerialized(array(
            'modal' => $this->Modal->save(array(
                'Modal' => array(
                    'modal'      => $this->request->data['modal'],
                    'user_id'    => $this->Auth->user('id'),
                    'created_at' => date('Y-m-d H:i:s')
                )))
            )
        );
    }

}
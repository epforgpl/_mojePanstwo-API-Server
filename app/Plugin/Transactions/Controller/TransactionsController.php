<?php

/**
 * @property Transaction Transaction
 */
class TransactionsController extends AppController {

    public $components = array('RequestHandler');
    public $uses = array('Transactions.Transaction');

    public function save() {
        if(isset($this->request->data['tr_id'])) {
            $this->setTransactionStatus($this->request->data);
        } elseif(isset($this->request->data['amount'])) {
            if($this->Auth->user('type') == 'account')
                $this->request->data['user_id'] = $this->Auth->user('id');

            $this->createTransaction($this->request->data);
        } else
            throw new BadRequestException;
    }

    public function get($id) {
        $this->setSerialized('transaction', $this->Transaction->findById($id));
    }

    private function setTransactionStatus($data) {
        $md5sum = md5(TPAY_SELLER_ID . $data['tr_id'] . $data['tr_amount'] . '' . TPAY_SELLER_CONFIRMATION_CODE);
        if($md5sum != $data['md5sum'])
            throw new UnauthorizedException;

        $transaction = $this->Transaction->findById((int) @$data['tr_desc']);
        if(!$transaction)
            throw new NotFoundException;

        $map = array(
            'tr_id' => 'res_id',
            'tr_date' => 'res_date',
            'tr_amount' => 'res_amount',
            'tr_paid' => 'res_paid',
            'tr_desc' => 'res_desc',
            'tr_status' => 'res_status',
            'tr_error' => 'res_error',
            'tr_email' => 'res_email',
            'test_mode' => 'res_test_mode',
            'wallet' => 'res_wallet'
        );

        $update = array(
            'id' => $transaction['Transaction']['id'],
            'res_received_at' => date('Y-m-d H:i:s')
        );

        foreach($map as $from => $to)
            $update[$to] = $data[$from];

        $this->Transaction->clear();
        $this->setSerialized(array(
            'transaction' => $this->Transaction->save($update)
        ));
    }

    private function createTransaction($data) {
        try {
            $this->Transaction->set($data);
            if($this->Transaction->validates(array(
                'fieldList' => array(
                    'krs_pozycje_id', 'amount',
                    'email')))) {
                $transaction = $this->Transaction->save($data);
                if(!$transaction)
                    throw new Exception('Wystąpił błąd podczas zapisywania danych');

                $query = http_build_query(array(
                    'id' => TPAY_SELLER_ID,
                    'kwota' => $transaction['Transaction']['amount'],
                    'opis' => $transaction['Transaction']['id'],
                    'md5sum' => md5(
                        TPAY_SELLER_ID .
                        $transaction['Transaction']['amount'] .
                        '' .
                        TPAY_SELLER_CONFIRMATION_CODE
                    ),
                    'wyn_url' => PORTAL_PRODUCTION_URL . 'transactions/transactions/getTransactionStatus',
                    'pow_url' => PORTAL_PRODUCTION_URL . 'transactions/transactions/results/' . $transaction['Transaction']['id'],
                    'email' => $transaction['Transaction']['email'],
                    'nazwisko' => $transaction['Transaction']['firstname'] . ' ' . $transaction['Transaction']['surname']
                ));

                $this->setSerialized(array(
                    'redirect' => 'https://secure.tpay.com?' . $query
                ));
            } else {
                $errors = array_values($this->Transaction->validationErrors);
                throw new Exception($errors[0][0]);
            }
        } catch(Exception $e) {
            $this->setSerialized(array(
                'error' => $e->getMessage()
            ));
        }
    }

}
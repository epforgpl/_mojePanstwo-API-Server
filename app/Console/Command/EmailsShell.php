<?php

App::uses('CakeEmail', 'Network/Email');

/**
 * @property User User
 */
class EmailsShell extends AppShell {

    public $uses = array('User');

    private static $interval = 90;
    private static $maxCreatedAt = '2016-01-07 00:00:00';

    public function sendPromoEmailToNGO() {
        $emailService = new CakeEmail('ngo');

        $row = $this->User->query("
              SELECT
                  krs_pozycje.id,
                  krs_pozycje.forma_prawna_id,
                  krs_pozycje.email,
                  krs_pozycje.nazwa
                FROM krs_pozycje
                WHERE krs_pozycje.forma_prawna_id IN(1, 15) AND krs_pozycje.www != \"\"
                AND NOT EXISTS (SELECT 1 FROM ngo_email_campaign c WHERE c.krs_pozycje_id = krs_pozycje.id)
                ORDER BY krs_pozycje.id DESC
                LIMIT 1
            ");

        if(!$row)
            return 1;

        $status = 1;

        if(filter_var($row[0]['krs_pozycje']['email'], FILTER_VALIDATE_EMAIL) === false) {
            $status = 3;
        } else {
            try {
                $emailService->template('ngo-promo')
                    ->addHeaders(array('X-Mailer' => 'mojePaństwo'))
                    ->emailFormat('html')
                    ->attachments(array(
                        array(
                            'file' => ROOT . '/app/webroot/img/ngo_email_promo.png',
                            'mimetype' => 'image/png',
                            'contentId' => '1'
                        ),
                    ))
                    ->subject('Uzupełnij konto swojej organizacji na mojepanstwo.pl!')
                    ->to('marek.bielecki@epf.org.pl')
                    ->from('asia.przybylska@epf.org.pl', 'Asia Przybylska')
                    ->replyTo('asia.przybylska@epf.org.pl', 'Asia Przybylska')
                    ->send();
            } catch (SocketException $e) {
                $this->out($e->getMessage());
                $status = 2;
            }
        }

        $this->User->query("
                INSERT INTO ngo_email_campaign VALUES
                ({$row[0]['krs_pozycje']['id']}, NOW(), {$status})
            ");
    }

    public function sendWelcomeEmailToUsers() {
        $emailService = new CakeEmail('pisma');

        $user = $this->User->query("
              SELECT id, email
              FROM users
              WHERE
                email_status = 0 AND
                created < '" . self::$maxCreatedAt . "' AND
                email != ''
              LIMIT 1
            ");

        if(!$user)
            return 1;

        try {
            $emailService->template('Paszport.welcome')
                ->addHeaders(array('X-Mailer' => 'mojePaństwo'))
                ->emailFormat('html')
                ->subject('MojePaństwo.pl - instrukcje i tutoriale')
                ->to($user[0]['users']['email'])
                ->from('asia.przybylska@epf.org.pl', 'Asia Przybylska')
                ->replyTo('asia.przybylska@epf.org.pl', 'Asia Przybylska')
                ->send();

            $status = 1;
        } catch (SocketException $e) {
            $this->out($e->getMessage());
            $status = 2;
        }

        $this->User->query("
              UPDATE users
              SET
                email_status = {$status},
                email_status_ts = NOW()
              WHERE
                id = ". $user[0]['users']['id'] ."
            ");
    }

}
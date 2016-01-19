<?php

App::uses('CakeEmail', 'Network/Email');

/**
 * @property User User
 */
class EmailsShell extends AppShell {

    public $uses = array('User');

    private static $interval = 90;
    private static $maxCreatedAt = '2016-01-07 00:00:00';

    public function sendWelcomeEmailToUsers()
    {
        $emailService = new CakeEmail('pisma');

        while(true) {
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

            sleep(self::$interval);
        }
    }

}
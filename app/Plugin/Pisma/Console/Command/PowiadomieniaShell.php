<?php


class PowiadomieniaShell extends Shell
{
    public $uses = array('Pisma.Document');

    public function send() {
        $db = ConnectionManager::getDataSource('default');
        $options = array(
            'final' => array(
                'data' => $db->query("
                    SELECT
                        `pisma_documents`.`id`,
                        `users`.`email`
                    FROM `pisma_documents`
                    RIGHT JOIN `users` ON `users`.`id` = `pisma_documents`.`from_user_id`
                    WHERE
                      `pisma_documents`.`sent` = '1' AND
                      `pisma_documents`.`from_user_type` = 'account' AND
                      `pisma_documents`.`powiadomienie_termin` = 0 AND
                      `pisma_documents`.`name` = 'Wniosek o udostępnienie informacji publicznej' AND
                      TIMESTAMPDIFF(DAY, `pisma_documents`.`sent_at`, NOW()) > 14 AND
                      TIMESTAMPDIFF(DAY, `pisma_documents`.`sent_at`, NOW()) < 30
                "),
                'flag' => 'powiadomienie_termin',
                'timestamp' => 'powiadomienie_termin_ts'
            ),
            '3dni' => array(
                'data' => $db->query("
                    SELECT
                        `pisma_documents`.`id`,
                        `users`.`email`
                    FROM `pisma_documents`
                    RIGHT JOIN `users` ON `users`.`id` = `pisma_documents`.`from_user_id`
                    WHERE
                      `pisma_documents`.`sent` = '1' AND
                      `pisma_documents`.`from_user_type` = 'account' AND
                      `pisma_documents`.`powiadomienie_termin` = 0 AND
                      `pisma_documents`.`powiadomienie_zbliza` = 0 AND
                      `pisma_documents`.`name` = 'Wniosek o udostępnienie informacji publicznej' AND
                      TIMESTAMPDIFF(DAY, `pisma_documents`.`sent_at`, NOW()) < 14 AND
                      TIMESTAMPDIFF(DAY, `pisma_documents`.`sent_at`, NOW()) > 11
                "),
                'flag' => 'powiadomienie_zbliza',
                'timestamp' => 'powiadomienie_zbliza_ts'
            )
        );

        foreach($options as $type => $opt)
        {
            foreach($opt['data'] as $row) {
                $status = $this->Document->notify($row['users']['email'], $type);
                $db->query("UPDATE `pisma_documents` SET `{$opt['flag']}` = ?, `{$opt['timestamp']}` = NOW() WHERE `id` = ?", array(
                    $status ? 1 : 2,
                    $row['pisma_documents']['id']
                ));
            }
        }
    }
}
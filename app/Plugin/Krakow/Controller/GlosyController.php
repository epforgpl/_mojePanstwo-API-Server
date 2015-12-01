<?php

class GlosyController extends AppController
{

    public $uses = array('Krakow.UserVotes');
    public $components = array('RequestHandler');

    public function getRadniByUserVotes() {
        if(!is_array($this->request->data))
            throw new BadRequestException;

        $voteDict = array(
            '0' => 3, // wstrzymuje sie
            '-1' => 2, // przeciw
            '1' => 1 // za
        );

        $data = array();
        foreach($this->request->data as $v) {
            if(isset($v['vote']) && isset($v['uchwala_id']) && isset($voteDict[$v['vote']])) {
                $data[] = array(
                    'uchwala_id' => $v['uchwala_id'],
                    'glos_id' => $voteDict[$v['vote']]
                );
            }
        }

        if(count($data) < 10)
            throw new BadRequestException;

        App::import('model','DB');
        $this->DB = new DB();

        $query = "

            SELECT
              prawo_lokalne_druki.uchwala_id,
              prawo_lokalne_druki.druk_id,
              pl_gminy_krakow_glosowania_bip.id,
              pl_gminy_krakow_posiedzenia_punkty_portal.glosowanie_id,
              GROUP_CONCAT(
                CONCAT(
                  pl_gminy_krakow_glosowania_glosy_bip.radny_id,
                  ':',
                  pl_gminy_krakow_glosowania_glosy_bip.glos_id
                )
                SEPARATOR ','
              ) as votes
            FROM
              prawo_lokalne_druki
            JOIN pl_gminy_krakow_glosowania_bip ON
                prawo_lokalne_druki.druk_id = pl_gminy_krakow_glosowania_bip.druk_id
            JOIN pl_gminy_krakow_posiedzenia_punkty_portal ON
              pl_gminy_krakow_posiedzenia_punkty_portal.id = pl_gminy_krakow_glosowania_bip.punkt_portal_id
            JOIN pl_gminy_krakow_posiedzenia ON
              pl_gminy_krakow_posiedzenia_punkty_portal.posiedzenie_id = pl_gminy_krakow_posiedzenia.id
            JOIN pl_gminy_krakow_glosowania_glosy_bip ON
              pl_gminy_krakow_glosowania_glosy_bip.glosowanie_id = pl_gminy_krakow_posiedzenia_punkty_portal.glosowanie_id
            WHERE
              prawo_lokalne_druki.uchwala_id IN(". implode(',', array_column($data, 'uchwala_id')) .")
            GROUP BY
              pl_gminy_krakow_glosowania_bip.id
            ORDER BY
              pl_gminy_krakow_glosowania_bip.ord DESC,
              pl_gminy_krakow_posiedzenia_punkty_portal.ord DESC,
              pl_gminy_krakow_glosowania_bip.ord DESC
            LIMIT 10
        ";

        $results = $this->DB->selectAssocs($query);
        $ranking = array();
        foreach($results as $row) {
            $user_glos_id = false;
            foreach($data as $d) {
                if($d['uchwala_id'] == $row['uchwala_id']) {
                    $user_glos_id = $d['glos_id'];
                }
            }

            if($user_glos_id === false)
                continue;

            foreach(explode(',', $row['votes']) as $vote) {
                list($radny_id, $glos_id) = explode(':', $vote);
                if(!$radny_id)
                    continue;
                if(!isset($ranking[$radny_id]))
                    $ranking[$radny_id] = 0;
                if($user_glos_id == $glos_id)
                    $ranking[$radny_id]++;
            }
        }

        $this->setSerialized('response', $ranking);
    }

    public function save($druk_id) {
        if($this->Auth->user('type') != 'account')
            throw new ForbiddenException();

        $this->setSerialized('response', $this->UserVotes->vote(
            (int) $this->Auth->user('id'),
            (int) $druk_id,
            (int) $this->data['vote']
        ));
    }

    public function view($druk_id) {
        $this->setSerialized(
            'response',
            $this->UserVotes->getVotes(
                $druk_id,
                (int) $this->Auth->user('id')
            )
        );
    }

}
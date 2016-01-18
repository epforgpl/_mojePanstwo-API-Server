<?php

class RadniRankingController extends AppController
{
    public $components = array('RequestHandler');

    public function get() {
        $type = @$this->request->query['type'];
        $results = array();
        App::import('model','DB');
        $db = new DB();

        if($type == 'activity') {
            $month = @$this->request->query['m'];
            if(!$month) {
                $rows = $db->selectAssocs('
                    SELECT
                        `pl_gminy_radni_krakow_ranking`.`radny_id`,
                        `pl_gminy_radni_krakow_ranking`.`typ`,
                        SUM(`pl_gminy_radni_krakow_ranking`.`points`) as `points`,
                        `pl_gminy_radni`.`nazwa`,
                        `pl_gminy_radni`.`avatar_id`
                    FROM `pl_gminy_radni_krakow`
                    JOIN
                      `pl_gminy_radni_krakow_ranking` ON
                        `pl_gminy_radni_krakow_ranking`.`radny_id` = `pl_gminy_radni_krakow`.`id`
                    JOIN
                      `pl_gminy_radni` ON
                        `pl_gminy_radni`.`id` = `pl_gminy_radni_krakow`.`id`
                    GROUP BY
                      `pl_gminy_radni_krakow_ranking`.`radny_id`,
                      `pl_gminy_radni_krakow_ranking`.`typ`
                ');

                $r = array();
                foreach($rows as $row) {
                    if(!isset($r[$row['radny_id']])) {
                        $r[$row['radny_id']] = array(
                            'id' => $row['radny_id'],
                            'nazwa' => $row['nazwa'],
                            'avatar_id' => $row['avatar_id'],
                            'types' => array()
                        );
                    }

                    $r[$row['radny_id']]['types'][
                        $row['typ']
                    ] = $row['points'];
                }

                foreach($r as $row)
                    $results[] = $row;

            } else {

                $rows = $db->selectAssocs('
                    SELECT
                        `pl_gminy_radni_krakow_ranking`.`radny_id`,
                        `pl_gminy_radni_krakow_ranking`.`typ`,
                        SUM(`pl_gminy_radni_krakow_ranking`.`points`) as `points`,
                        `pl_gminy_radni`.`nazwa`,
                        `pl_gminy_radni`.`avatar_id`
                    FROM `pl_gminy_radni_krakow`
                    JOIN
                      `pl_gminy_radni_krakow_ranking` ON
                        `pl_gminy_radni_krakow_ranking`.`radny_id` = `pl_gminy_radni_krakow`.`id`
                    JOIN
                      `pl_gminy_radni` ON
                        `pl_gminy_radni`.`id` = `pl_gminy_radni_krakow`.`id`
                    WHERE `pl_gminy_radni_krakow_ranking`.`month` = "'. addslashes($month) .'"
                    GROUP BY
                      `pl_gminy_radni_krakow_ranking`.`radny_id`,
                      `pl_gminy_radni_krakow_ranking`.`typ`
                ');

                $r = array();
                foreach($rows as $row) {
                    if(!isset($r[$row['radny_id']])) {
                        $r[$row['radny_id']] = array(
                            'id' => $row['radny_id'],
                            'nazwa' => $row['nazwa'],
                            'avatar_id' => $row['avatar_id'],
                            'types' => array()
                        );
                    }

                    $r[$row['radny_id']]['types'][
                    $row['typ']
                    ] = $row['points'];
                }

                foreach($r as $row)
                    $results[] = $row;

            }

        } elseif($type == 'openness') {

            $openness_points = array(
                'email2' => 1,
                'tel' => 5,
                'blog' => 2,
                'www' => 2,
                'fb' => 2,
                'twitter' => 2
            );

            $r = array();
            $rows = $db->selectAssocs('
                SELECT
                    `pl_gminy_radni`.`id`,
                    `pl_gminy_radni`.`nazwa`,
                    `pl_gminy_radni`.`avatar_id`,
                    `pl_gminy_radni`.`ranking_otwartosc_punkty`,
                    `pl_gminy_radni`.`email2`,
                    `pl_gminy_radni`.`tel`,
                    `pl_gminy_radni`.`blog`,
                    `pl_gminy_radni`.`www`,
                    `pl_gminy_radni`.`fb`,
                    `pl_gminy_radni`.`twitter`
                FROM `pl_gminy_radni_krakow`
                JOIN
                  `pl_gminy_radni` ON
                    `pl_gminy_radni`.`id` = `pl_gminy_radni_krakow`.`id`
                ORDER BY `pl_gminy_radni`.`ranking_otwartosc_punkty`
            ');

            foreach($rows as $row) {

                $types = array(
                    '*' => $row['ranking_otwartosc_punkty']
                );

                foreach($openness_points as $k => $p) {
                    if (!in_array($row[$k], array('', 'nie'))) {
                        $types[$k] = $p;
                    }
                }

                $r[] = array(
                    'id' => $row['id'],
                    'nazwa' => $row['nazwa'],
                    'avatar_id' => $row['avatar_id'],
                    'types' => $types
                );
            }

            $results = $r;

        } else {
            throw new BadRequestException;
        }

        $this->set('results', $results);
        $this->set('_serialize', 'results');
    }

}

<?php

return $this->DB->selectAssocs("
	SELECT
        `krs_wspolnicy`.`id`,
        `krs_wspolnicy`.`pozycja_id`,
        `krs_wspolnicy`.`udzialy_str`,
        `krs_pozycje`.`nazwa`,
        `krs_wspolnicy`.`udzialy_status`,
        `krs_wspolnicy`.`udzialy_liczba`,
        `krs_wspolnicy`.`udzialy_wartosc_jedn`,
        `krs_wspolnicy`.`udzialy_wartosc`,
        `objects`.`slug`
	FROM
		`krs_wspolnicy`
	LEFT JOIN
		`krs_pozycje`
			ON `krs_pozycje`.`id` = `krs_wspolnicy`.`pozycja_id`
    LEFT JOIN
        `objects`
            ON
              `objects`.`object_id` = `krs_wspolnicy`.`pozycja_id` AND
              `objects`.`dataset` = 'krs_podmioty'
	WHERE
		`krs_wspolnicy`.`gmina_id` = '" . addslashes($id) . "' AND
		`krs_wspolnicy`.`deleted` = '0'
	ORDER BY
		`krs_wspolnicy`.`ord` ASC
	LIMIT 100
");

<?php

$sql = <<<SQL
SELECT
    l.iso2cc AS country_code,
    e.nazwa as 'delegacja',
    l.kraj,
    e.id,
    e.numer as 'wniosek_nr',
    e.liczba_dni,
    e.data_start AS od,
    e.data_stop AS do,
    w.koszt_transport,
    w.koszt_dieta,
    w.koszt_hotel,
    w.koszt_dojazd,
    w.koszt_ubezpieczenie,
    w.koszt_zaliczki,
    w.koszt AS koszt_suma

FROM `poslowie_wyjazdy8` w
INNER JOIN `poslowie_wyjazdy_wydarzenia8` e ON (w.wydarzenie_id = e.id)
INNER JOIN `poslowie_wyjazdy_wydarzenia_lokalizacje8` el ON (w.wydarzenie_id = el.wydarzenie_id)
INNER JOIN `poslowie_wyjazdy_lokalizacje8` l ON (l.id = el.lokalizacja_id)
WHERE w.posel_id = $id AND e.deleted = '0' AND w.deleted = '0' 
GROUP BY e.`id` 
ORDER BY e.data_start DESC, e.id, w.id
SQL;

$rows = $this->DB->selectAssocs($sql);

if (!$rows) {
   return new object();
}

return $rows;
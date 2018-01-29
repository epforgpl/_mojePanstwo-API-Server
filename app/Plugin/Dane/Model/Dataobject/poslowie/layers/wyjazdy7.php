<?php

$sql = <<<SQL
SELECT
    l.iso2cc AS country_code,
    e.delegacja,
    kraj,
    miasto,
    e.id,
    e.wniosek_nr,
    e.liczba_dni,
    e.date_start AS od,
    e.date_stop AS do,
    w.koszt_transport,
    w.koszt_dieta,
    w.koszt_hotel,
    w.koszt_dojazd,
    w.koszt_ubezpieczenie,
    w.koszt_fundusz,
    w.koszt_kurs,
    w.koszt_zaliczki,
    w.koszt AS koszt_suma

FROM poslowie_wyjazdy w
INNER JOIN poslowie_wyjazdy_wydarzenia e ON (w.wydarzenie_id = e.id)
INNER JOIN poslowie_wyjazdy_lokalizacje l ON (l.lokalizacja = e.lokalizacja)
WHERE w.posel_id = $id AND e.deleted = '0' AND w.deleted = '0'
ORDER BY e.date_start DESC, e.id, w.id
SQL;

$rows = $this->DB->selectAssocs($sql);

if (!$rows) {
   return new object();
}

return $rows;
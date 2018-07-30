<?

return $this->DB->selectAssocs("
    SELECT id, complete, status, complete_ts
    FROM krs_files
    WHERE krs_pozycje_id = ".( (int) $id )." AND `status` = 'OK' AND `content`='PDF'
    ORDER BY complete ASC, complete_ts DESC 
    LIMIT 10
");
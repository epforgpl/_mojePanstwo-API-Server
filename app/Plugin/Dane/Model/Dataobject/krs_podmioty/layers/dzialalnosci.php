<?

/*
$data = $this->DB->query("SELECT `pkd2007`.`id`, `pkd2007`.`nazwa` 
		FROM `krs_dzialalnosci` 
		JOIN `krs_pozycje-dzialalnosci` 
		ON `krs_pozycje-dzialalnosci`.`dzialalnosc_id` = `krs_dzialalnosci`.`id`
		AND `krs_pozycje-dzialalnosci`.krs_id='" . addslashes($id) . "'
		AND `krs_pozycje-dzialalnosci`.`deleted`='0' 
		JOIN `pkd2007` ON `krs_dzialalnosci`.`pkd2007_id` = `pkd2007`.`id` 
		ORDER BY `krs_dzialalnosci`.`id` ASC");

return $data;

$output = array();
foreach ($data as $d)
    $output[] = $d['krs_dzialalnosci'];

return $output;
*/

$data = $this->DB->query("SELECT `krs_dzialalnosci`.`id`, `krs_dzialalnosci`.`str`, `krs_pozycje-dzialalnosci`.`przewazajaca` 
		FROM `krs_dzialalnosci` 
		JOIN `krs_pozycje-dzialalnosci` 
		ON `krs_pozycje-dzialalnosci`.`dzialalnosc_id` = `krs_dzialalnosci`.`id`
		AND `krs_pozycje-dzialalnosci`.krs_id='" . addslashes($id) . "'
		AND `krs_pozycje-dzialalnosci`.`deleted`='0' 
		ORDER BY `krs_pozycje-dzialalnosci`.`przewazajaca` DESC, `krs_pozycje-dzialalnosci`.`ord` ASC");

$output = array();
foreach ($data as $d)
    $output[] = array_merge($d['krs_dzialalnosci'], $d['krs_pozycje-dzialalnosci']);

return $output;
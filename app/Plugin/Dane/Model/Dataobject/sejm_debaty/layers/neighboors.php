<?
	
	list($kolejnosc, $kadencja) = $this->DB->selectRow("SELECT `stenogramy_subpunkty`.`kolejnosc`, `stenogramy`.`kadencja` FROM `stenogramy_subpunkty` JOIN `stenogramy` ON `stenogramy_subpunkty`.`stenogram_id` = `stenogramy`.`id` WHERE `stenogramy_subpunkty`.`id`='$id'");
	
	$output = array(
		'prev' => $this->DB->selectAssoc("SELECT `stenogramy_subpunkty`.`id`, `stenogramy_subpunkty`.`tytul_punktu` as 'label' FROM `stenogramy_subpunkty` JOIN `stenogramy` ON `stenogramy_subpunkty`.`stenogram_id` = `stenogramy`.`id` WHERE `stenogramy`.`kadencja`='$kadencja' AND `stenogramy_subpunkty`.`kolejnosc`<'$kolejnosc' ORDER BY `stenogramy_subpunkty`.`kolejnosc` DESC LIMIT 1"),
		'next' => $this->DB->selectAssoc("SELECT `stenogramy_subpunkty`.`id`, `stenogramy_subpunkty`.`tytul_punktu` as 'label' FROM `stenogramy_subpunkty` JOIN `stenogramy` ON `stenogramy_subpunkty`.`stenogram_id` = `stenogramy`.`id` WHERE `stenogramy`.`kadencja`='$kadencja' AND `stenogramy_subpunkty`.`kolejnosc`>'$kolejnosc' ORDER BY `stenogramy_subpunkty`.`kolejnosc` ASC LIMIT 1"),
	);
	
	if( $output['prev'] )
		$output['prev']['url'] = '/dane/instytucje/3214,sejm/debaty/' . $output['prev']['id'];
		
	if( $output['next'] )
		$output['next']['url'] = '/dane/instytucje/3214,sejm/debaty/' . $output['next']['id'];
	
	return $output;
<?
	$data = $this->DB->selectAssocs("SELECT 
		`sejm_interpellations_letters`.`id`, 
		`sejm_interpellations_letters`.`str`, 
		`sejm_interpellations_letters`.`date_registered`, 
		`sejm_interpellations_letters`.`date_sent`, 
		`sejm_interpellations_letters`.`date_published`, 
		`sejm_interpellations_letters`.`date_responded`, 
		`sejm_interpellations_letters`.`date_withdrawn`, 
		GROUP_CONCAT(DISTINCT(CONCAT(`sejm_interpellations_letters_contents`.`id`, '\t', `sejm_interpellations_letters_contents`.`sid`, '\t', `sejm_interpellations_letters_contents`.`type`, '\t', `sejm_interpellations_letters_contents`.`to_str_a`, '\t', IFNULL(`sejm_interpellations_letters_contents`.`doc_id`, 'null'))) SEPARATOR \"\n\") as 'contents',
		GROUP_CONCAT(DISTINCT(`sejm_interpellations_letters_mps`.`mp_id`) SEPARATOR \"\n\") as 'mps'
	FROM 
		`sejm_interpellations_letters` 
	LEFT JOIN 
		`sejm_interpellations_letters_contents` ON
			(
				(`sejm_interpellations_letters`.`id` = `sejm_interpellations_letters_contents`.`letter_id`) AND 
				(`sejm_interpellations_letters_contents`.`is_deleted` = 0)
			)
	LEFT JOIN 
		`sejm_interpellations_letters_mps` ON
			(
				(`sejm_interpellations_letters`.`id` = `sejm_interpellations_letters_mps`.`letter_id`) AND 
				(`sejm_interpellations_letters_mps`.`is_deleted` = 0)
			)
	WHERE 
		`sejm_interpellations_letters`.`interpellation_id` = '" . $id . "' AND 
		`sejm_interpellations_letters`.`is_deleted` = '0' 
	GROUP BY 
		`sejm_interpellations_letters`.`id`
	ORDER BY
		`sejm_interpellations_letters`.`i` ASC, 
		`sejm_interpellations_letters_contents`.`i` ASC
	");
		
	foreach ($data as &$d) {
		$contents = empty($d['contents']) ? array() : explode("\n", $d['contents']);
		foreach ($contents as &$content) {
			$parts = explode("\t", $content);
			$content = array(
				'id' => $parts[0],
				'sid' => $parts[1],
				'type' => $parts[2],
				'to_str_a' => $parts[3],
				'doc_id' => ($parts[4] === 'null') ? null : $parts[4],
			);
		}
		$d['contents'] = $contents;
		
		$mps = empty($d['mps']) ? array() : explode("\n", $d['mps']);
		foreach ($mps as &$mp) {
			$mp = array(
				'id' => $mp,
			);
		}
		$d['mps'] = $mps;
	}
	
	return $data;
	
	
	
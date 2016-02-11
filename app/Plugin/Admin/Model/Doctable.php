<?php

class Doctable extends AppModel
{

    public function saveTables($id = 0, $tables)
    {
        $doctables = $this->query('SELECT id FROM doctables WHERE document_id = ?', array($id));

        if (count($doctables) > 0) {
            $ids = array_unique(array_column(array_column($doctables, 'doctables'), 'id'));
            $this->query('DELETE FROM `doctables` WHERE `document_id` = ?', array($id));
            $this->query('DELETE FROM `doctable_rows` WHERE `doctable_id` IN (?)', array(implode(',', $ids)));
            $this->query('DELETE FROM `doctable_cols` WHERE `doctable_id` IN (?)', array(implode(',', $ids)));
        }

        foreach ($tables as $table) {
            if ($table == 'false')
                continue;

            $this->clear();
            $this->save(array(
                'document_id' => $id,
                'page_index' => $table['pageIndex'],
                'name' => $table['name'],
                'x' => $table['x'],
                'y' => $table['y'],
                'width' => $table['width'],
                'height' => $table['height']
            ));

            $tempID = $this->getLastInsertID();

            if (isset($table['rows']) && is_array($table['rows'])) {
                foreach ($table['rows'] as $val) {
                    if($val == 0) continue;
                    $this->query('INSERT INTO doctable_rows (`doctable_id`, `top`) VALUES (?, ?)', array(
                        $tempID, $val
                    ));
                }
            }

            if (isset($table['cols']) && is_array($table['cols'])) {
                foreach ($table['cols'] as $val) {
                    if($val == 0) continue;
                    $this->query('INSERT INTO doctable_cols (`doctable_id`, `left`) VALUES (?, ?)', array(
                        $tempID, $val
                    ));
                }
            }

        }

        return true;
    }

    public function getTables($document_id = 0)
    {
        return $this->query('
            SELECT `doctables`.*,
            GROUP_CONCAT(DISTINCT `doctable_cols`.`left` SEPARATOR ";") as `c`,
            GROUP_CONCAT(DISTINCT `doctable_rows`.`top` SEPARATOR ";") as `r`
            FROM `doctables`
            LEFT JOIN `doctable_rows` ON `doctable_rows`.`doctable_id` = `doctables`.`id`
            LEFT JOIN `doctable_cols` ON `doctable_cols`.`doctable_id` = `doctables`.`id`
            WHERE `doctables`.`document_id` = ' . (int)$document_id . '
            GROUP BY `doctables`.`id`
        ');
    }

}
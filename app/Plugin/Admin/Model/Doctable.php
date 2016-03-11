<?php

/**
 * @property DoctableData DoctableData
 * @property DoctableDataTable DoctableDataTable
 * @property DoctableDataTableValue DoctableDataTableValue
 */
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

    public function saveTablesData($id = 0, $data) {
        $name = @$data['name'];
        $tables = $data['tables'];

        App::uses('DoctableData', 'Admin.Model');
        $this->DoctableData = new DoctableData();

        App::uses('DoctableDataTable', 'Admin.Model');
        $this->DoctableDataTable = new DoctableDataTable();

        App::uses('DoctableDataTableValue', 'Admin.Model');
        $this->DoctableDataTableValue = new DoctableDataTableValue();

        $this->DoctableData->save(array(
            'document_id' => $id,
            'user_id' => AuthComponent::user('id'),
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s')
        ));

        $doctableDataID = $this->DoctableData->getLastInsertID();
        foreach($tables as $table) {
            $this->DoctableDataTable->clear();
            $this->DoctableDataTable->save(array(
                'doctable_data_id' => $doctableDataID,
                'name' => $table['name'],
                'rows' => count($table['data']),
                'cols' => count($table['data'][0])
            ));

            $doctableDataTableID = $this->DoctableDataTable->getLastInsertID();
            $index = 0;
            foreach($table['data'] as $row) {
                foreach($row as $value) {
                    $this->DoctableDataTableValue->clear();
                    $this->DoctableDataTableValue->save(array(
                        'doctable_data_table_id' => $doctableDataTableID,
                        'index' => $index,
                        'value' => $value
                    ));

                    $index++;
                }
            }
        }

        return true;
    }

    public function getTables($document_id = 0)
    {
        $this->query('SET SESSION group_concat_max_len = 1000000;');
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

    public function getTablesData($document_id = 0)
    {
        return $this->query('
            SELECT `doctable_data`.*,
            COUNT(`doctable_data_table`.`id`) as `tables`,
            `users`.`username`
            FROM `doctable_data`
            LEFT JOIN `doctable_data_table` ON `doctable_data_table`.`doctable_data_id` = `doctable_data`.`id`
            LEFT JOIN `users` ON `users`.`id` = `doctable_data`.`user_id`
            WHERE `doctable_data`.`document_id` = ' . (int)$document_id . '
            GROUP BY `doctable_data`.`id`
        ');
    }

    public function getTableData($doctable_data_id = 0) {
        $this->query('SET SESSION group_concat_max_len = 1000000;');
        return array(
            'doctable_data' => $this->query('
                SELECT *
                FROM `doctable_data`
                WHERE `id` = ?
            ', array($doctable_data_id)),
            'doctable_data_tables' => $this->query('
                SELECT
                  `doctable_data_table`.*,
                  GROUP_CONCAT(`doctable_data_table_value`.`value` ORDER BY `doctable_data_table_value`.`index` ASC SEPARATOR "[{~}]") as `values`,
                  COUNT(`doctable_data_table_value`.`value`) as `values_count`
                FROM `doctable_data_table`
                LEFT JOIN `doctable_data_table_value`
                  ON `doctable_data_table_value`.`doctable_data_table_id` = `doctable_data_table`.`id`
                WHERE `doctable_data_id` = ?
                GROUP BY `doctable_data_table`.`id`
            ', array($doctable_data_id))
        );
    }

    public function exportMySQL($data) {
        $errors = array();
        $tables = $data['tables'];

        foreach($tables as $table) {
            $this->query('DROP TABLE IF EXISTS `docd_' . $table['dbName'] . '`');
        }

        $SQLTransaction = $this->tablesToSQLTransaction($tables);

        CakeLog::write('doctables_sql_dumps', $SQLTransaction);

        try {

            $results = $this->query($SQLTransaction);

            if ($results === false) {
                $errors[] = 'Wystąpił błąd składniowy, sprawdź nazwy kolumn, poprawność typów danych itd.';
            }

            foreach ($tables as $t => $table) {
                $results = $this->query('SELECT COUNT(*) FROM `docd_' . $table['dbName'] . '`');
                $count = (int)@$results[0][0]['COUNT(*)'];
                $rowsCount = count($table['rows']);
                if ($count != $rowsCount) {
                    $errors[] = 'Wystąpił błąd podczas dodawania rekordów do tabeli ' . $table['dbName'] . ' (' . $count . '/' . $rowsCount . '). Sprawdź jeszcze raz poprawność danych.';
                }
            }

        } catch(PDOException $e) {
            $errors[] = $e->getMessage();
        }

        if(count($errors) > 0) {
            foreach ($tables as $t => $table) {
                $this->query('DROP TABLE IF EXISTS `docd_' . $table['dbName'] . '`');
            }
        }

        return count($errors) == 0 ? true : $errors;
    }

    private function prepareSQLValue($value, $type)
    {
        switch(strtolower($type)) {

            case 'char':
                $value = '"' . str_replace('"', "'", trim($value)) . '"';
            break;

            case 'varchar':
                $value = '"' . str_replace('"', "'", trim($value)) . '"';
            break;

            case 'int':
                $value = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            break;

            case 'bigint':
                $value = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            break;

            case 'float':
                $value = str_replace(' ', '', $value);

                $dotParts = explode('.', $value);
                $comParts = explode(',', $value);

                if(strpos($value, ',') !== false &&
                    count($comParts) == 2 &&
                    strpos($value, '.' === false))
                {

                    $value = str_replace(',', '.', $value);

                } else if(strpos($value, ',') !== false &&
                    strpos($value, '.') !== false &&
                    count($dotParts) == 2 &&
                    count($comParts) == 2)
                {

                    if(strpos($value, '.') > strpos($value, ',')) {
                        $value = str_replace(',', '', $value);
                    } else {
                        $value = str_replace('.', '', $value);
                        $value = str_replace(',', '.', $value);
                    }
                }

            break;

            default:
                $value = trim($value);
            break;

        }

        return $value;
    }

    private function tablesToSQLTransaction($tables) {
        $sql = array('START TRANSACTION;');

        foreach($tables as $t => $table) {
            $sql[] = 'CREATE TABLE IF NOT EXISTS `docd_'. $table['dbName'] .'` (`id` INT(11) UNSIGNED AUTO_INCREMENT, `parent_id` INT(11) UNSIGNED DEFAULT 0,';
            foreach($table['cols'] as $c => $col) {
                $sql[] = ' `' . $col['name'] . '` ' . $col['type'] . '(' . $col['size'] . '),';
            }
            $sql[] = ' PRIMARY KEY (`id`));';
            $sql[] = 'INSERT INTO `docd_'. $table['dbName'] .'` (`id`, `parent_id`, `'. implode('`,`', array_column($table['cols'], 'name')) .'`) VALUES ';
            foreach($table['rows'] as $r => $row)
            {
                $row = $table['rows'][$r];
                $parent_id = 0;

                if($r > 0 && $table['rowParents'][$r] > 0)
                {
                    $rr = $r;
                    do {
                        $rr--;
                    } while($table['rowParents'][$r] - 1 != $table['rowParents'][$rr]);
                    $parent_id = $rr + 1;
                }

                $sql[] = '("", ' . $parent_id . ', ';
                foreach($row as $v => $value)
                {
                    $preparedValue = $this->prepareSQLValue($value, $table['cols'][$v]['type']);
                    $sql[] = $preparedValue . '' . ($v == count($row) - 1 ? '' : ', ');
                }

                $sql[] = ')' . ($r == count($table['rows']) - 1 ? ';' : ', ');
            }
        }

        $sql[] = 'COMMIT;';
        return implode('', $sql);
    }

    public function getDict() {
        return $this->query('SELECT * FROM `doctable_dict`');
    }

}
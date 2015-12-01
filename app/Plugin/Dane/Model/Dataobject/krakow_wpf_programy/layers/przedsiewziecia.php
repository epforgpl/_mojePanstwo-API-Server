<?

$name = $this->DB->selectValue('SELECT nazwa FROM krakow_wpf_program WHERE id = ' . (int) $id);

return $this->DB->selectAssocs('
    SELECT
      krakow_wpf_przedsiewziecie.*,
      krakow_wpf_podkategoria.nazwa as podkategoria_nazwa,
      krakow_wpf_kategoria.nazwa as kategoria_nazwa
    FROM krakow_wpf_przedsiewziecie
    JOIN
      krakow_wpf_podkategoria ON krakow_wpf_podkategoria.id = krakow_wpf_przedsiewziecie.podkategoria_id
    JOIN
      krakow_wpf_kategoria ON krakow_wpf_kategoria.id = krakow_wpf_przedsiewziecie.kategoria_id
    WHERE krakow_wpf_przedsiewziecie.nazwa = '.$this->DB->getDataSource()->value($name, 'string') . '
');
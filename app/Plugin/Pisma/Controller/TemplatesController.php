<?

class TemplatesController extends AppController
{
    public $uses = array('Dane.Dataobject', 'Pisma.Template');

    public function index()
    {

        $query = $this->request->query;
        $query['conditions']['Template.enabled'] = true;

        if (
            isset($this->request->query['adresat']) &&
            $this->request->query['adresat']
        ) {

            $parts = explode(':', $this->request->query['adresat']);
            $preset = @$parts[0];
            $preset_params = @$parts[2];

            switch ($preset) {

                case 'radni_gmin': {

                    if ($preset_params == 'K')
                        $query['conditions']['Template.id'] = 70;
                    else
                        $query['conditions']['Template.id'] = 69;

                    break;
                }
                case 'rada_gminy': {
                    $query['conditions']['Template.id'] = array(35, 36, 37, 38, 39, 40, 41, 42, 43, 72, 74);
                    break;
                }
                case 'dzielnice': {
                    $query['conditions']['Template.id'] = array(35, 36, 37, 38, 39, 40, 41, 42, 43, 72, 74);
                    break;
                }
                case 'gminy': {
                    $query['conditions']['Template.id'] = array(35, 36, 37, 38, 39, 40, 41, 42, 43, 74);
                    break;
                }
            }


        } else {

            $query['conditions']['Template.pisma_kategorie_id'] = 16;

        }

        $query['order'][] = 'Template.ord asc';
        $query['fields'] = array(
            'Template.id',
            'Template.nazwa',
            'Template.opis',
        );
        $data = $this->Template->find('all', $query);
        $this->setSerialized('data', $data);
    }

    public function view()
    {
        
        if( $object = $this->Template->findById($this->request->params['id'], array(
            'fields' => 'Template.id,Template.nazwa,Template.tresc,Template.adresat_opis'
        )) ) {
			
			$object['Inputs'] = $this->Template->query("SELECT `id`, `type`, `label`, `desc` FROM `pisma_szablony_pola` AS `Input` WHERE `template_id`='" . $object['Template']['id'] . "' ORDER BY `ord` ASC");
					
		}
		
        if (!isset($object['Template']) || empty($object['Template'])) {
            throw new NotFoundException();
        }

        $this->setSerialized('object', $object);
    }

    public function grouped()
    {

        App::import('model', 'DB');
        $DB = new DB();

        $temp = $DB->selectAssocs("
        	SELECT `pisma_szablony`.`id`, `pisma_szablony`.`nazwa`, `pisma_szablony`.`opis`, `pisma_kategorie`.`id` as `kategoria_id`, `pisma_kategorie`.`nazwa` as `kategoria_nazwa`
			FROM `pisma_szablony` 
			JOIN `pisma_kategorie`
			ON `pisma_szablony`.`pisma_kategorie_id` = `pisma_kategorie`.`id` 
			WHERE pisma_szablony.akcept='1'
			ORDER BY pisma_kategorie.ord ASC, pisma_szablony.ord ASC
		");

        $data = array();
        foreach ($temp as $t) {

            $data[$t['kategoria_id']]['id'] = $t['kategoria_id'];
            $data[$t['kategoria_id']]['nazwa'] = $t['kategoria_nazwa'];
            $data[$t['kategoria_id']]['templates'][] = array(
                'id' => $t['id'],
                'nazwa' => $t['nazwa'],
                'opis' => $t['opis'],
            );

        }
        unset($temp);
        $data = array_values($data);

        $this->setSerialized('data', $data);
    }
}
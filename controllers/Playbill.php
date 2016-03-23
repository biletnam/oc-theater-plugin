<?php namespace Abnmt\Theater\Controllers;

use Abnmt\Theater\Models\Event as EventModel;
use Abnmt\Theater\Models\Performance as PerformanceModel;
use Backend\Classes\Controller;
use \Clockwork\Support\Laravel\Facade as CW;

/**
 * Playbill Back-end Controller
 */
class Playbill extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();
        $this->addCss("/plugins/abnmt/theater/assets/css/custom.css");
        $this->addJs("/plugins/abnmt/theater/assets/js/custom.js");
    }

    public function index()
    {

        $now  = new \DateTime;
        $from = $now->format('Y-m-d');
        $to   = date("Y-m-d", strtotime("+2 month"));

        $url = "http://komedianty.apit.bileter.ru/d98a3e8ab87ecd3721a78a273bd9146a/afisha/?from=" . $from . "&to=" . $to . "&json=true";

        $contents = file_get_contents($url);

        $convert = iconv("UTF-8", "UTF-8//IGNORE", $contents);
        $convert = preg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/', ' ', $convert);

        $json = json_decode($convert, true);

        $this->vars['request'] = $url;
        $this->vars['json']    = $json;

        $this->vars['dump'] = [
            'exist'   => [],
            'changed' => [],
            'wrong'   => [],
            'new'     => [],
            'events'  => [],
            'finds'   => [],
        ];

        $changed = [];
        $saved   = [];

        $performances = PerformanceModel::isPublished()->get();
        $events       = EventModel::where('event_date', '>=', \Carbon::now())->get();

        $_changed = $_saved = [];

        foreach ($json as $month => $days) {

            $_changed[$month] = $_saved[$month] = [];

            foreach ($days as $day => $day_list) {

                $_changed[$month][$day] = $_saved[$month][$day] = [];

                foreach ($day_list as $key => $event) {

                    extract([
                        'bileter_id' => $event['IdPerformance'],
                        'title'      => $event['Name'],
                        'event_date' => new \DateTime($event['PerfDate']),
                    ]);

                    // Exclusion Titles
                    $exclusion = [
                        "Если поженились,значит,жить прид тся" => "Если поженились, значит, жить придётся!",
                        "Кыцик,Мыцик и т тушка Мари"                     => "Кыцик, Мыцик и тётушка Мари",
                        "Марлен,рожд нная для любви"                    => "Марлен, рожденная для любви",
                        "Не всякий вор - грабитель"                       => "Не всякий вор — грабитель",
                        " В Париж !"                                                    => "В Париж!",
                        "Брысь! Или истории кота Филофея"           => "Брысь! или Истории кота Филофея",
                    ];
                    if (array_key_exists($title, $exclusion)) {
                        $title = $exclusion[$title];
                    }

                    $search   = $events->where('bileter_id', (string) $bileter_id)->first(); // TODO: change DB type
                    $relation = $performances->where('title', $title)->first();

                    // $this->vars['dump']['finds'][] = [
                    //     'Search'   => $search ? $search->toArray() : null,
                    //     'Relation' => $relation ? $relation->toArray() : null,
                    // ];

                    if (!is_null($relation) && !is_null($search)) {
                        // Check Relation
                        if ($search->relation->title == $relation->title) {
                            // Exist --> is OK
                            $this->vars['dump']['exist'][] = $title;
                            $_saved[$month][$day][]        = $search;
                        }
                        if ($search->relation->title != $relation->title) {
                            $status = 'changed';
                            // Changed --> Update
                            $this->vars['dump']['changed'][] = $title;
                            $_changed[$month][$day][]        = compact('bileter_id', 'event_date', 'title', 'status', 'relation', 'search');
                        }
                    }

                    if (is_null($search) && !is_null($relation)) {
                        $status = 'new';
                        // No Event, but Relation --> Need Create
                        $this->vars['dump']['new'][] = $title;
                        $_changed[$month][$day][]    = compact('bileter_id', 'event_date', 'title', 'status', 'relation', 'search');

                    }

                    if (!is_null($search) && is_null($relation)) {
                        $status   = 'wrong_name';
                        $relation = $search->relation;
                        // No Relation, but Event --> Manual check
                        $this->vars['dump']['wrong'][] = $title;
                        $_changed[$month][$day][]      = compact('bileter_id', 'event_date', 'title', 'status', 'relation', 'search');

                    }

                    if (is_null($search) && is_null($relation)) {
                        $status = 'wrong';
                        // No Relation, No Event -> Bad, bad, bad…
                        $this->vars['dump']['wrong'][] = $title;
                        $_changed[$month][$day][]      = compact('bileter_id', 'event_date', 'title', 'status', 'relation', 'search');
                    }
                }
            }
        }

        $this->vars['saved']   = $_saved;
        $this->vars['changed'] = $_changed;

    }

    public function onSync()
    {
        extract($post = post());

        $result = $this->addEvent($bileter_id, $event_date, $relation_id);

        if (!$result) {
            return [
                '#messages'         => $this->makePartial('message', ['type' => 'warning', 'message' => 'Для мероприятия не найден спектакль!']),
                '#js' . $bileter_id => $this->makePartial('button_error'),
            ];
        }

        return [
            '#messages'         => $this->makePartial('message', ['type' => 'success', 'message' => 'ОК']),
            '#js' . $bileter_id => $this->makePartial('button_success'),
        ];
    }

    private function addEvent($bileter_id, $event_date, $relation_id)
    {
        $relation = PerformanceModel::find($relation_id);

        if (!$relation) {
            return null;
        }

        $event = EventModel::where('event_date', $event_date)->where('bileter_id', $bileter_id)->first();

        if (!$event) {
            $event = [
                'bileter_id'  => $bileter_id,
                'event_date'  => $event_date,
                'relation'    => $relation,
                'title'       => $relation ? $relation->title : null,
                'description' => $relation ? $relation->description : null,
            ];
            $model = EventModel::make($event);
        }

        $event['title']       = $relation ? $relation->title : null;
        $event['description'] = $relation ? $relation->description : null;

        // if (!is_null($relation)) {
        //     $result['title']       = $relation['title'];
        //     $result['description'] = $relation['description'];
        // }

        $relation->events()->add($event, null);

        return $event;
    }

    private function checkEvent($event)
    {

        $model = 'Abnmt\Theater\Models\Event';

        $event_date = new \DateTime($event['PerfDate']);
        $bileter_id = $event['IdPerformance'];

        $search = $model::where('bileter_id', '=', $bileter_id)->first();

        $relation = $this->findRelation($event['Name']);

        $result = [
            'event_date' => $event_date,
            'bileter_id' => $bileter_id,
            'relation'   => $relation,
            'changed'    => 0,
        ];

        if (!is_null($relation)) {
            $result['title']       = $relation['title'];
            $result['description'] = $relation['description'];
        }

        $model = $model::make($result);

        // if (!is_null($relation)) {
        //     $relation->events()->add($model, null);
        // }

        $result['model'] = $model;

        CW::info(['Model' => $model]);

        if (is_null($search)) {

            if (is_null($relation)) {
                $result['search_string'] = $event['Name'];
                return $result; // New, not found relation
            }

            $relation->events()->add($model, null);
            return $result; // New, relation find
        }

        if ($search['relation_id'] != $relation['id']) {

            $result['changed'] = $search['relation_id'];

            $relation->events()->add($model, null);

            return $result; // Exist, changed
        }

        return; // Exist, not changed

    }

    private function findRelation($name)
    {

        $models = [
            'Abnmt\Theater\Models\Performance',
            // 'Abnmt\Theater\Models\Article',
            // 'Abnmt\Theater\Models\Person',
        ];

        $exclusion = [
            "Если поженились,значит,жить прид тся" => "Если поженились, значит, жить придётся!",
            "Кыцик,Мыцик и т тушка Мари"                     => "Кыцик, Мыцик и тётушка Мари",
            "Марлен,рожд нная для любви"                    => "Марлен, рождённая для любви",
            "Не всякий вор - грабитель"                       => "Не всякий вор — грабитель",
            " В Париж !"                                                    => "В Париж!",
        ];

        if (array_key_exists($name, $exclusion)) {
            $name = $exclusion[$name];
        }

        foreach ($models as $model) {
            $post = $model::where('title', '=', $name)->first();
            if (!is_null($post)) {
                return $post;
            }
        }

        // echo "Not find relation for " . $name . "\n";
        return;
    }

}

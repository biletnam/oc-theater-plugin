<?php namespace Abnmt\Theater\Components;

use Cms\Classes\ComponentBase;
use Abnmt\Theater\Models\Performance as PerformanceModel;

use \Clockwork\Support\Laravel\Facade as CW;
// use Laravelrus\LocalizedCarbon\LocalizedCarbon as Carbon;
use \Carbon\Carbon as Carbon;

class Performance extends ComponentBase
{

    /**
     * A Post object
     * @var Model
     */
    public $post;

    /**
     * A Post slug
     * @var string
     */
    public $slug;

    /**
     * A Roles in Performance
     * @var string
     */
    public $roles;

    /**
     * A Participation
     * @var string
     */
    public $participation;

    /**
     * Reference to the page name for linking to posts.
     * @var string
     */
    public $personPage = 'theater/person';

    /**
     * Reference to the page name for linking to posts.
     * @var string
     */
    public $pressPage = 'theaterPress/article';


    public function componentDetails()
    {
        return [
            'name'        => 'Спектакль',
            'description' => 'Выводит одиночный спектакль'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->prepareVars();

        if ($this->slug = $this->param('slug'))
            $this->post = $this->page['post'] = $this->loadPost();

        // $this->page['roles'] = $this->roles;

    }

    protected function prepareVars()
    {
        // $this->categoryFilter = $this->param('category');
    }

    protected function loadPost()
    {
        $post = PerformanceModel::isPublished()
            ->Performance()
            ->whereSlug($this->slug)
            ->first()
        ;

        /*
         * Add a "URL" helper attribute for linking to each performance and person press
         */

        $this->roles = [];
        $this->participation = [];

        $post->participation->each(function($role)
        {
            $role->person->setUrl($this->personPage, $this->controller);

            if ($role['group'] != NULL) {
                $this->roles[$role['group']][] = $role;
                return;
            }

            $this->participation[$role['title']]['title'] = $role->title;
            $this->participation[$role['title']]['type'] = $role->type;
            $this->participation[$role['title']]['description'] = $role->description;
            $this->participation[$role['title']]['group'] = $role->group;
            $this->participation[$role['title']]['persons'][] = $role->person;
        });

        $post->press->each(function($press){
            $press->setUrl($this->pressPage, $this->controller);
        });


        // BACKGROUNDS

        $bgs = [];
        $key = 0;

        $summ = [0];

        $bg = [
            ['top right', 960],
            ['middle right', 768],
            ['bottom right', 768],
            ['bottom left', 592],
            ['middle left', 592],
            ['top left', 304],
        ];

        $post->background->each(function($image) use (&$bgs, $bg, &$key, &$summ) {
            $image['sizes'] = $sizes = getimagesize('./' . $image->getPath());
            $bgs[] = [
                'width'  => $sizes[0],
                'height' => $sizes[1],
                'class'  => $bg[$key][0],
                'dest_width'  => $bg[$key][1],
                'dest_height' => round($bg[$key][1]/$sizes[0]*$sizes[1]),
            ];
            $summ[$key+1] = $summ[$key] + $bgs[$key]['dest_height'];
            $key++;
        });

        $count = count($bgs);
        foreach ($bgs as $pos => &$bg) {
            $bg_pos[$pos] = [];
            $i = $pos;
            while ($i < $count) {
                $bg_pos[$pos][$i] = [
                    'top'    => $summ[$pos],
                    'width'  => $bg['dest_width'],
                    'height' => $bg['dest_height'],
                    'bottom' => $summ[$i+1] - $bg['dest_height'] - $summ[$pos],
                    'all'    => $summ[$i+1],
                    'top_perc'    => $summ[$pos]/$summ[$i+1]*100 . '%',
                    'height_perc' => $bg['dest_height']/$summ[$i+1]*100 . '%',
                    'bottom_perc' => ($summ[$i+1] - $bg['dest_height'] - $summ[$pos])/$summ[$i+1]*100 . '%',
                ];
                $i++;
            }
        }

        $post->bg_pos = $bg_pos;

        $post->roles = $this->roles;
        $post->roles_ng = $this->participation;

        CW::info(['Performance' => $post, $bgs, $summ]);

        return $post;
    }

    // protected function loadEvents($post)
    // {
    //     $post->events();
    // }
}
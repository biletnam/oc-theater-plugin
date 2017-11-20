<?php namespace Abnmt\Theater\Components;

use Abnmt\Theater\Models\Performance as PerformanceModel;
use Cms\Classes\ComponentBase;

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
            'description' => 'Выводит одиночный спектакль',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->prepareVars();

        if ($this->slug = $this->param('slug')) {
            $this->post = $this->page['post'] = $this->loadPost();
        }
    }

    protected function prepareVars()
    {
        $this->params = $this->getProperties();
    }

    protected function loadPost()
    {
        $post = PerformanceModel::isPublished()
            ->Performance()
            ->whereSlug($this->slug)
            ->first()
        ;

        $this->roles         = [];
        $this->participation = [];

        $this->creators_group = [];
        $this->roles_group    = [];

        /**
         * Add a "URL" helper attribute for linking to each performance and person press
         */
        $post->participation->each(function ($role) {
            $role->person->setUrl($this->personPage, $this->controller);

            if ($role['group'] != null) {
                $this->roles[$role['group']][] = $role;
                return;
            }

            $this->participation[$role['title']]['title']       = $role->title;
            $this->participation[$role['title']]['type']        = $role->type;
            $this->participation[$role['title']]['description'] = $role->description;
            $this->participation[$role['title']]['group']       = $role->group;
            $this->participation[$role['title']]['persons'][]   = $role->person;
        });

        $roles_by_group = $post->participation
            ->groupBy('type')
            ->map(function ($type) {
                return $type
                    ->groupBy(function ($role) {
                        // if ($role->group == null) {
                        //     return "none";
                        // }
                        return $role->group ?: 'none';
                    })
                    ->map(function ($group) {
                        return $group_of_roles = $group
                            ->groupBy('title')
                            ->map(function ($role) {
                                if ($role->count() == 1) {
                                    return ['person' => $role->pluck('person')->first()];
                                }
                                return ['persons' => $role->pluck('person')];
                            })
                        ;
                        // $return = $group_of_roles
                        //     ->map(function($role))
                        // ;
                        // return $return;
                    })
                ;
            })
        ;

        $post->press->each(function ($press) {
            $press->setUrl($this->pressPage, $this->controller);
        });

        // ROLES
        $post->roles_by_group = $roles_by_group;
        $post->roles          = $this->roles;
        $post->roles_ng       = $this->participation;

        return $post;
    }

}

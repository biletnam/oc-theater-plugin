<?php namespace Abnmt\Theater\Components;

use Abnmt\Theater\Models\Article as ArticleModel;
use Abnmt\Theater\Models\Performance as PerformanceModel;
use Abnmt\Theater\Models\Person as PersonModel;
use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Request;

class Theater extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Театр',
            'description' => 'Компонент для вывода Театральных данных',
        ];
    }

    public function defineProperties()
    {
        return [
            'type'            => [
                'title'       => 'Тип',
                'description' => 'Выводит записи с выбранным типом',
                'type'        => 'dropdown',
                'default'     => 'person',
            ],
            'categories'      => [
                'title'       => 'Категории',
                'description' => 'Выводит записи выбранных категории',
                'type'        => 'dropdown',
                'depends'     => ['type'],
            ],
            'sort'            => [
                'title'       => 'Сортировка',
                'description' => 'Сортирует список записей',
                'type'        => 'dropdown',
                'depends'     => ['type'],
            ],
            'scopes'          => [
                'title'       => 'Наборы',
                'description' => 'Загружает для записей дополнительные наборы данных',
                'type'        => 'dropdown',
                'depends'     => ['type'],
            ],
            'performancePage' => [
                'title'       => 'Страница спектакля',
                'description' => 'Шаблон страницы спектакля',
                'type'        => 'dropdown',
                'default'     => 'theater/performance',
                'group'       => 'Страницы',
            ],
            'personPage'      => [
                'title'       => 'Страница персоналии',
                'description' => 'Шаблон страницы персоналии',
                'type'        => 'dropdown',
                'default'     => 'theater/person',
                'group'       => 'Страницы',
            ],
            'postPage'        => [
                'title'       => 'Страница новости',
                'description' => 'Шаблон страницы новости',
                'type'        => 'dropdown',
                'default'     => 'theaterNews/post',
                'group'       => 'Страницы',
            ],
            'articlePage'     => [
                'title'       => 'Страница статьи',
                'description' => 'Шаблон страницы статьи',
                'type'        => 'dropdown',
                'default'     => 'theater/article',
                'group'       => 'Страницы',
            ],
            'pageNumber'      => [
                'title'       => 'Номер страницы',
                'description' => 'Переменная с номером страницы',
                'type'        => 'string',
                'default'     => '{{ :page }}',
                'group'       => 'Пагинация',
            ],
            'postsPerPage'    => [
                'title'             => 'Количество записей на страницу',
                'description'       => 'Переменная с количеством записей на страницу',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'Должно быть целым числом',
                'default'           => '10',
                'group'             => 'Пагинация',
            ],
        ];
    }

    public function getPerformancePageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }
    public function getPersonPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }
    public function getPostPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }
    public function getArticlePageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getTypeOptions()
    {
        $options = [
            'person'      => 'Люди',
            'performance' => 'Спектакли',
            'article'     => 'Статьи',
        ];
        return $options;
    }

    public function getCategoriesOptions()
    {
        $type    = Request::input('type');
        $options = [
            'person'      => [
                '{{ :category }}' => 'Из URL',
                'actor'           => 'Актёры',
                'cooperate'       => 'Приглашенные артисты',
                'administration'  => 'Администрация',
            ],
            'performance' => [
                '{{ :category }}' => 'Из URL',
                'normal'          => 'Спектакли',
                'child'           => 'Детские спектакли',
                'archived'        => 'Архивные спектакли',
            ],
            'article'     => [
                '{{ :category }}' => 'Из URL',
                'news'            => 'Новости',
                'press'           => 'Пресса',
                'journal'         => 'Журнал',
            ],
        ];
        return $options[$type];
    }

    public function getSortOptions()
    {
        $type    = Request::input('type');
        $options = [
            'person'      => PersonModel::$allowedSortingOptions,
            'performance' => PerformanceModel::$allowedSortingOptions,
            'article'     => ArticleModel::$allowedSortingOptions,
        ];
        return $options[$type];
    }

    public function getScopesOptions()
    {
        $type    = Request::input('type');
        $options = [
            'person'      => PersonModel::$allowedScopingOptions,
            'performance' => PerformanceModel::$allowedScopingOptions,
            'article'     => ArticleModel::$allowedScopingOptions,
        ];
        return $options[$type];
    }

    /**
     * A Params object
     * @var array
     */
    public $params;

    /**
     * A Post slug
     * @var string
     */
    public $slug;

    /**
     * Reference to the page name for linking to posts.
     * @var string
     */
    public $postPage;

    /**
     * A collection of posts to display
     * @var Collection
     */
    public $posts;

    /**
     * Parameter to use for the page number
     * @var string
     */
    public $pageParam;

    /**
     *  onRun function
     */
    public function onRun()
    {
        $this->prepareVars();

        $this->posts = $this->page['posts'] = $this->listPosts();

        if ($this->slug = $this->param('slug')) {
            $this->post = $this->page['post'] = $this->loadPost();
        }

    }

    /**
     *  Prepare vars
     */
    protected function prepareVars()
    {
        $this->params = $this->getProperties();

        $this->pageParam         = $this->page['pageParam']         = $this->paramName('pageNumber');
        $this->params['page']    = $this->property('pageNumber');
        $this->params['perPage'] = $this->property('postsPerPage');

        $this->params['slug'] = $this->param('slug');
    }

    protected function listPosts()
    {

        extract($this->params);

        $model = "Abnmt\\Theater\\Models\\" . ucfirst($type);

        /**
         * List all posts
         */
        $posts = $model::listFrontEnd($this->params);

        $page           = $type . 'Page';
        $this->postPage = $$page;

        $posts->each(function ($post) {
            $post->setUrl($this->postPage, $this->controller);
        });

        return $posts;
    }

    protected function loadPost()
    {

        extract($this->params);

        $model = "Abnmt\\Theater\\Models\\" . ucfirst($type);

        /**
         * Load the posts
         */
        $post = $model::LoadPost($this->params);

        return $post;
    }

}

<?php namespace Abnmt\Theater\Components;

use Cms\Classes\ComponentBase;

class Repertoire extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Репертуар',
            'description' => 'Выводит репертуар'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

}
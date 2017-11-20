<?php namespace Abnmt\Theater\Models;

use Model;

/**
 * Participation Model
 */
class Participation extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'abnmt_theater_participations';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['title', 'performance', 'person', 'type', 'group', 'description', 'sort_order'];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'performance' => ['Abnmt\Theater\Models\Performance'],
        'person'      => ['Abnmt\Theater\Models\Person'],
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];


    public function listRoles($keyValue = null, $fieldName = null){
        return $this->all()->filter(function($item){return $item->title;})->unique('title')->lists('title', 'title');
        // return $this->all()->unique('group')->filter()->lists('group', 'group');
    }
    public function listGroups($keyValue = null, $fieldName = null){
        return $this->all()->filter(function($item){return $item->group;})->unique('group')->lists('group', 'group');
        // return $this->all()->unique('group')->filter()->lists('group', 'group');
    }

}

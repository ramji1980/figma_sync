<?php
namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;


class FigmaComponentset extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'figma_component_set';


    protected $fillable = [
    'figma_id',
    'file_key',
    'key',
    'name',
    'description',
    //'page_id'    
    ];
}
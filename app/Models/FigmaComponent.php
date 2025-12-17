<?php
namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;


class FigmaComponent extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'figma_components';


    protected $fillable = [
    'figma_id',
    'file_key',
    'key',
    'set_id',
    'name',
    'description',
    'node_json'
    ];
}
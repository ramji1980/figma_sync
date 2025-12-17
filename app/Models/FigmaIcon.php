<?php
namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;


class FigmaIcon extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'figma_icons';


    protected $fillable = [
    'node_id',
    'component_key',
    'file_key',
    'name',    
    'is_icon_set',
    'icon_rules',
    'dimensions',
    'raw_node'
    ];
}
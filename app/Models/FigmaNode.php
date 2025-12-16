<?php
namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;


class FigmaNode extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'figma_nodes';


    protected $fillable = [
    'node_id',    
    'raw',
    'metadata'
    ];
}
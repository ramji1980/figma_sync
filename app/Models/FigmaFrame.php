<?php
namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;


class FigmaFrame extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'figma_frames';


    protected $fillable = [
    'node_id',
    'name',
    'raw',
    'metadata'
    ];
}
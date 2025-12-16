<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class FigmaImage extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'frames_images';

    protected $fillable = [
        'node_id',           // frame_id (Figma node id)
        'file_key',
        'format',        // png | jpg | svg
        'scale',         // 1 | 2 | 3 | 4
        'image_url',     // temporary CDN URL
        'metadata',       
        
    ];

   // public $timestamps = false;
}

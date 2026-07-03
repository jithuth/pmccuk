<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = "news";

    public $timestamps = false;

    protected $fillable = [
        "title", "content", "image_url", "status", "created_at"
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];
}

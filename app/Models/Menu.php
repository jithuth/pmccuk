<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected $casts = [
        'title' => 'encrypted',
        'url'   => 'encrypted',
    ];
}

<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FareCategory extends Model
{
    protected $table = 'fare_categories';
    public $timestamps = false;
    protected $fillable = ['name'];
}

<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FareRubric extends Model
{
    protected $table = 'fare_rubrics';
    public $timestamps = false;
    protected $fillable = ['name'];

    public function items()
    {
        return $this->hasMany(FareRubricItem::class, 'rubric_id');
    }
}

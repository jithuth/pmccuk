<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FareRubricItem extends Model
{
    protected $table = 'fare_rubric_items';
    public $timestamps = false;
    protected $fillable = ['rubric_id', 'category_id', 'member_price', 'guest_price'];

    public function category()
    {
        return $this->belongsTo(FareCategory::class, 'category_id');
    }
}

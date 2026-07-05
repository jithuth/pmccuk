<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    protected $table = 'albums';
    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'cover_image_url'
    ];

    public function photos()
    {
        return $this->hasMany(Gallery::class, 'album_id');
    }
}

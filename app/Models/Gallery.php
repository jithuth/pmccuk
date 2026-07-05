<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    protected $table = 'gallery';
    public $timestamps = false; // Legacy table

    protected $fillable = [
        'title', 'image_url', 'album_id'
    ];

    public function album()
    {
        return $this->belongsTo(Album::class, 'album_id');
    }
}

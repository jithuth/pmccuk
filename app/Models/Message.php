<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSmartDecryption;

class Message extends Model
{
    use HasSmartDecryption;

    protected $table = 'messages';
    public $timestamps = false; 

    protected $fillable = [
        'name', 'email', 'subject', 'message', 'status'
    ];

    protected $casts = [
        // No encrypted casts, the trait handles it dynamically
    ];
}

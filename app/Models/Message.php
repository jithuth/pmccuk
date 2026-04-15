<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';
    public $timestamps = false; // Legacy table

    protected $fillable = [
        'name', 'email', 'subject', 'message', 'status'
    ];

    protected $casts = [
        'name' => 'encrypted',
        'email' => 'encrypted',
        'subject' => 'encrypted',
        'message' => 'encrypted',
    ];
}

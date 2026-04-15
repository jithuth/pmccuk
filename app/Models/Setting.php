<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    public $timestamps = false; // Legacy table has updated_at only or via DB triggers

    protected $fillable = [
        'setting_key', 'setting_value'
    ];
    protected $casts = [
        'setting_value' => 'encrypted',
    ];
}

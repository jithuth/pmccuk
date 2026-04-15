<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRequest extends Model
{
    protected $table = 'student_requests';
    protected $guarded = [];

    protected $casts = [
        'parent_name' => 'encrypted',
        'student_name' => 'encrypted',
        'school' => 'encrypted',
        'grade' => 'encrypted',
        'dob' => 'encrypted',
    ];
}

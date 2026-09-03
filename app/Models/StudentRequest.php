<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSmartDecryption;

class StudentRequest extends Model
{
    use HasSmartDecryption;

    protected $table = 'student_requests';
    protected $guarded = [];
}


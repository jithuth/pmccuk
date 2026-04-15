<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    protected $table = 'payment_history';
    protected $guarded = [];

    protected $casts = [
        'transaction_ref' => 'encrypted',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}

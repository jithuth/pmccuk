<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    protected $table = 'financial_transactions';
    
    protected $fillable = [
        'type', 'category', 'amount', 'description', 
        'ref_no', 'transaction_date', 'payment_method'
    ];

    protected $casts = [
        'description' => 'encrypted',
        'ref_no'      => 'encrypted',
    ];

    public $timestamps = true;
}

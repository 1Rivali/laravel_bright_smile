<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_method',
        'payment_date',
        'total_amount',
        'paid_amount',
        'remaining_amount',
    ];

    protected $guarded = [
        'deleted_at'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }
}

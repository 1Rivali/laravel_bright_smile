<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'evaluation',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}

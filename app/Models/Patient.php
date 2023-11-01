<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'marital_status',
        'health_status',
    ];

    protected $guarded = [
        'deleted_at'
    ];

    protected $casts = [
        'marital_status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comment()
    {
        return $this->hasMany(Comment::class);
    }

    public function appointment()
    {
        return $this->hasMany(Appointment::class);
    }

    public function invoice()
    {
        return $this->hasMany(Invoice::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($patient) {
            $patient->comment()->delete();
            $patient->appointment()->delete();
            $patient->invoice()->delete();
        });

        static::restoring(function ($patient) {
            $patient->comment()->withTrashed()->restore();
            $patient->appointment()->withTrashed()->restore();
            $patient->invoice()->withTrashed()->restore();
        });
    }
}

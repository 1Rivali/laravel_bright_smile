<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shift',
        'working_days',
        'specialization',
        "image",
    ];

    protected $guarded = [
        'deleted_at'
    ];

    protected $casts = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function appointment()
    {
        return $this->hasMany(Appointment::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($doctor) {
            $doctor->appointment()->delete();
        });

        static::restoring(function ($doctor) {
            $doctor->appointment()->withTrashed()->restore();
        });
    }
}

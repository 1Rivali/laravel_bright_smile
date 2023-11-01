<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'deleted_at'
    ];

    protected $fillable = [
        'name',
        'number',
        'doctor_name',
        'work_hours',
        'phone',
        "image",
    ];

    public function doctor()
    {
        return $this->hasMany(Doctor::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($department) {
            $department->doctor()->delete();
        });

        static::restoring(function ($department) {
            $department->doctor()->withTrashed()->restore();
        });
    }
}

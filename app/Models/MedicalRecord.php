<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'deleted_at'
    ];

    protected $fillable = [
        'diagnosis',
        'treatment',
    ];



    public function appointment()
    {
        return $this->hasMany(Appointment::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($medicalRecord) {
            $medicalRecord->appointment()->delete();
        });

        static::restoring(function ($medicalRecord) {
            $medicalRecord->appointment()->withTrashed()->restore();
        });
    }
}

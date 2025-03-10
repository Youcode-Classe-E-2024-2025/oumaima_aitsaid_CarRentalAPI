<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;
    protected $fillable= [
        'brand',
        'model',
        'license_plate',
        'year',
        'color',
        'transmission',
        'fuel_type',
        'seats',
        'daily_rate',
        'is_available',
        'description',
        'image',
    ];
    protected $casts = [
        'is_available' => 'boolean',
        'daily_rate' => 'decimal:2',
        'year' => 'integer',
    ];
    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }
}

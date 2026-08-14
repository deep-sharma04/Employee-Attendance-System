<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'holiday_date',
        'name',
        'description',
        'is_recurring_yearly',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_recurring_yearly' => 'boolean',
        ];
    }
}

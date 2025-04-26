<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CookingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipe_id',
        'cooked_at',
        'rating',
        'notes'
    ];

    protected $casts = [
        'cooked_at' => 'datetime',
        'rating' => 'float'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}

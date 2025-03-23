<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'api_key_name',
        'active'
    ];

    public function modelOptions(): HasMany
    {
        return $this->hasMany(ModelOption::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'model_id',
        'description',
        'supports_vision',
        'supports_file_input',
        'supports_image_generation',
        'supports_tts',
        'supports_stt',
        'supports_fine_tuning',
        'supports_streaming',
        'active',
        'api_provider_id'
    ];

    public function apiProvider()
    {
        return $this->belongsTo(ApiProvider::class);
    }
}

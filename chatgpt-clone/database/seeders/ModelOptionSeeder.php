<?php

namespace Database\Seeders;

use App\Models\ApiProvider;
use App\Models\ModelOption;
use Illuminate\Database\Seeder;

class ModelOptionSeeder extends Seeder
{
    public function run(): void
    {
        $openaiProvider = ApiProvider::where('name', 'OpenAI')->first();

        if ($openaiProvider) {
            $models = [
                [
                    'name' => 'GPT-4o Mini',
                    'model_id' => 'gpt-4o-mini',
                    'description' => 'Compact GPT-4 model for efficient responses',
                    'supports_vision' => true,
                    'supports_file_input' => true,
                    'supports_image_generation' => false,
                    'supports_tts' => false,
                    'supports_stt' => false,
                    'supports_fine_tuning' => false,
                    'supports_streaming' => true,
                    'active' => true,
                ],
                [
                    'name' => 'O1 Mini',
                    'model_id' => 'o1-mini',
                    'description' => 'Lightweight model for quick responses',
                    'supports_vision' => false,
                    'supports_file_input' => false,
                    'supports_image_generation' => false,
                    'supports_tts' => false,
                    'supports_stt' => false,
                    'supports_fine_tuning' => false,
                    'supports_streaming' => true,
                    'active' => true,
                ],
                [
                    'name' => 'DALL-E 2',
                    'model_id' => 'dall-e-2',
                    'description' => 'Generate images from text',
                    'supports_vision' => false,
                    'supports_file_input' => false,
                    'supports_image_generation' => true,
                    'supports_tts' => false,
                    'supports_stt' => false,
                    'supports_fine_tuning' => false,
                    'supports_streaming' => false,
                    'active' => true,
                ],
            ];

            foreach ($models as $model) {
                ModelOption::updateOrCreate(
                    ['model_id' => $model['model_id']],
                    array_merge($model, ['api_provider_id' => $openaiProvider->id])
                );
            }
        }
    }
}

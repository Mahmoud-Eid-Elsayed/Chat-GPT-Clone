<?php

namespace Database\Seeders;

use App\Models\ApiProvider;
use Illuminate\Database\Seeder;

class ApiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'OpenAI',
                'base_url' => 'https://api.openai.com/v1',
                'api_key_name' => 'Authorization',
                'active' => true
            ],
         
        ];

        foreach ($providers as $provider) {
            ApiProvider::updateOrCreate(
                ['name' => $provider['name']],
                $provider
            );
        }
    }
}
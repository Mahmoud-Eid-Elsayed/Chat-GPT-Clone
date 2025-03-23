<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Message;
use App\Models\ModelOption;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AIService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getResponse(Chat $chat, Message $userMessage)
    {
        $modelOption = $chat->modelOption;
        $apiProvider = $modelOption->apiProvider;

        $history = $this->prepareHistory($chat);

        try {
            switch ($apiProvider->name) {
                case 'OpenAI':
                    return $this->callOpenAI($modelOption, $history, $userMessage);

                default:
                    throw new \Exception('Unknown API provider: ' . $apiProvider->name);
            }
        } catch (\Exception $e) {
            // Log the full exception details for debugging
            Log::error('AI API Error: ' . $e->getMessage(), [
                'exception' => $e,
                'model_option' => $modelOption->toArray(),
                'api_provider' => $apiProvider->toArray(),
                'user_message' => $userMessage->content
            ]);
            return 'Sorry, there was an error processing your request: ' . $e->getMessage();
        }
    }

    protected function prepareHistory(Chat $chat)
    {
        return $chat->messages()
            ->orderBy('created_at')
            ->get();
    }

    protected function callOpenAI(ModelOption $modelOption, $history, Message $userMessage)
    {
        $apiKey = env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key is not set in .env file');
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $messages = [];
        foreach ($history as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        // Log the request for debugging
        Log::info('OpenAI API Request', [
            'model' => $modelOption->model_id,
            'messages_count' => count($messages),
            'last_message' => end($messages)
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
                    'model' => $modelOption->model_id,
                    'messages' => $messages,
                    'temperature' => 0.7,
                ]);

        if ($response->successful()) {
            $responseData = $response->json();
            if (!isset($responseData['choices'][0]['message']['content'])) {
                Log::error('OpenAI API Response missing expected data', ['response' => $responseData]);
                throw new \Exception('Invalid response format from OpenAI API');
            }
            return $responseData['choices'][0]['message']['content'];
        } else {
            // Log the full error response
            Log::error('OpenAI API Error Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('OpenAI API Error: ' . $response->status() . ' - ' . $response->body());
        }
    }

    public function streamResponse(Chat $chat, Message $userMessage)
    {
        $modelOption = $chat->modelOption;
        $apiProvider = $modelOption->apiProvider;

        $history = $this->prepareHistory($chat);

        try {
            switch ($apiProvider->name) {
                case 'OpenAI':
                    $this->streamOpenAI($modelOption, $history, $userMessage);
                    break;

                default:
                    throw new \Exception('Unknown API provider: ' . $apiProvider->name);
            }
        } catch (\Exception $e) {
            Log::error('AI API Streaming Error: ' . $e->getMessage(), [
                'exception' => $e,
                'model_option' => $modelOption->toArray(),
                'api_provider' => $apiProvider->toArray(),
                'user_message' => $userMessage->content
            ]);
            echo "data: " . json_encode(['content' => 'Sorry, there was an error processing your request: ' . $e->getMessage()]) . "\n\n";
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }
    }

    protected function streamOpenAI(ModelOption $modelOption, $history, Message $userMessage)
    {
        $apiKey = env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key is not set in .env file');
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $messages = [];
        foreach ($history as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        // Create empty message to append to
        $assistantMessage = Message::create([
            'chat_id' => $userMessage->chat_id,
            'role' => 'assistant',
            'content' => '',
        ]);

        // Log the request for debugging
        Log::info('OpenAI API Streaming Request', [
            'model' => $modelOption->model_id,
            'messages_count' => count($messages),
            'assistant_message_id' => $assistantMessage->id
        ]);

        // For OpenAI streaming, we need to use the client directly
        try {
            $client = new Client();
            $response = $client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $modelOption->model_id,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'stream' => true,
                ],
                'stream' => true,
            ]);

            $fullContent = '';
            $body = $response->getBody();

            // Use proper stream reading instead of readline
            $buffer = '';

            while (!$body->eof()) {
                // Read a chunk of data (4KB at a time)
                $chunk = $body->read(4096);
                $buffer .= $chunk;

                // Process complete lines
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);

                    // Skip empty lines
                    if (trim($line) === '') {
                        continue;
                    }

                    // Check for the "data: [DONE]" message
                    if (trim($line) === 'data: [DONE]') {
                        echo "data: [DONE]\n\n";
                        ob_flush();
                        flush();
                        break 2; // Exit both loops
                    }

                    // Make sure the line starts with "data: "
                    if (strpos($line, 'data: ') === 0) {
                        $jsonString = substr($line, 6); // Remove "data: " prefix

                        // Parse the JSON data
                        $data = json_decode($jsonString, true);

                        if (
                            json_last_error() === JSON_ERROR_NONE &&
                            isset($data['choices'][0]['delta']['content'])
                        ) {
                            $content = $data['choices'][0]['delta']['content'];
                            $fullContent .= $content;

                            // Update the message in the database
                            $assistantMessage->update(['content' => $fullContent]);

                            // Send the chunk to the client
                            echo "data: " . json_encode([
                                'id' => $assistantMessage->id,
                                'content' => $fullContent  // Send full content for better client display
                            ]) . "\n\n";

                            ob_flush();
                            flush();
                        }
                    }
                }
            }

            // Make sure we send a final DONE event
            if (!str_contains($buffer, 'data: [DONE]')) {
                echo "data: [DONE]\n\n";
                ob_flush();
                flush();
            }

        } catch (\Exception $e) {
            Log::error('OpenAI Streaming Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            echo "data: " . json_encode([
                'id' => $assistantMessage->id,
                'content' => 'Error: ' . $e->getMessage()
            ]) . "\n\n";

            echo "data: [DONE]\n\n";
            ob_flush();
            flush();

            throw $e;
        }
    }

    public function generateImage($prompt)
    {
        $apiKey = env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key is not set in .env file');
        }

        $endpoint = 'https://api.openai.com/v1/images/generations';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                        'prompt' => $prompt,
                        'n' => 1,
                        'size' => '1024x1024',
                        'model' => 'dall-e-3', // or whatever model you want to use
                    ]);

            if ($response->successful()) {
                $responseData = $response->json();
                if (!isset($responseData['data'][0]['url'])) {
                    Log::error('OpenAI Image API Response missing expected data', ['response' => $responseData]);
                    throw new \Exception('Invalid response format from OpenAI Image API');
                }
                return $responseData['data'][0]['url'];
            } else {
                Log::error('OpenAI Image API Error Response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('OpenAI Image API Error: ' . $response->status() . ' - ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Image Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

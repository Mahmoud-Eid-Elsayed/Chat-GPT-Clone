<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImageGenerationController extends Controller
{
    public function generate(Request $request, Chat $chat)
    {
        // Validate the request
        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
        ]);

        try {
            // Example using OpenAI's DALL-E API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/images/generations', [
                        'prompt' => $validated['prompt'],
                        'n' => 1,
                        'size' => '1024x1024',
                        'response_format' => 'url',
                    ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate image: ' . $response->body(),
                ], 500);
            }

            $imageData = $response->json();
            $imageUrl = $imageData['data'][0]['url'] ?? null;

            if (!$imageUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'No image URL returned',
                ], 500);
            }

            // Download the image
            $imageContent = file_get_contents($imageUrl);
            $filename = 'generated_' . time() . '.png';
            $path = 'chat_images/' . $filename;

            // Store the image
            Storage::disk('public')->put($path, $imageContent);
            $fullPath = Storage::url($path); // This uses Laravel's asset URL helper

            // Create a new message
            $message = new Message();
            $message->chat_id = $chat->id;
            $message->role = 'user';
            $message->content = 'Generated image: ' . $validated['prompt'];
            $message->file_paths = [$path];
            $message->save();

            // Create AI response acknowledging the image
            $aiMessage = new Message();
            $aiMessage->chat_id = $chat->id;
            $aiMessage->role = 'assistant';
            $aiMessage->content = 'I\'ve generated an image based on your prompt: "' . $validated['prompt'] . '"';
            $aiMessage->save();

            return response()->json([
                'success' => true,
                'message' => $message,
                'aiMessage' => $aiMessage,
                'imageUrl' => $fullPath,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}

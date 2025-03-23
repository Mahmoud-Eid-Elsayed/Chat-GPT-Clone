<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function store(Request $request, Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'files.*' => 'sometimes|file|max:10240',
        ]);

        $filePaths = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('chat_files', 'public');
                $filePaths[] = $path;
            }
        }

        // Save user message
        $userMessage = Message::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $validated['content'],
            'file_paths' => $filePaths,
        ]);

        // Update chat title if this is the first message
        if ($chat->messages()->count() === 1 && $chat->title === 'New Chat') {
            $chat->update([
                'title' => substr($validated['content'], 0, 30) . '...',
            ]);
        }

        // Get AI response
        if ($request->has('stream') && $chat->modelOption->supports_streaming) {
            return response()->stream(function () use ($chat, $userMessage) {
                $this->aiService->streamResponse($chat, $userMessage);
            }, 200, [
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'text/event-stream',
            ]);
        } else {
            $aiResponse = $this->aiService->getResponse($chat, $userMessage);

            // Save AI response
            $assistantMessage = Message::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $aiResponse,
            ]);

            return response()->json([
                'message' => $assistantMessage,
            ]);
        }
    }

    public function update(Request $request, Chat $chat, Message $message)
    {
        if ($chat->user_id !== Auth::id() || $message->chat_id !== $chat->id || $message->role !== 'user') {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $message->update([
            'content' => $validated['content'],
        ]);

        // Delete subsequent messages
        $subsequentMessages = $chat->messages()->where('id', '>', $message->id)->get();
        foreach ($subsequentMessages as $subMessage) {
            $subMessage->delete();
        }

        return redirect()->route('chats.show', $chat);
    }

    public function destroy(Chat $chat, Message $message)
    {
        if ($chat->user_id !== Auth::id() || $message->chat_id !== $chat->id) {
            abort(403);
        }

        // Delete message and subsequent messages
        $subsequentMessages = $chat->messages()->where('id', '>=', $message->id)->get();
        foreach ($subsequentMessages as $subMessage) {
            // Delete any files associated with the message
            if (!empty($subMessage->file_paths)) {
                foreach ($subMessage->file_paths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }
            $subMessage->delete();
        }

        return redirect()->route('chats.show', $chat);
    }

    public function regenerate(Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        // Get the last user message
        $lastUserMessage = $chat->messages()->where('role', 'user')->latest()->first();
        if (!$lastUserMessage) {
            return redirect()->route('chats.show', $chat);
        }

        // Delete the last assistant message if it exists
        $lastAssistantMessage = $chat->messages()->where('role', 'assistant')->latest()->first();
        if ($lastAssistantMessage) {
            $lastAssistantMessage->delete();
        }

        // Get new AI response
        $aiResponse = $this->aiService->getResponse($chat, $lastUserMessage);

        // Save AI response
        Message::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => $aiResponse,
        ]);

        return redirect()->route('chats.show', $chat);
    }


    public function generateImage(Request $request, Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'prompt' => 'required|string',
        ]);

        try {
            // Save user message (image prompt)
            $userMessage = Message::create([
                'chat_id' => $chat->id,
                'role' => 'user',
                'content' => "Generate image: " . $validated['prompt'],
            ]);

            // Generate image
            $imageUrl = $this->aiService->generateImage($validated['prompt']);

            // Save AI response with the image
            $assistantMessage = Message::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => "![Generated Image](" . $imageUrl . ")\n\nImage generated based on your prompt: \"" . $validated['prompt'] . "\"",
            ]);

            return response()->json([
                'message' => $assistantMessage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate image: ' . $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\ModelOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $chats = Auth::user()->chats()->with('modelOption')->latest()->get();
        $modelOptions = ModelOption::where('active', true)->with('apiProvider')->get();

        return view('chats.index', compact('chats', 'modelOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'model_option_id' => 'required|exists:model_options,id',
        ]);

        $chat = Chat::create([
            'user_id' => Auth::id(),
            'model_option_id' => $validated['model_option_id'],
            'title' => 'New Chat',
        ]);

        return redirect()->route('chats.show', $chat);
    }

    public function show(Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        $chat->load('messages', 'modelOption');
        $chats = Auth::user()->chats()->with('modelOption')->latest()->get();
        $modelOptions = ModelOption::where('active', true)->with('apiProvider')->get();

        return view('chats.show', compact('chat', 'chats', 'modelOptions'));
    }

    public function update(Request $request, Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $chat->update($validated);

        return redirect()->route('chats.show', $chat);
    }

    public function destroy(Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        $chat->delete();

        return redirect()->route('chats.index');
    }
}
